import 'dart:async';

import 'package:flutter/material.dart';
import 'package:drift/drift.dart'
    show BooleanExpressionOperators, StringExpressionOperators;

import '../../core/app_controller.dart';
import '../../core/models.dart';
import '../../core/pos_api.dart';
import '../../data/database.dart';
import 'inventory_editor.dart';
import 'inventory_destructive_action.dart';

class InventoryScreen extends StatefulWidget {
  final AppController controller;
  const InventoryScreen({super.key, required this.controller});

  @override
  State<InventoryScreen> createState() => _InventoryScreenState();
}

class _InventoryScreenState extends State<InventoryScreen> {
  final _search = TextEditingController();
  Timer? _debounce;
  List<ProductView> _products = [];
  List<String> _categories = [];
  String? _category, _error, _movementError;
  ProductView? _selected;
  String? _editorAction, _editorProductId, _notice;
  List<InventoryRequest> _pending = [];
  bool _retrying = false;
  List<Map<String, dynamic>> _movements = [];
  bool _loading = true, _movementLoading = false, _more = false;
  int _request = 0, _movementRequest = 0, _generation = -1, _page = 0;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void didUpdateWidget(InventoryScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (_generation != widget.controller.generation) _load();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _search.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    final c = widget.controller;
    if (!c.canReadInventory) return;
    final request = ++_request;
    _generation = c.generation;
    final repo = c.repository!;
    try {
      final pending =
          await (repo.db.select(repo.db.inventoryRequests)..where(
                (t) => t.status.equals('pending') & t.path.like('inventory/%'),
              ))
              .get();
      final categories = await repo.productCategories();
      final category = categories.contains(_category) ? _category : null;
      final products = await repo.products(
        _search.text,
        classification: category,
      );
      final selected = _selected == null
          ? null
          : await repo.productById(_selected!.id);
      if (!mounted ||
          request != _request ||
          !c.canReadInventory ||
          c.repository != repo) {
        return;
      }
      setState(() {
        _pending = pending;
        _products = products;
        _categories = categories;
        _category = category;
        _selected = selected;
        _loading = false;
        _error = null;
      });
    } catch (_) {
      if (mounted && request == _request) {
        setState(() {
          _loading = false;
          _error =
              'No se pudo leer el inventario local. Reintenta la consulta.';
        });
      }
    }
  }

  void _select(ProductView product) {
    _debounce?.cancel();
    _request++;
    _movementRequest++;
    setState(() {
      _selected = product;
      _movements = [];
      _movementError = null;
      _movementLoading = false;
      _more = false;
      _page = 0;
    });
  }

  Future<void> _loadMovements({bool append = false}) async {
    final product = _selected;
    if (product == null || _movementLoading) return;
    final request = ++_movementRequest;
    setState(() {
      _movementLoading = true;
      _movementError = null;
    });
    try {
      final result = await widget.controller.inventoryMovements(
        product.id,
        page: append ? _page + 1 : 1,
      );
      if (!mounted ||
          request != _movementRequest ||
          _selected?.id != product.id ||
          !widget.controller.canReadInventory) {
        return;
      }
      setState(() {
        final rows = (result['movements'] as List).map(object).toList();
        _movements = append ? [..._movements, ...rows] : rows;
        _page = result['page'] as int;
        _more = result['hasMore'] == true;
      });
    } catch (e) {
      if (mounted && request == _movementRequest) {
        setState(
          () => _movementError = e is ApiFailure
              ? e.message
              : 'No se pudieron leer los movimientos.',
        );
      }
    } finally {
      if (mounted && request == _movementRequest) {
        setState(() => _movementLoading = false);
      }
    }
  }

  void _edit(String action) {
    setState(() {
      _editorAction = action;
      _editorProductId = _selected?.id;
      _notice = null;
    });
  }

  Future<void> _retry(InventoryRequest row) async {
    if (_retrying) return;
    setState(() {
      _retrying = true;
      _notice = null;
    });
    try {
      final result = await widget.controller.retryInventoryRequest(
        row.operationId,
      );
      if (!mounted) return;
      setState(
        () =>
            _notice = result['syncWarning'] as String? ?? 'Cambios guardados.',
      );
    } catch (e) {
      if (mounted) setState(() => _notice = e.toString());
    } finally {
      if (mounted) {
        setState(() => _retrying = false);
        await _load();
      }
    }
  }

