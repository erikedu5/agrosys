import 'package:flutter/material.dart';

import '../../core/app_controller.dart';
import '../../core/models.dart';
import '../../core/pos_api.dart';
import 'inventory_editor.dart' show priceFields;

/// Cantidad decimal sin ceros sobrantes: 25000 → "250", 250 → "2.5".
String plainDecimal(int units) {
  final text = decimalText(units);
  return text.endsWith('.00')
      ? text.substring(0, text.length - 3)
      : text.endsWith('0')
      ? text.substring(0, text.length - 1)
      : text;
}

/// El tamaño es texto libre ("1000ml", "1 L"); se propone lo que se pueda leer.
(String, String) parseSize(String size) {
  final match = RegExp(r'^(\d+(?:[.,]\d+)?)\s*(\D.*)?$')
      .firstMatch(size.trim());
  if (match == null) return ('', '');
  final unit = (match[2] ?? '').trim();
  return (
    match[1]!.replaceAll(',', '.'),
    unit.length > 20 ? unit.substring(0, 20) : unit,
  );
}

/// Da de baja 1 unidad y da de alta sus medidas en `Medida <nombre>`
/// (InventoryManagement::dose en el servidor).
class InventoryDoseEditor extends StatefulWidget {
  final AppController controller;
  final String productId;
  final VoidCallback onCancel;
  final ValueChanged<Map<String, dynamic>> onSaved;
  const InventoryDoseEditor({
    super.key,
    required this.controller,
    required this.productId,
    required this.onCancel,
    required this.onSaved,
  });

  @override
  State<InventoryDoseEditor> createState() => _InventoryDoseEditorState();
}

class _InventoryDoseEditorState extends State<InventoryDoseEditor> {
  static const _permission = 'inventory.dose';
  final _content = TextEditingController(),
      _unit = TextEditingController(),
      _measure = TextEditingController();
  final _prices = {
    for (final field in priceFields.keys) field: TextEditingController(),
  };
  Map<String, dynamic> _product = {};
  Map<String, List<String>> _errors = {};
  String? _error, _operationId;
  bool _loading = true,
      _saving = false,
      _conflict = false,
      _acceptLoss = false,
      _pricesEdited = false;
  int _lastLeftover = 0;

  bool get _editable => !_saving && _operationId == null && !_conflict;

  int _units(TextEditingController field) {
    try {
      return decimalUnits(field.text.trim());
    } catch (_) {
      return 0;
    }
  }

