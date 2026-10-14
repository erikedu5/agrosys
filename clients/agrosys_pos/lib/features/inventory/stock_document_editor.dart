import 'package:flutter/material.dart';

import '../../core/app_controller.dart';
import '../../core/models.dart';
import '../../core/pos_api.dart';

class StockDocumentEditor extends StatefulWidget {
  final AppController controller;
  final String kind;
  final VoidCallback onCancel;
  final ValueChanged<Map<String, dynamic>> onSaved;
  const StockDocumentEditor({
    super.key,
    required this.controller,
    required this.kind,
    required this.onCancel,
    required this.onSaved,
  });
  @override
  State<StockDocumentEditor> createState() => _StockDocumentEditorState();
}

class _StockDocumentEditorState extends State<StockDocumentEditor> {
  final _supplier = TextEditingController(),
      _date = TextEditingController(),
      _payment = TextEditingController(text: '0.00');
  final _notes = TextEditingController(),
      _query = TextEditingController(),
      _quantity = TextEditingController(),
      _cost = TextEditingController();
  List<ProductView> _products = [];
  List<Map<String, dynamic>> _destinations = [];
  final List<Map<String, dynamic>> _items = [];
  String? _product, _destination, _error, _operationId;
  bool _loading = true, _saving = false;
  int _searchRequest = 0;
  bool get _purchase => widget.kind == 'purchases';
  String get _permission => _purchase ? 'purchase.create' : 'transfer.create';
  bool get _editable => !_saving && _operationId == null;
  int get _total => _items.fold(
    0,
    (sum, row) =>
        sum +
        (decimalUnits(row['quantity']) * decimalUnits(row['cost']) + 50) ~/ 100,
  );
  @override
  void initState() {
    super.initState();
    _date.text = DateTime.now().toIso8601String().substring(0, 10);
    _load();
  }

