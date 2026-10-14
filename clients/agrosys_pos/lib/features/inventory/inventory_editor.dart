import 'package:flutter/material.dart';

import '../../core/app_controller.dart';
import '../../core/models.dart';
import '../../core/pos_api.dart';

class InventoryEditor extends StatefulWidget {
  final AppController controller;
  final String action;
  final String? productId;
  final VoidCallback onCancel;
  final ValueChanged<Map<String, dynamic>> onSaved;
  const InventoryEditor({
    super.key,
    required this.controller,
    required this.action,
    this.productId,
    required this.onCancel,
    required this.onSaved,
  });

  @override
  State<InventoryEditor> createState() => _InventoryEditorState();
}

class _InventoryEditorState extends State<InventoryEditor> {
  final _form = GlobalKey<FormState>();
  final _fields = {
    for (final name in [
      'nombre',
      'tamano',
      'barcode',
      'ingrediente_activo',
      'precio_ieps',
      'precio_unitario',
      'ieps',
      'cantidad',
    ])
      name: TextEditingController(),
  };
  List<Map<String, dynamic>> _categories = [], _brands = [];
  String? _category, _brand, _version, _error, _operationId;
  Map<String, List<String>> _errors = {};
  bool _loading = true,
      _saving = false,
      _manageCosts = false,
      _conflict = false;