  Widget _editor(AppController c) {
    void cancel() {
      setState(() => _editorAction = null);
      _load();
    }

    void saved(Map<String, dynamic> result) {
      setState(() {
        _notice =
            result['syncWarning'] as String? ??
            switch (_editorAction) {
              'delete' => 'Producto eliminado.',
              'reset' => 'Existencias reseteadas a cero.',
              _ => 'Cambios guardados.',
            };
        if (_editorAction == 'delete') _selected = null;
        _editorAction = null;
        _movements = [];
        _page = 0;
        _more = false;
        _movementRequest++;
      });
      _load();
    }

    if (['reset', 'delete'].contains(_editorAction)) {
      return InventoryDestructiveAction(
        key: ValueKey('$_editorAction-$_editorProductId'),
        controller: c,
        action: _editorAction!,
        productId: _editorProductId!,
        onCancel: cancel,
        onSaved: saved,
      );
    }
    return InventoryEditor(
      key: ValueKey('$_editorAction-$_editorProductId'),
      controller: c,
      action: _editorAction!,
      productId: _editorAction == 'create' ? null : _editorProductId,
      onCancel: cancel,
      onSaved: saved,
    );
  }

  String _date(DateTime? value) => value == null
      ? 'Sin sincronizar'
      : value.toLocal().toString().substring(0, 16);