  @override
  void dispose() {
    for (final field in [
      _supplier,
      _date,
      _payment,
      _notes,
      _query,
      _quantity,
      _cost,
    ]) {
      field.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
    try {
      if (!_purchase) {
        final data = await widget.controller.stockWorkflowData(
          'transfers/destinations',
          _permission,
        );
        if (!mounted) return;
        _destinations = (data['destinations'] as List).map(object).toList();
      }
      await _search();
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _search() async {
    final request = ++_searchRequest;
    final repo = widget.controller.repository;
    if (repo == null) return;
    final products = await repo.products(_query.text);
    if (!mounted ||
        request != _searchRequest ||
        widget.controller.repository != repo) {
      return;
    }
    setState(() {
      _products = _purchase
          ? products
          : products.where((p) => p.estimatedQuantity > 0).toList();
      if (!_products.any((p) => p.id == _product)) _product = null;
    });
  }

  int _decimal(String value, {bool zero = false, int max = 9999999999}) {
    final units = decimalUnits(value.trim());
    if (units < (zero ? 0 : 1) || units > max) {
      throw const FormatException('Cantidad o importe fuera de rango.');
    }
    return units;
  }

  void _add() {
    if (!_editable) return;
    try {
      if (_product == null) {
        throw const FormatException('Selecciona un producto.');
      }
      if (_items.any((row) => row['productId'] == _product)) {
        throw const FormatException(
          'El producto ya está agregado. Retíralo para cambiar su cantidad.',
        );
      }
      final quantity = _decimal(_quantity.text, max: 99999999);
      final cost = _purchase ? _decimal(_cost.text, zero: true) : 0;
      final product = _products.firstWhere((p) => p.id == _product);
      setState(() {
        _items.add({
          'productId': _product,
          'name': product.name,
          'size': product.size,
          'quantity': decimalText(quantity),
          if (_purchase) 'cost': decimalText(cost),
        });
        _product = null;
        _quantity.clear();
        _cost.clear();
        _error = null;
      });
    } catch (e) {
      setState(() => _error = e.toString());
    }
  }

  Future<void> _save() async {
    if (_saving || !widget.controller.canWriteInventory(_permission)) return;
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      if (_operationId == null) {
        if (_items.isEmpty) {
          throw const FormatException('Agrega al menos un producto.');
        }
        final payload = <String, dynamic>{
          'items': _items
              .map(
                (row) => {
                  'productId': row['productId'],
                  'quantity': row['quantity'],
                  if (_purchase) 'cost': row['cost'],
                },
              )
              .toList(),
        };
        if (_purchase) {
          if (_supplier.text.trim().isEmpty) {
            throw const FormatException('Indica el proveedor.');
          }
          final date = DateTime.tryParse(_date.text.trim());
          if (date == null ||
              date.toIso8601String().substring(0, 10) != _date.text.trim()) {
            throw const FormatException('Indica una fecha válida AAAA-MM-DD.');
          }
          final payment = _decimal(_payment.text, zero: true);
          if (_total > 9999999999 || payment > _total) {
            throw const FormatException('Revisa el total y el pago inicial.');
          }
          payload.addAll({
            'supplier': _supplier.text.trim(),
            'date': _date.text.trim(),
            'payment': decimalText(payment),
          });
        } else {
          if (_destination == null) {
            throw const FormatException('Selecciona la sucursal destino.');
          }
          payload.addAll({
            'destination_id': _destination,
            'notes': _notes.text.trim(),
          });
        }
        _operationId = await widget.controller.prepareInventoryRequest(
          '${widget.kind}/create',
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
              ? [e.message, ...e.errors.values.expand((v) => v)].join('\n')
              : e.toString();
          if (e is ApiFailure && [404, 409, 422].contains(e.status)) {
            _operationId = null;
          }
        });
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Widget _field(
    String name,
    String label,
    TextEditingController controller, {
    bool number = false,
  }) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 6),
    child: TextField(
      key: ValueKey('stock-field-$name'),
      controller: controller,
      enabled: _editable,
      keyboardType: number
          ? const TextInputType.numberWithOptions(decimal: true)
          : TextInputType.text,
      decoration: InputDecoration(
        labelText: label,
        border: const OutlineInputBorder(),
      ),
    ),
  );
  @override
  Widget build(BuildContext context) {
    if (!widget.controller.hasPermission(_permission)) {
      return Column(
        children: [
          const Text('Operación no autorizada.'),
          TextButton(onPressed: widget.onCancel, child: const Text('Volver')),
        ],
      );
    }
    if (_loading) return const Center(child: CircularProgressIndicator());
    return ListView(
      key: const Key('stock-editor-scroll'),
      children: [
        Text(
          _purchase ? 'Nueva compra' : 'Nueva transferencia',
          style: Theme.of(context).textTheme.titleLarge,
        ),
        if (_purchase) ...[
          _field('supplier', 'Proveedor', _supplier),
          _field('date', 'Fecha (AAAA-MM-DD)', _date),
        ] else ...[
          const Text(
            'Las existencias se moverán cuando la sucursal destino confirme la recepción.',
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<String>(
            key: ValueKey('stock-destination-$_destination'),
            initialValue: _destination,
            isExpanded: true,
            decoration: const InputDecoration(
              labelText: 'Sucursal destino',
              border: OutlineInputBorder(),
            ),
            items: _destinations
                .map(
                  (row) => DropdownMenuItem(
                    value: row['id'] as String,
                    child: Text(row['name'] as String),
                  ),
                )
                .toList(),
            onChanged: _editable
                ? (v) => setState(() => _destination = v)
                : null,
          ),
          if (_destinations.isEmpty)
            const Text('No hay otra sucursal disponible en esta empresa.'),
          _field('notes', 'Observaciones', _notes),
        ],
        const SizedBox(height: 12),
        TextField(
          key: const Key('stock-product-search'),
          controller: _query,
          enabled: _editable,
          onChanged: (_) => _search(),
          decoration: const InputDecoration(
            labelText: 'Buscar producto, ID o código',
          ),
        ),
        const SizedBox(height: 12),
        DropdownButtonFormField<String>(
          key: ValueKey('stock-product-$_product'),
          initialValue: _product,
          isExpanded: true,
          decoration: const InputDecoration(
            labelText: 'Producto',
            border: OutlineInputBorder(),
          ),
          items: _products
              .map(
                (p) => DropdownMenuItem(
                  value: p.id,
                  child: Text(
                    '${p.name} · ${p.size}',
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              )
              .toList(),
          onChanged: _editable ? (v) => setState(() => _product = v) : null,
        ),
        const Text(
          'Hasta 200 resultados del catálogo local. Refina la búsqueda si es necesario.',
        ),
        _field('quantity', 'Cantidad', _quantity, number: true),
        if (_purchase)
          _field('cost', 'Precio de compra por unidad', _cost, number: true),
        Align(
          alignment: Alignment.centerLeft,
          child: OutlinedButton(
            key: const Key('stock-add-item'),
            onPressed: _editable ? _add : null,
            child: const Text('Agregar producto'),
          ),
        ),
        for (final row in _items)
          ListTile(
            contentPadding: EdgeInsets.zero,
            title: Text('${row['name']} · ${row['size']}'),
            subtitle: Text(
              '${row['quantity']} unidades${_purchase ? ' · \$${row['cost']} por unidad' : ''}',
            ),
            trailing: IconButton(
              tooltip: 'Retirar producto',
              onPressed: _editable
                  ? () => setState(() => _items.remove(row))
                  : null,
              icon: const Icon(Icons.remove_circle_outline),
            ),
          ),
        if (_purchase) ...[
          Text('Total: ${money(_total)}'),
          _field(
            'payment',
            'Pago inicial (0 para crédito)',
            _payment,
            number: true,
          ),
        ],
        if (_error != null)
          Text(
            _error!,
            style: TextStyle(color: Theme.of(context).colorScheme.error),
          ),
        if (!widget.controller.online)
          const Text('Esta operación requiere conexión.'),
        if (_operationId != null)
          const Text(
            'Solicitud guardada. Reintenta para confirmar el resultado.',
          ),
        const SizedBox(height: 16),
        Wrap(
          spacing: 8,
          children: [
            FilledButton(
              key: const Key('stock-save'),
              onPressed:
                  !_saving && widget.controller.canWriteInventory(_permission)
                  ? _save
                  : null,
              child: Text(
                _saving
                    ? 'Guardando…'
                    : _operationId != null
                    ? 'Reintentar solicitud'
                    : 'Guardar',
              ),
            ),
            TextButton(
              onPressed: _saving ? null : widget.onCancel,
              child: const Text('Volver'),
            ),
          ],
        ),
      ],
    );
  }
}