  String get _permission => {
    'create': 'product.create',
    'update': 'product.update',
    'prices': 'product.prices.update',
    'add': 'inventory.add',
  }[widget.action]!;
  String get _title => {
    'create': 'Nuevo producto',
    'update': 'Editar producto',
    'prices': 'Actualizar precios',
    'add': 'Agregar existencias',
  }[widget.action]!;
  bool get _canShowCosts =>
      _manageCosts && widget.controller.hasPermission('product.costs.manage');
  bool get _editable => !_saving && _operationId == null && !_conflict;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    for (final field in _fields.values) {
      field.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
    if (widget.action == 'add') {
      setState(() => _loading = false);
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
      _errors = {};
    });
    try {
      final data = await widget.controller.inventoryFormData(
        productId: widget.productId,
      );
      if (!mounted) return;
      final product = data['product'] == null
          ? <String, dynamic>{}
          : object(data['product']);
      setState(() {
        _categories = (data['categories'] as List).map(object).toList();
        _brands = (data['brands'] as List).map(object).toList();
        _category = product['id_clasificacion'] as String?;
        _brand = product['id_marca'] as String?;
        if (!_categories.any((v) => v['id'] == _category)) _category = null;
        if (!_brands.any((v) => v['id'] == _brand)) _brand = null;
        _manageCosts =
            data['manageCosts'] == true &&
            widget.controller.hasPermission('product.costs.manage');
        _version = product['version'] as String?;
        for (final e in _fields.entries) {
          e.value.text = product[e.key]?.toString() ?? '';
        }
        _conflict = false;
        _loading = false;
      });
    } catch (e) {
      if (mounted) {
        setState(() {
          _loading = false;
          _error = e.toString();
        });
      }
    }
  }

  Future<void> _save() async {
    if (_saving || !widget.controller.canWriteInventory(_permission)) return;
    if (_operationId == null &&
        (_conflict || !_form.currentState!.validate())) {
      return;
    }
    setState(() {
      _saving = true;
      _error = null;
      _errors = {};
    });
    try {
      if (_operationId == null) {
        final payload = <String, dynamic>{};
        if (widget.action == 'add') {
          payload['cantidad'] = _fields['cantidad']!.text.trim();
        } else {
          payload['precio_ieps'] = _fields['precio_ieps']!.text.trim();
          if (_canShowCosts) {
            payload['precio_unitario'] =
                _fields['precio_unitario']!.text.trim().isEmpty
                ? null
                : _fields['precio_unitario']!.text.trim();
            payload['ieps'] = _fields['ieps']!.text.trim().isEmpty
                ? null
                : _fields['ieps']!.text.trim();
          }
          if (widget.action != 'prices') {
            for (final field in [
              'nombre',
              'tamano',
              'barcode',
              'ingrediente_activo',
            ]) {
              payload[field] = _fields[field]!.text.trim();
            }
            payload['id_clasificacion'] = _category;
            payload['id_marca'] = _brand;
          }
          if (_version != null) payload['version'] = _version;
        }
        final path = widget.action == 'create'
            ? 'inventory/products'
            : 'inventory/${widget.productId}/${widget.action}';
        _operationId = await widget.controller.prepareInventoryRequest(
          path,
          _permission,
          payload,
        );
      }
      final result = await widget.controller.retryInventoryRequest(
        _operationId!,
      );
      if (mounted) widget.onSaved(result);
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e is ApiFailure
              ? e.message
              : 'No se confirmó el guardado. Reintenta la misma solicitud.';
          if (e is ApiFailure) {
            _errors = e.errors;
            if ([404, 409, 422].contains(e.status)) {
              _operationId = null;
              _conflict = e.status == 409;
            }
          }
        });
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  void _recalculate(String trigger) {
    if (!_canShowCosts || !_editable) return;
    int? amount(String field) {
      try {
        return decimalUnits(_fields[field]!.text.trim());
      } catch (_) {
        return null;
      }
    }

    final cost = amount('precio_unitario'),
        gain = amount('ieps'),
        price = amount('precio_ieps');
    if (cost != null && gain != null && trigger != 'precio_ieps') {
      _fields['precio_ieps']!.text = decimalText(
        (cost * (10000 + gain) + 5000) ~/ 10000,
      );
    } else if (price != null && gain != null && trigger != 'precio_unitario') {
      final divisor = 10000 + gain;
      _fields['precio_unitario']!.text = decimalText(
        (price * 10000 + divisor ~/ 2) ~/ divisor,
      );
    } else if (cost != null && cost > 0 && price != null && trigger != 'ieps') {
      _fields['ieps']!.text = decimalText(
        ((price - cost) * 10000 / cost).round(),
      );
    }
  }

  String? _decimal(
    String? value, {
    bool optional = false,
    bool zero = false,
    int max = 9999999999,
  }) {
    final text = value?.trim() ?? '';
    if (optional && text.isEmpty) return null;
    try {
      final units = decimalUnits(text);
      if (units < (zero ? 0 : 1) || units > max) {
        return 'Cantidad o importe fuera de rango.';
      }
      return null;
    } catch (_) {
      return 'Usa un número con hasta dos decimales.';
    }
  }

  Widget _text(
    String name,
    String label, {
    bool required = false,
    bool decimal = false,
    bool optional = false,
    bool zero = false,
    int? maxLength,
  }) => Padding(
    padding: const EdgeInsets.only(bottom: 12),
    child: TextFormField(
      key: ValueKey('inventory-field-$name'),
      controller: _fields[name],
      enabled: _editable,
      maxLength: maxLength,
      onChanged: (v) {
        if (['precio_ieps', 'precio_unitario', 'ieps'].contains(name)) {
          _recalculate(name);
        }
      },
      keyboardType: decimal
          ? const TextInputType.numberWithOptions(decimal: true)
          : TextInputType.text,
      decoration: InputDecoration(
        labelText: label,
        errorText: _errors[name]?.firstOrNull,
      ),
      validator: (value) {
        if (required && (value?.trim().isEmpty ?? true)) {
          return 'Campo obligatorio.';
        }
        if (decimal) {
          return _decimal(
            value,
            optional: optional,
            zero: zero,
            max: name == 'cantidad' ? 99999999 : 9999999999,
          );
        }
        return null;
      },
    ),
  );

  @override
  Widget build(BuildContext context) {
    if (!widget.controller.hasPermission(_permission)) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text('Operación no autorizada.'),
            TextButton(
              onPressed: widget.onCancel,
              child: const Text('Volver al inventario'),
            ),
          ],
        ),
      );
    }
    if (_loading) return const Center(child: CircularProgressIndicator());
    return Form(
      key: _form,
      child: ListView(
        key: const Key('inventory-editor-scroll'),
        children: [
          Text(_title, style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: 12),
          if (!widget.controller.online)
            const Text('Conéctate para guardar cambios de inventario.'),
          if (_error != null)
            Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Text(
                _error!,
                style: TextStyle(color: Theme.of(context).colorScheme.error),
              ),
            ),
          if (_operationId != null)
            const Padding(
              padding: EdgeInsets.only(bottom: 12),
              child: Text(
                'La solicitud está guardada. Reinténtala para confirmar el resultado; conserva los mismos datos.',
              ),
            ),
          if (_conflict ||
              (_version == null &&
                  widget.productId != null &&
                  widget.action != 'add'))
            TextButton(
              onPressed: _saving ? null : _load,
              child: const Text('Recargar producto'),
            ),
          if (widget.action == 'add')
            _text(
              'cantidad',
              'Cantidad a agregar',
              required: true,
              decimal: true,
            )
          else ...[
            if (widget.action != 'prices') ...[
              _text('nombre', 'Nombre', required: true, maxLength: 255),
              _text('tamano', 'Presentación', required: true, maxLength: 100),
              DropdownButtonFormField<String>(
                key: ValueKey('category-$_category'),
                initialValue: _category,
                isExpanded: true,
                decoration: InputDecoration(
                  labelText: 'Categoría',
                  errorText: _errors['id_clasificacion']?.firstOrNull,
                ),
                items: [
                  for (final item in _categories)
                    DropdownMenuItem(
                      value: item['id'] as String,
                      child: Text(
                        item['name'] as String,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                ],
                onChanged: _editable
                    ? (v) => setState(() => _category = v)
                    : null,
                validator: (v) =>
                    v == null ? 'Selecciona una categoría.' : null,
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                key: ValueKey('brand-$_brand'),
                initialValue: _brand,
                isExpanded: true,
                decoration: InputDecoration(
                  labelText: 'Marca',
                  errorText: _errors['id_marca']?.firstOrNull,
                ),
                items: [
                  for (final item in _brands)
                    DropdownMenuItem(
                      value: item['id'] as String,
                      child: Text(
                        item['name'] as String,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                ],
                onChanged: _editable ? (v) => setState(() => _brand = v) : null,
                validator: (v) => v == null ? 'Selecciona una marca.' : null,
              ),
              const SizedBox(height: 12),
              _text('barcode', 'Código de barras', maxLength: 255),
              _text('ingrediente_activo', 'Ingrediente activo', maxLength: 255),
            ],
            if (_canShowCosts) ...[
              _text(
                'precio_unitario',
                'Precio de compra',
                decimal: true,
                optional: true,
                zero: true,
              ),
              _text(
                'ieps',
                'Ganancia (%)',
                decimal: true,
                optional: true,
                zero: true,
              ),
            ],
            _text(
              'precio_ieps',
              'Precio de venta',
              required: true,
              decimal: true,
            ),
          ],
          if (_saving) const LinearProgressIndicator(),
          Wrap(
            spacing: 12,
            children: [
              FilledButton(
                key: const Key('inventory-save'),
                onPressed:
                    !_saving &&
                        !_conflict &&
                        (widget.action == 'create' ||
                            widget.action == 'add' ||
                            _version != null) &&
                        widget.controller.canWriteInventory(_permission)
                    ? _save
                    : null,
                child: Text(
                  _operationId == null ? 'Guardar' : 'Reintentar guardado',
                ),
              ),
              TextButton(
                onPressed: _saving ? null : widget.onCancel,
                child: Text(
                  _operationId == null ? 'Cancelar' : 'Volver al inventario',
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