  int get _contentUnits => _units(_content);
  int get _measureUnits => _units(_measure);
  int get _count => _contentUnits > 0 && _measureUnits > 0
      ? _contentUnits ~/ _measureUnits
      : 0;
  int get _leftover => _count > 0 ? _contentUnits - _count * _measureUnits : 0;
  String get _targetName => 'Medida ${_product['nombre'] ?? ''}';
  bool get _canSave =>
      _count >= 1 &&
      _count <= 99999 &&
      _unit.text.trim().isNotEmpty &&
      _units(_prices['precio_publico']!) > 0 &&
      (_leftover == 0 || _acceptLoss) &&
      !_conflict &&
      widget.controller.canWriteInventory(_permission);

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    for (final field in [_content, _unit, _measure, ..._prices.values]) {
      field.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
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
      final product = object(data['product']);
      final (content, unit) = parseSize(product['tamano']?.toString() ?? '');
      setState(() {
        _product = product;
        _content.text = content;
        _unit.text = unit;
        _measure.clear();
        _acceptLoss = false;
        _pricesEdited = false;
        _conflict = false;
        _loading = false;
        _suggestPrices();
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

  // Precio sugerido: el del envase repartido entre las medidas, hasta que el usuario lo cambie.
  void _suggestPrices() {
    if (_pricesEdited) return;
    for (final field in priceFields.keys) {
      int price;
      try {
        price = decimalUnits(_product[field]);
      } catch (_) {
        price = 0;
      }
      final count = _count;
      final each = count >= 1 && price > 0 ? (price + count ~/ 2) ~/ count : 0;
      _prices[field]!.text = each == 0 ? '' : decimalText(each < 1 ? 1 : each);
    }
  }

  void _changed() {
    setState(() {
      if (_leftover != _lastLeftover) _acceptLoss = false;
      _lastLeftover = _leftover;
      _suggestPrices();
    });
  }

  Future<void> _save() async {
    if (_saving || (_operationId == null && !_canSave)) return;
    setState(() {
      _saving = true;
      _error = null;
      _errors = {};
    });
    try {
      _operationId ??= await widget.controller.prepareInventoryRequest(
        'inventory/${widget.productId}/dose',
        _permission,
        {
          'contenido': decimalText(_contentUnits),
          'medida': decimalText(_measureUnits),
          'unidad': _unit.text.trim(),
          'acepta_perdida': _acceptLoss,
          for (final field in priceFields.keys)
            field: _prices[field]!.text.trim().isEmpty
                ? null
                : _prices[field]!.text.trim(),
          'version': _product['version'],
        },
      );
      final result = await widget.controller.retryInventoryRequest(
        _operationId!,
      );
      if (mounted) widget.onSaved(result);
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e is ApiFailure
              ? e.message
              : 'No se confirmó la dosificación. Reintenta la misma solicitud.';
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

  Widget _field(
    String key,
    TextEditingController controller,
    String label, {
    bool decimal = true,
    int? maxLength,
    String? hint,
    ValueChanged<String>? onChanged,
  }) => Padding(
    padding: const EdgeInsets.only(bottom: 12),
    child: TextField(
      key: ValueKey('dose-field-$key'),
      controller: controller,
      enabled: _editable,
      maxLength: maxLength,
      keyboardType: decimal
          ? const TextInputType.numberWithOptions(decimal: true)
          : TextInputType.text,
      onChanged: onChanged ?? (_) => _changed(),
      decoration: InputDecoration(
        labelText: label,
        hintText: hint,
        errorText: _errors[key]?.firstOrNull,
      ),
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
    final unit = _unit.text.trim();
    final error = Theme.of(context).colorScheme.error;
    return ListView(
      key: const Key('inventory-dose-scroll'),
      children: [
        Text(
          'Dosificar producto',
          style: Theme.of(context).textTheme.titleLarge,
        ),
        const SizedBox(height: 4),
        Text(
          'Se dará de baja 1 unidad de ${_product['nombre'] ?? ''}'
          '${(_product['tamano'] ?? '').toString().isEmpty ? '' : ' (${_product['tamano']})'}'
          ' y se darán de alta sus medidas.',
        ),
        const SizedBox(height: 12),
        if (!widget.controller.online) const Text('Conéctate para dosificar.'),
        if (_error != null)
          Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: Text(_error!, style: TextStyle(color: error)),
          ),
        for (final key in ['producto', 'acepta_perdida'])
          if (_errors[key]?.firstOrNull case final message?)
            Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Text(message, style: TextStyle(color: error)),
            ),
        if (_operationId != null)
          const Padding(
            padding: EdgeInsets.only(bottom: 12),
            child: Text(
              'La solicitud está guardada. Reinténtala para confirmar el resultado; conserva los mismos datos.',
            ),
          ),
        if (_conflict || _product['version'] == null)
          TextButton(
            onPressed: _saving ? null : _load,
            child: const Text('Recargar producto'),
          ),
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: _field(
                'contenido',
                _content,
                'Contenido del envase',
                hint: '1000',
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _field(
                'unidad',
                _unit,
                'Unidad',
                decimal: false,
                maxLength: 20,
                hint: 'ml',
              ),
            ),
          ],
        ),
        _field(
          'medida',
          _measure,
          'Tamaño de cada medida${unit.isEmpty ? '' : ' ($unit)'}',
          hint: '250',
        ),
        if (_count >= 1)
          Padding(
            key: const Key('dose-preview'),
            padding: const EdgeInsets.only(bottom: 12),
            child: Text(
              'Salen $_count ${_count == 1 ? 'medida' : 'medidas'} de '
              '${plainDecimal(_measureUnits)} $unit en "$_targetName".',
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
          )
        else if (_contentUnits > 0 && _measureUnits > 0)
          Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: Text(
              'La medida no puede ser mayor que el contenido del envase.',
              style: TextStyle(color: error),
            ),
          ),
        if (_count > 99999)
          Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: Text(
              'La medida es demasiado pequeña para el contenido del envase.',
              style: TextStyle(color: error),
            ),
          ),
        if (_leftover > 0)
          Card(
            key: const Key('dose-leftover'),
            margin: const EdgeInsets.only(bottom: 12),
            color: const Color(0xfffffbeb),
            shape: RoundedRectangleBorder(
              side: const BorderSide(color: Color(0xfffcd34d)),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Sobran ${plainDecimal(_leftover)} $unit que no completan una medida y se perderán del inventario.',
                    style: const TextStyle(
                      color: Color(0xff78350f),
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  CheckboxListTile(
                    key: const Key('dose-accept-loss'),
                    contentPadding: EdgeInsets.zero,
                    controlAffinity: ListTileControlAffinity.leading,
                    value: _acceptLoss,
                    onChanged: _editable
                        ? (v) => setState(() => _acceptLoss = v == true)
                        : null,
                    title: const Text(
                      'Acepto la pérdida del producto restante',
                    ),
                  ),
                ],
              ),
            ),
          ),
        for (final price in priceFields.entries)
          _field(
            price.key,
            _prices[price.key]!,
            price.value,
            onChanged: (_) => setState(() => _pricesEdited = true),
          ),
        const Text(
          'Precios por medida. Se sugiere el precio del envase entre el número de medidas; si la medida ya existe, se actualizan.',
        ),
        const SizedBox(height: 12),
        if (_saving) const LinearProgressIndicator(),
        Wrap(
          spacing: 12,
          children: [
            FilledButton(
              key: const Key('dose-save'),
              onPressed: !_saving && (_canSave || _operationId != null)
                  ? _save
                  : null,
              child: Text(
                _operationId == null ? 'Dosificar' : 'Reintentar dosificación',
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
    );
  }
}