  @override
  Widget build(BuildContext context) {
    final c = widget.controller;
    if (!c.canReadInventory) {
      return const Center(child: Text('Inventario no autorizado.'));
    }
    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  'Inventario',
                  style: Theme.of(context).textTheme.headlineMedium,
                ),
              ),
              if (c.hasPermission('product.create') && _editorAction == null)
                IconButton(
                  key: const Key('inventory-create'),
                  tooltip: 'Nuevo producto',
                  icon: const Icon(Icons.add),
                  onPressed: c.canWriteInventory('product.create')
                      ? () => _edit('create')
                      : null,
                ),
            ],
          ),
          const SizedBox(height: 8),
          Text('Sucursal: ${c.session!.branch.name}'),
          Text(
            'Última actualización: ${_date(c.syncInfo?.syncedAt)} · Existencias estimadas',
          ),
          if (!c.online)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 8),
              child: Text(
                'Sin conexión. Consulta el catálogo guardado; el cardex requiere conexión.',
              ),
            ),
          const SizedBox(height: 12),
          if (_error != null)
            Text(
              _error!,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
          if (_notice != null) Text(_notice!),
          if (_editorAction == null && _pending.isNotEmpty)
            ConstrainedBox(
              constraints: const BoxConstraints(maxHeight: 140),
              child: ListView(
                shrinkWrap: true,
                children: [
                  for (final row in _pending)
                    if (c.hasPermission(row.permission))
                      ListTile(
                        title: const Text(
                          'Solicitud de inventario por confirmar',
                        ),
                        subtitle: Text(switch (row.permission) {
                          'inventory.add' => 'Entrada de existencias',
                          'inventory.reset' =>
                            'Reseteo de existencias confirmado previamente',
                          'product.delete' =>
                            'Eliminación de producto confirmada previamente',
                          _ => 'Cambios de producto',
                        }),
                        trailing: TextButton(
                          onPressed:
                              !_retrying && c.canWriteInventory(row.permission)
                              ? () => _retry(row)
                              : null,
                          child: const Text('Reintentar solicitud'),
                        ),
                      ),
                ],
              ),
            ),
          Expanded(
            child: _editorAction != null
                ? _editor(c)
                : _selected == null
                ? _list(context)
                : _detail(context, _selected!),
          ),
        ],
      ),
    );
  }

  Widget _list(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      TextField(
        key: const Key('inventory-search'),
        controller: _search,
        onChanged: (_) {
          _debounce?.cancel();
          _debounce = Timer(const Duration(milliseconds: 150), _load);
        },
        onSubmitted: (_) {
          _debounce?.cancel();
          _load();
        },
        decoration: InputDecoration(
          labelText: 'Buscar nombre, identificador o código de barras',
          prefixIcon: const Icon(Icons.search),
          suffixIcon: IconButton(
            tooltip: 'Limpiar búsqueda',
            icon: const Icon(Icons.clear),
            onPressed: () {
              _debounce?.cancel();
              _search.clear();
              _load();
            },
          ),
        ),
      ),
      if (_categories.isNotEmpty)
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 8),
          child: SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                for (final category in <String?>[null, ..._categories])
                  Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: ChoiceChip(
                      label: Text(category ?? 'Todas'),
                      selected: category == _category,
                      onSelected: (_) {
                        setState(() => _category = category);
                        _load();
                      },
                    ),
                  ),
              ],
            ),
          ),
        ),
      if (_loading) const LinearProgressIndicator(),
      Expanded(
        child: _products.isEmpty && !_loading
            ? Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Text('No hay productos activos para esta búsqueda.'),
                    if (_error != null)
                      TextButton(
                        onPressed: _load,
                        child: const Text('Reintentar'),
                      ),
                  ],
                ),
              )
            : ListView.separated(
                itemCount: _products.length,
                separatorBuilder: (_, _) => const SizedBox(height: 8),
                itemBuilder: (context, i) {
                  final p = _products[i];
                  return Card(
                    child: ListTile(
                      key: ValueKey('inventory-product-${p.id}'),
                      title: Text(p.name),
                      subtitle: Text(
                        '${p.classification.isEmpty ? 'Sin categoría' : p.classification} · ${p.size}\nExistencia estimada: ${decimalText(p.estimatedQuantity)} · ${money(p.priceCents)}',
                      ),
                      isThreeLine: true,
                      trailing: const Icon(Icons.chevron_right),
                      onTap: () => _select(p),
                    ),
                  );
                },
              ),
      ),
      const Text(
        'Hasta 200 resultados. Refina tu búsqueda para encontrar más.',
      ),
    ],
  );

  Widget _detail(BuildContext context, ProductView p) => ListView(
    key: const Key('inventory-detail-scroll'),
    children: [
      Align(
        alignment: Alignment.centerLeft,
        child: TextButton.icon(
          onPressed: () {
            _movementRequest++;
            setState(() {
              _selected = null;
              _movementLoading = false;
            });
            _load();
          },
          icon: const Icon(Icons.arrow_back),
          label: const Text('Volver al inventario'),
        ),
      ),
      Text(p.name, style: Theme.of(context).textTheme.titleLarge),
      const SizedBox(height: 12),
      Text('Identificador: ${p.id}'),
      Text(
        'Categoría: ${p.classification.isEmpty ? 'Sin categoría' : p.classification}',
      ),
      Text('Presentación: ${p.size.isEmpty ? 'Sin presentación' : p.size}'),
      Text('Código de barras: ${p.barcode.isEmpty ? 'Sin código' : p.barcode}'),
      Text('Precio de venta: ${money(p.priceCents)}'),
      Text('Existencia estimada: ${decimalText(p.estimatedQuantity)}'),
      const SizedBox(height: 16),
      Wrap(
        spacing: 8,
        children: [
          for (final action in [
            ('update', 'product.update', 'Editar producto'),
            ('prices', 'product.prices.update', 'Actualizar precios'),
            ('add', 'inventory.add', 'Agregar existencias'),
            ('reset', 'inventory.reset', 'Resetear existencias'),
            ('delete', 'product.delete', 'Eliminar producto'),
          ])
            if (widget.controller.hasPermission(action.$2))
              OutlinedButton(
                key: ValueKey('inventory-action-${action.$1}'),
                onPressed: widget.controller.canWriteInventory(action.$2)
                    ? () => _edit(action.$1)
                    : null,
                child: Text(action.$3),
              ),
        ],
      ),
      const SizedBox(height: 16),
      if (widget.controller.hasPermission('inventory.movements.read')) ...[
        Text('Cardex', style: Theme.of(context).textTheme.titleMedium),
        const Text(
          'Movimientos confirmados en el servidor. Las ventas locales pendientes no aparecen aquí.',
        ),
        Align(
          alignment: Alignment.centerLeft,
          child: OutlinedButton.icon(
            key: const Key('inventory-movements-button'),
            onPressed: widget.controller.online && !_movementLoading
                ? () => _loadMovements()
                : null,
            icon: const Icon(Icons.history),
            label: const Text('Consultar movimientos'),
          ),
        ),
        if (_movementLoading) const LinearProgressIndicator(),
        if (_movementError != null)
          Text(
            _movementError!,
            style: TextStyle(color: Theme.of(context).colorScheme.error),
          ),
        if (_page > 0 && _movements.isEmpty)
          const Text('No hay movimientos registrados.'),
        for (final row in _movements)
          Card(
            child: ListTile(
              title: Text('${row['type']} · ${row['change']}'),
              subtitle: Text(
                'Antes: ${row['before']} · Después: ${row['after']}\n${row['user']} · ${row['date'] ?? 'Sin fecha'}',
              ),
              isThreeLine: true,
            ),
          ),
        if (_more)
          TextButton(
            onPressed: widget.controller.online && !_movementLoading
                ? () => _loadMovements(append: true)
                : null,
            child: const Text('Ver más movimientos'),
          ),
      ],
    ],
  );
}
