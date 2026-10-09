import 'package:flutter/material.dart';

import '../../core/app_controller.dart';
import '../../core/models.dart';
import '../../core/pos_api.dart';

class InventoryDestructiveAction extends StatefulWidget {
  final AppController controller;
  final String action, productId;
  final VoidCallback onCancel;
  final ValueChanged<Map<String, dynamic>> onSaved;
  const InventoryDestructiveAction({
    super.key,
    required this.controller,
    required this.action,
    required this.productId,
    required this.onCancel,
    required this.onSaved,
  });

  @override
  State<InventoryDestructiveAction> createState() =>
      _InventoryDestructiveActionState();
}

class _InventoryDestructiveActionState
    extends State<InventoryDestructiveAction> {
  Map<String, dynamic>? _preview;
  String? _error, _operationId;
  bool _loading = true, _saving = false, _confirmed = false;
  String get _permission =>
      widget.action == 'delete' ? 'product.delete' : 'inventory.reset';
  String get _title =>
      widget.action == 'delete' ? 'Eliminar producto' : 'Resetear existencias';

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _preview = null;
      _confirmed = false;
      _error = null;
    });
    try {
      final data = await widget.controller.inventoryDestructivePreview(
        widget.productId,
        widget.action,
      );
      if (mounted) setState(() => _preview = data);
    } catch (e) {
      if (mounted) {
        setState(
          () => _error = e is ApiFailure
              ? e.message
              : 'No se pudieron consultar los datos actuales.',
        );
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _save() async {
    if (_saving ||
        !_confirmed ||
        !widget.controller.canWriteInventory(_permission)) {
      return;
    }
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      _operationId ??= await widget.controller.prepareInventoryRequest(
        'inventory/${widget.productId}/${widget.action}',
        _permission,
        {
          'version': _preview!['version'],
          'stock_revision': _preview!['stockRevision'],
          'confirmed': true,
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
              : 'No se recibió la confirmación. Reintenta la misma solicitud.';
          if (e is ApiFailure && [404, 409, 422].contains(e.status)) {
            _operationId = null;
            _preview = null;
            _confirmed = false;
          }
        });
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

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
    final blocked =
        widget.action == 'delete' &&
        (_preview?['branchesWithStock'] as List? ?? []).isNotEmpty;
    return ListView(
      key: const Key('inventory-destructive-scroll'),
      children: [
        Text(_title, style: Theme.of(context).textTheme.titleLarge),
        const SizedBox(height: 12),
        if (_loading) const Center(child: CircularProgressIndicator()),
        if (_preview != null) ...[
          Text('${_preview!['name']} · ${_preview!['size']}'),
          Text('Sucursal: ${widget.controller.session!.branch.name}'),
          Text('Existencia confirmada: ${_preview!['quantity']}'),
          const SizedBox(height: 12),
          Text(
            widget.action == 'delete'
                ? 'El producto se retirará del catálogo de todas las sucursales de la empresa. Su historial se conservará.'
                : 'La existencia de este producto quedará en cero en esta sucursal. Se registrará el ajuste en el cardex.',
          ),
          if (blocked) ...[
            const SizedBox(height: 12),
            const Text(
              'No se puede eliminar: hay existencias en estas sucursales.',
            ),
            for (final row in (_preview!['branchesWithStock'] as List).map(
              object,
            ))
              Text('${row['name']}: ${row['quantity']}'),
          ] else
            CheckboxListTile(
              key: const Key('inventory-destructive-confirm'),
              contentPadding: EdgeInsets.zero,
              title: Text(
                widget.action == 'delete'
                    ? 'Confirmo eliminar este producto.'
                    : 'Confirmo dejar estas existencias en cero.',
              ),
              value: _confirmed,
              onChanged: _saving || _operationId != null
                  ? null
                  : (value) => setState(() => _confirmed = value ?? false),
            ),
        ],
        if (_error != null)
          Text(
            _error!,
            style: TextStyle(color: Theme.of(context).colorScheme.error),
          ),
        if (!widget.controller.online)
          const Text('Esta operación requiere conexión.'),
        const SizedBox(height: 16),
        Wrap(
          spacing: 8,
          children: [
            if (_preview == null && !_loading)
              OutlinedButton(
                onPressed: widget.controller.canWriteInventory(_permission)
                    ? _load
                    : null,
                child: const Text('Recargar datos'),
              ),
            if (_preview != null && !blocked)
              FilledButton(
                key: const Key('inventory-destructive-save'),
                style: FilledButton.styleFrom(
                  backgroundColor: Theme.of(context).colorScheme.error,
                ),
                onPressed:
                    !_saving &&
                        _confirmed &&
                        widget.controller.canWriteInventory(_permission)
                    ? _save
                    : null,
                child: Text(
                  _saving
                      ? 'Confirmando…'
                      : _operationId != null
                      ? 'Reintentar solicitud'
                      : _title,
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
