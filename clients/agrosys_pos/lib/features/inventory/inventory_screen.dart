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
import 'inventory_dose_editor.dart';
import '../../ui/components.dart';

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
  bool _retrying = false, _fromList = false;
  String? _stock;
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

  /// [product]: acción lanzada desde una tarjeta del listado; al terminar vuelve al listado.
  void _edit(String action, [ProductView? product]) {
    setState(() {
      if (product != null) {
        _selected = product;
        _fromList = true;
      }
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
      setState(() {
        _editorAction = null;
        if (_fromList) _selected = null;
        _fromList = false;
      });
      _load();
    }

    void saved(Map<String, dynamic> result) {
      setState(() {
        _notice =
            result['syncWarning'] as String? ??
            switch (_editorAction) {
              'delete' => 'Producto eliminado.',
              'reset' => 'Existencias reseteadas a cero.',
              'dose' =>
                'Se agregaron ${result['doseUnits']} medidas a "Medida ${_selected?.name ?? ''}".',
              _ => 'Cambios guardados.',
            };
        if (_editorAction == 'delete' || _fromList) _selected = null;
        _fromList = false;
        _editorAction = null;
        _movements = [];
        _page = 0;
        _more = false;
        _movementRequest++;
      });
      _load();
    }

    if (_editorAction == 'dose') {
      return InventoryDoseEditor(
        key: ValueKey('dose-$_editorProductId'),
        controller: c,
        productId: _editorProductId!,
        onCancel: cancel,
        onSaved: saved,
      );
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

  /// Encabezado, avisos y solicitudes pendientes; se desplazan junto con la vista, como en la web.
  List<Widget> _top(AppController c) => [
    PageHeader(
      title: 'Inventario',
      subtitle: 'Productos, precios y existencias',
      actions: [
        if (c.hasPermission('product.create') && _editorAction == null)
          FilledButton(
            key: const Key('inventory-create'),
            onPressed: c.canWriteInventory('product.create')
                ? () => _edit('create')
                : null,
            child: const Text('Nuevo producto'),
          ),
      ],
    ),
    const SizedBox(height: 4),
    Muted(
      'Sucursal ${c.session!.branch.name} · Actualizado ${_date(c.syncInfo?.syncedAt)} · Existencias estimadas',
    ),
    const SizedBox(height: 12),
    if (!c.online)
      const Padding(
        padding: EdgeInsets.only(bottom: 12),
        child: UiAlert(
          'Sin conexión. Consulta el catálogo guardado; el cardex requiere conexión.',
        ),
      ),
    if (_error != null)
      Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: UiAlert(_error!, tone: Tone.red),
      ),
    if (_notice != null)
      Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: UiAlert(_notice!, tone: Tone.blue),
      ),
    if (_editorAction == null &&
        _pending.any((row) => c.hasPermission(row.permission)))
      Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: SectionCard(
          title: 'Solicitudes por confirmar',
          children: [
            for (final row in _pending)
              if (c.hasPermission(row.permission))
                UiRow(
                  title: 'Solicitud de inventario por confirmar',
                  subtitle: switch (row.permission) {
                    'inventory.add' => 'Entrada de existencias',
                    'inventory.dose' => 'Dosificación de producto',
                    'inventory.reset' =>
                      'Reseteo de existencias confirmado previamente',
                    'product.delete' =>
                      'Eliminación de producto confirmada previamente',
                    _ => 'Cambios de producto',
                  },
                  trailing: TextButton(
                    onPressed: !_retrying && c.canWriteInventory(row.permission)
                        ? () => _retry(row)
                        : null,
                    child: const Text('Reintentar solicitud'),
                  ),
                ),
          ],
        ),
      ),
  ];

  @override
  Widget build(BuildContext context) {
    final c = widget.controller;
    if (!c.canReadInventory) {
      return const Center(child: Text('Inventario no autorizado.'));
    }
    return Padding(
      padding: const EdgeInsets.all(16),
      child: _editorAction != null
          // Los editores tienen su propio desplazamiento: sólo el título queda arriba.
          ? Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const PageHeader(
                  title: 'Inventario',
                  subtitle: 'Productos, precios y existencias',
                ),
                const SizedBox(height: 12),
                Expanded(child: _editor(c)),
              ],
            )
          : _selected == null
          ? _list(context, _top(c))
          : _detail(context, _selected!, _top(c)),
    );
  }

  static const _stockFilters = <(String?, String, Color?)>[
    (null, 'Todas', null),
    ('normal', 'Normal', Palette.green600),
    ('baja', 'Baja', Palette.amber400),
    ('agotado', 'Agotado', Palette.red500),
  ];

  bool _matchesStock(ProductView p) => switch (_stock) {
    'normal' => p.estimatedQuantity > 500,
    'baja' => p.estimatedQuantity > 0 && p.estimatedQuantity <= 500,
    'agotado' => p.estimatedQuantity <= 0,
    _ => true,
  };

  // Acciones de la tarjeta (mismo orden que Inventario.vue): acción, permiso, texto, icono.
  static const _cardActions = [
    (
      'prices',
      'product.prices.update',
      'Actualizar precios',
      Icons.payments_outlined,
    ),
    ('update', 'product.update', 'Editar producto', Icons.edit_outlined),
    ('add', 'inventory.add', 'Agregar existencias', Icons.add_box_outlined),
    ('dose', 'inventory.dose', 'Dosificar', Icons.science_outlined),
    ('reset', 'inventory.reset', 'Resetear existencias', Icons.restart_alt),
    ('delete', 'product.delete', 'Eliminar producto', Icons.delete_outline),
  ];

  bool _canRun(String action, String permission, ProductView p) =>
      widget.controller.canWriteInventory(permission) &&
      // Dosificar requiere al menos una unidad en existencia.
      (action != 'dose' || p.estimatedQuantity >= 100);

  Widget _productCard(ProductView p) {
    final c = widget.controller;
    return RecordCard(
      key: ValueKey('inventory-product-${p.id}'),
      accent: stockAccent(p.estimatedQuantity),
      eyebrow: p.classification.isEmpty ? '#${p.id}' : p.classification,
      title: p.name,
      subtitle: p.size,
      badges: [
        UiBadge(
          stockBadgeText(p.estimatedQuantity, decimalText(p.estimatedQuantity)),
          tone: stockTone(p.estimatedQuantity),
        ),
      ],
      fields: [
        RecordField('Preferencial 1', money(p.preferential1Cents)),
        RecordField('Preferencial 2', money(p.preferential2Cents)),
        RecordField('Id', p.id),
        RecordField('Código de barras', p.barcode),
      ],
      actions: [
        if (c.hasPermission('inventory.movements.read'))
          IconAction(
            key: ValueKey('inventory-card-cardex-${p.id}'),
            icon: Icons.history,
            tooltip: 'Ver cardex',
            onPressed: () => _select(p),
          ),
        for (final (action, permission, label, icon) in _cardActions)
          if (c.hasPermission(permission))
            IconAction(
              key: ValueKey('inventory-card-$action-${p.id}'),
              icon: icon,
              tooltip: label,
              danger: action == 'delete',
              onPressed: _canRun(action, permission, p)
                  ? () => _edit(action, p)
                  : null,
            ),
      ],
      onTap: () => _select(p),
      child: AmountLine(money(p.priceCents), label: 'Precio público'),
    );
  }

  Widget _list(BuildContext context, List<Widget> top) {
    final products = _products.where(_matchesStock).toList();
    final filtered =
        _search.text.trim().isNotEmpty || _category != null || _stock != null;
    return RecordGrid(
      scrollKey: const Key('inventory-list-scroll'),
      itemCount: products.length,
      itemBuilder: (context, i) => _productCard(products[i]),
      header: [
        ...top,
        UiSearchField(
          key: const Key('inventory-search'),
          controller: _search,
          hint: 'Buscar producto, identificador o código',
          onChanged: (_) {
            _debounce?.cancel();
            _debounce = Timer(const Duration(milliseconds: 150), _load);
          },
          onSubmitted: (_) {
            _debounce?.cancel();
            _load();
          },
          onClear: () {
            _debounce?.cancel();
            _search.clear();
            _load();
          },
        ),
        const SizedBox(height: 10),
        FilterChips<String?>(
          options: _stockFilters,
          selected: _stock,
          onSelected: (value) => setState(() => _stock = value),
        ),
        if (_categories.isNotEmpty) ...[
          const SizedBox(height: 8),
          FilterChips<String?>(
            options: [
              (null, 'Todas las categorías', null),
              for (final category in _categories) (category, category, null),
            ],
            selected: _category,
            onSelected: (value) {
              setState(() => _category = value);
              _load();
            },
          ),
        ],
        const SizedBox(height: 10),
        if (_loading) const LinearProgressIndicator(),
        if (products.isNotEmpty)
          Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: Muted(
              products.length == 1
                  ? '1 producto'
                  : '${products.length} productos',
            ),
          ),
        if (products.isEmpty && !_loading)
          EmptyState(
            title: filtered
                ? 'Ningún producto coincide con los filtros'
                : 'No hay productos activos',
            text: filtered
                ? 'Cambia la búsqueda, la categoría o la existencia.'
                : 'Crea un producto nuevo para verlo aquí.',
            child: _error == null
                ? null
                : TextButton(onPressed: _load, child: const Text('Reintentar')),
          ),
      ],
      footer: const [
        Muted('Hasta 200 resultados. Refina tu búsqueda para encontrar más.'),
      ],
    );
  }

  Widget _detail(BuildContext context, ProductView p, List<Widget> top) {
    final c = widget.controller;
    return ListView(
      key: const Key('inventory-detail-scroll'),
      children: [
        ...top,
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
        const SizedBox(height: 4),
        RecordCard(
          key: const Key('inventory-detail'),
          accent: stockAccent(p.estimatedQuantity),
          eyebrow: '#${p.id}',
          title: p.name,
          subtitle: p.size,
          badges: [
            UiBadge(
              stockBadgeText(
                p.estimatedQuantity,
                decimalText(p.estimatedQuantity),
              ),
              tone: stockTone(p.estimatedQuantity),
            ),
          ],
          fields: [
            RecordField(
              'Categoría',
              p.classification.isEmpty ? 'Sin categoría' : p.classification,
            ),
            RecordField(
              'Existencia estimada',
              decimalText(p.estimatedQuantity),
            ),
            RecordField('Preferencial 1', money(p.preferential1Cents)),
            RecordField('Preferencial 2', money(p.preferential2Cents)),
            RecordField(
              'Presentación',
              p.size.isEmpty ? 'Sin presentación' : p.size,
            ),
            RecordField(
              'Código de barras',
              p.barcode.isEmpty ? 'Sin código' : p.barcode,
            ),
          ],
          actions: [
            for (final (action, permission, label, icon) in _cardActions)
              if (c.hasPermission(permission))
                OutlinedButton.icon(
                  key: ValueKey('inventory-action-$action'),
                  style: action == 'delete'
                      ? OutlinedButton.styleFrom(
                          foregroundColor: Palette.red700,
                          side: const BorderSide(color: Color(0xfffecaca)),
                        )
                      : null,
                  onPressed: _canRun(action, permission, p)
                      ? () => _edit(action)
                      : null,
                  icon: Icon(icon, size: 20),
                  label: Text(label),
                ),
          ],
          child: AmountLine(money(p.priceCents), label: 'Precio público'),
        ),
        const SizedBox(height: 12),
        if (c.hasPermission('inventory.movements.read'))
          SectionCard(
            title: 'Cardex',
            children: [
              const Muted(
                'Movimientos confirmados en el servidor. Las ventas locales pendientes no aparecen aquí.',
              ),
              const SizedBox(height: 12),
              Align(
                alignment: Alignment.centerLeft,
                child: OutlinedButton.icon(
                  key: const Key('inventory-movements-button'),
                  onPressed: c.online && !_movementLoading
                      ? () => _loadMovements()
                      : null,
                  icon: const Icon(Icons.history),
                  label: const Text('Consultar movimientos'),
                ),
              ),
              const SizedBox(height: 8),
              if (_movementLoading) const LinearProgressIndicator(),
              if (_movementError != null)
                UiAlert(_movementError!, tone: Tone.red),
              if (_page > 0 && _movements.isEmpty)
                const Muted('No hay movimientos registrados.'),
              for (final row in _movements)
                UiRow(
                  title: '${row['type']} · ${row['change']}',
                  subtitle:
                      'Antes: ${row['before']} · Después: ${row['after']}\n${row['user']} · ${row['date'] ?? 'Sin fecha'}',
                ),
              if (_more)
                TextButton(
                  onPressed: c.online && !_movementLoading
                      ? () => _loadMovements(append: true)
                      : null,
                  child: const Text('Ver más movimientos'),
                ),
            ],
          ),
      ],
    );
  }
}
