import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../core/app_controller.dart';
import '../../core/models.dart';
import '../../ui/components.dart';
import '../../ui/web_theme.dart';
import '../sales/sale_screen.dart';
import '../sales/history_screen.dart';
import '../inventory/inventory_screen.dart';
import '../inventory/stock_workflow_screen.dart';

class CatalogScreen extends StatefulWidget {
  final AppController controller;
  const CatalogScreen({super.key, required this.controller});
  @override
  State<CatalogScreen> createState() => _CatalogScreenState();
}

class _CatalogScreenState extends State<CatalogScreen> {
  int _section = 0, _searchRequest = 0;
  bool _menuOpen = false;
  final _query = TextEditingController();
  final _focus = FocusNode();
  Timer? _debounce;
  List<ProductView> _products = [];
  List<CustomerView> _customers = [];
  bool _loading = true;
  String? _searchError;
  int _generation = -1;
  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void didUpdateWidget(CatalogScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (!_allowed(_section)) {
      _section = 0;
      _query.clear();
    }
    if (_generation != widget.controller.generation) _load();
  }

  @override
  void dispose() {
    _query.dispose();
    _focus.dispose();
    _debounce?.cancel();
    super.dispose();
  }

  Future<void> _load() async {
    final request = ++_searchRequest;
    _generation = widget.controller.generation;
    try {
      final repo = widget.controller.repository!;
      final products = await repo.products(_query.text);
      final customers = widget.controller.canReadCustomers
          ? await repo.customers(_query.text)
          : <CustomerView>[];
      if (!mounted || request != _searchRequest) return;
      setState(() {
        _products = products;
        _customers = customers;
        _loading = false;
        _searchError = null;
      });
    } catch (_) {
      if (mounted && request == _searchRequest) {
        setState(() {
          _loading = false;
          _searchError =
              'No se pudo leer el catálogo local. Los datos se conservaron.';
        });
      }
    }
  }

  void _search(String _) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 150), _load);
  }

  bool _allowed(int section) => switch (section) {
    1 => widget.controller.canReadCustomers,
    2 => widget.controller.canSell,
    3 => widget.controller.canReadHistory,
    5 => widget.controller.canReadInventory,
    6 => widget.controller.hasPermission('purchase.read'),
    7 => widget.controller.hasPermission('transfer.read'),
    _ => true,
  };

  void _navigate(int section) {
    if (!_allowed(section)) return;
    setState(() {
      _section = section;
      _query.clear();
    });
    _load();
  }

  Future<void> _changeBranch() async {
    final c = widget.controller;
    await c.loadBranches();
    if (!mounted) return;
    if (c.error != null) {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(c.error!)));
      return;
    }
    final selected = await showDialog<Branch>(
      context: context,
      builder: (context) => SimpleDialog(
        title: const Text('Cambiar sucursal'),
        children: [
          const Padding(
            padding: EdgeInsets.symmetric(horizontal: 24, vertical: 8),
            child: Text(
              'Las ventas y pendientes se conservan en su sucursal original.',
            ),
          ),
          for (final branch in c.branches)
            SimpleDialogOption(
              onPressed: branch.id == c.session!.branch.id
                  ? null
                  : () => Navigator.pop(context, branch),
              child: Row(
                children: [
                  Icon(
                    branch.id == c.session!.branch.id
                        ? Icons.check
                        : Icons.store_outlined,
                  ),
                  const SizedBox(width: 12),
                  Expanded(child: Text(branch.name)),
                ],
              ),
            ),
          if (c.branches.length < 2)
            const Padding(
              padding: EdgeInsets.all(24),
              child: Text('No tienes otras sucursales autorizadas.'),
            ),
        ],
      ),
    );
    if (selected == null || !mounted) return;
    await c.changeBranch(selected);
    if (mounted && c.deviceSessionConfirmationRequired) {
      final confirmed = await showDialog<bool>(
        context: context,
        builder: (dialogContext) => AlertDialog(
          title: const Text('Cerrar las demás sesiones'),
          content: const Text(
            'Esta sucursal alcanzó su número de dispositivos. Se cerrarán todas las demás sesiones web y POS de tu usuario. Las ventas pendientes se conservan; sincronízalas antes si es posible. Los equipos sin conexión detectarán el cierre al reconectarse.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext, false),
              child: const Text('Cancelar'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(dialogContext, true),
              child: const Text('Cerrar y entrar'),
            ),
          ],
        ),
      );
      if (confirmed == true) {
        await c.changeBranch(selected, closeOtherSessions: true);
      }
    }
    if (mounted && c.error != null) {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(c.error!)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final c = widget.controller;
    if (!_allowed(_section)) _section = 0;
    final wide = MediaQuery.sizeOf(context).width >= 840;
    return CallbackShortcuts(
      bindings: {
        const SingleActivator(LogicalKeyboardKey.keyF, control: true): () =>
            _focus.requestFocus(),
        const SingleActivator(LogicalKeyboardKey.keyF, meta: true): () =>
            _focus.requestFocus(),
      },
      child: Scaffold(
        appBar: AppBar(
          leading: IconButton(
            tooltip: 'Menú',
            onPressed: () {
              if (wide) {
                setState(() => _menuOpen = !_menuOpen);
              } else {
                _scaffoldKey.currentState?.openDrawer();
              }
            },
            icon: const Icon(Icons.menu),
          ),
          title: const BrandLogo(width: 95),
          actions: [
            IconButton(
              tooltip: 'Cambiar sucursal',
              onPressed: c.busy || c.syncBusy ? null : _changeBranch,
              icon: const Icon(Icons.store_outlined),
            ),
            if (wide)
              Padding(
                padding: const EdgeInsets.only(right: 16),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      c.session!.userName,
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                    Text(
                      c.session!.branch.name,
                      style: const TextStyle(
                        fontSize: 12,
                        color: WebTheme.green,
                      ),
                    ),
                  ],
                ),
              ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 8),
              child: StatusPill(
                c.syncBusy
                    ? 'Sincronizando'
                    : c.online
                    ? 'En línea'
                    : 'Sin conexión',
                warning: !c.online,
              ),
            ),
            IconButton(
              tooltip: 'Sincronizar ventas y catálogo',
              onPressed: c.busy || c.syncBusy ? null : c.synchronize,
              icon: const Icon(Icons.sync),
            ),
          ],
        ),
        key: _scaffoldKey,
        drawer: wide
            ? null
            : Drawer(
                child: _menu(
                  context,
                  close: () => _scaffoldKey.currentState?.closeDrawer(),
                ),
              ),
        body: Row(
          children: [
            if (wide && _menuOpen) SizedBox(width: 280, child: _menu(context)),
            if (wide && _menuOpen) const VerticalDivider(width: 1),
            Expanded(
              child: IndexedStack(
                index: switch (_section) {
                  2 => 1,
                  3 => 2,
                  5 => 3,
                  6 => 4,
                  7 => 5,
                  _ => 0,
                },
                children: [
                  Padding(
                    padding: EdgeInsets.all(wide ? 28 : 16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        if (c.busy) ...[
                          const LinearProgressIndicator(),
                          Padding(
                            padding: const EdgeInsets.symmetric(vertical: 8),
                            child: Text(
                              'Actualizando · ${c.downloadedPages} páginas guardadas',
                            ),
                          ),
                        ],
                        if (c.error != null)
                          Padding(
                            padding: const EdgeInsets.only(bottom: 12),
                            child: Text(
                              c.error!,
                              style: TextStyle(
                                color: Theme.of(context).colorScheme.error,
                              ),
                            ),
                          ),
                        Expanded(
                          child: _section == 4
                              ? Column(
                                  crossAxisAlignment:
                                      CrossAxisAlignment.stretch,
                                  children: [
                                    ..._top(c),
                                    Expanded(child: _account(context)),
                                  ],
                                )
                              : _catalogList(context),
                        ),
                      ],
                    ),
                  ),
                  if (c.canSell)
                    SaleScreen(
                      key: ValueKey('sale-${c.session!.contextId}'),
                      controller: c,
                      active: _section == 2,
                    )
                  else
                    const SizedBox.shrink(),
                  if (c.canReadHistory)
                    HistoryScreen(
                      key: ValueKey('history-${c.session!.contextId}'),
                      controller: c,
                    )
                  else
                    const SizedBox.shrink(),
                  if (c.canReadInventory && _section == 5)
                    InventoryScreen(
                      key: ValueKey('inventory-${c.session!.contextId}'),
                      controller: c,
                    )
                  else
                    const SizedBox.shrink(),
                  if (c.hasPermission('purchase.read') && _section == 6)
                    StockWorkflowScreen(
                      key: ValueKey('purchases-${c.session!.contextId}'),
                      controller: c,
                      kind: 'purchases',
                    )
                  else
                    const SizedBox.shrink(),
                  if (c.hasPermission('transfer.read') && _section == 7)
                    StockWorkflowScreen(
                      key: ValueKey('transfers-${c.session!.contextId}'),
                      controller: c,
                      kind: 'transfers',
                    )
                  else
                    const SizedBox.shrink(),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  final _scaffoldKey = GlobalKey<ScaffoldState>();
  Widget _menu(BuildContext context, {VoidCallback? close}) => SafeArea(
    child: ListView(
      padding: const EdgeInsets.all(16),
      children: [
        const Padding(
          padding: EdgeInsets.symmetric(vertical: 12),
          child: Text(
            'Menú principal',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
          ),
        ),
        GridView.count(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisCount: 2,
          mainAxisSpacing: 12,
          crossAxisSpacing: 12,
          childAspectRatio: 1.05,
          children: [
            for (final item in const [
              (0, 'Productos', Icons.inventory_2_outlined),
              (5, 'Inventario', Icons.warehouse_outlined),
              (6, 'Compras', Icons.shopping_bag_outlined),
              (7, 'Transferencias', Icons.swap_horiz),
              (2, 'Venta', Icons.point_of_sale),
              (1, 'Clientes', Icons.people_outline),
              (3, 'Historial', Icons.receipt_long_outlined),
              (4, 'Cuenta', Icons.person_outline),
            ])
              if (_allowed(item.$1))
                OutlinedButton(
                  style: OutlinedButton.styleFrom(
                    backgroundColor: _section == item.$1
                        ? const Color(0xffeff6ff)
                        : Colors.white,
                    side: BorderSide(
                      color: _section == item.$1
                          ? WebTheme.blue
                          : WebTheme.border,
                      width: 2,
                    ),
                  ),
                  onPressed: () {
                    _navigate(item.$1);
                    close?.call();
                  },
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(item.$3, color: WebTheme.blue, size: 28),
                      const SizedBox(height: 10),
                      Text(
                        item.$2,
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),
          ],
        ),
        const SizedBox(height: 20),
        const Divider(),
        Text(
          widget.controller.session!.userName,
          style: const TextStyle(fontWeight: FontWeight.w600),
        ),
        Text(
          widget.controller.session!.branch.name,
          style: const TextStyle(color: WebTheme.green),
        ),
        const SizedBox(height: 16),
        TextButton.icon(
          onPressed: widget.controller.busy ? null : widget.controller.signOut,
          icon: const Icon(Icons.logout, color: Colors.red),
          label: const Text(
            'Cerrar sesión',
            style: TextStyle(color: Colors.red),
          ),
        ),
      ],
    ),
  );

  Widget _productCard(BuildContext context, ProductView p) => RecordCard(
    key: ValueKey('catalog-product-${p.id}'),
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
    onTap: () => showDialog<void>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(p.name),
        content: Text(
          'Precio público: ${money(p.priceCents)}\nPreferencial 1: ${money(p.preferential1Cents)}\nPreferencial 2: ${money(p.preferential2Cents)}\nExistencia estimada: ${decimalText(p.estimatedQuantity)}\nCódigo: ${p.barcode}\nActualizado: ${_date(widget.controller.syncInfo?.syncedAt)}',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cerrar'),
          ),
        ],
      ),
    ),
    child: AmountLine(money(p.priceCents), label: 'Precio público'),
  );

  Widget _customerCard(CustomerView c) {
    final blocked = c.creditRestriction();
    return RecordCard(
      key: ValueKey('catalog-customer-${c.id}'),
      eyebrow: '#${c.id}',
      title: c.name,
      badges: [
        if (c.requiresInvoice) const UiBadge('Factura', tone: Tone.blue),
        if (blocked != null) const UiBadge('Solo contado', tone: Tone.red),
      ],
      fields: [
        RecordField('Precio asignado', priceTierLabel(c.priceTier)),
        RecordField(
          'Límite de crédito',
          c.creditLimitCents == null
              ? 'Sin límite'
              : money(c.creditLimitCents!),
        ),
        if (blocked != null) RecordField('Crédito', blocked, wide: true),
      ],
      child: AmountLine(money(c.balanceCents), label: 'Adeudo estimado'),
    );
  }

  /// Título y fecha de actualización; en los listados se desplazan con las tarjetas.
  List<Widget> _top(AppController c) => [
    PageHeader(
      title: switch (_section) {
        0 => 'Productos',
        1 => 'Clientes',
        _ => 'Tu dispositivo',
      },
      subtitle: switch (_section) {
        0 => 'Precios y existencias del catálogo guardado',
        1 => 'Precio asignado, crédito y adeudos',
        _ => 'Sesión, sucursal y autorización offline',
      },
    ),
    const SizedBox(height: 4),
    Muted(
      'Actualizado ${_date(c.syncInfo?.syncedAt)} · ${_section == 1 ? 'Adeudos estimados' : 'Existencias estimadas'}',
    ),
    const SizedBox(height: 16),
  ];

  Widget _catalogList(BuildContext context) {
    final products = _section == 0;
    final count = products ? _products.length : _customers.length;
    final query = _query.text.trim();
    return RecordGrid(
      itemCount: _loading ? 0 : count,
      itemBuilder: (context, i) => products
          ? _productCard(context, _products[i])
          : _customerCard(_customers[i]),
      header: [
        ..._top(widget.controller),
        UiSearchField(
          controller: _query,
          focusNode: _focus,
          hint: products ? 'Buscar producto o código' : 'Buscar cliente',
          onChanged: _search,
          onSubmitted: (_) => _load(),
          onClear: () {
            _query.clear();
            _load();
          },
        ),
        const SizedBox(height: 10),
        if (_searchError != null)
          Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: UiAlert(_searchError!, tone: Tone.red),
          ),
        if (_loading)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 32),
            child: Center(child: CircularProgressIndicator()),
          )
        else if (count == 0)
          EmptyState(
            title: query.isNotEmpty
                ? 'No encontramos “$query”'
                : products
                ? 'No hay productos activos para esta búsqueda.'
                : 'No hay clientes activos para esta búsqueda.',
            text: 'Revisa la escritura o sincroniza el catálogo.',
          )
        else
          Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: Muted(
              products
                  ? (count == 1 ? '1 producto' : '$count productos')
                  : (count == 1 ? '1 cliente' : '$count clientes'),
            ),
          ),
      ],
      footer: const [
        Muted('Hasta 200 resultados. Refina tu búsqueda para encontrar más.'),
      ],
    );
  }

  Widget _account(BuildContext context) {
    final c = widget.controller;
    return ListView(
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  c.session!.userName,
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                const SizedBox(height: 12),
                Text('Sucursal: ${c.session!.branch.name}'),
                const SizedBox(height: 8),
                Text(
                  'Autorización offline hasta: ${_date(c.session!.leaseExpiresAt)}',
                ),
                const SizedBox(height: 8),
                Text('Servidor: ${c.session!.apiUrl}'),
                const SizedBox(height: 20),
                const Text(
                  'Consulta y ventas guardadas en este dispositivo. La sincronización conserva pendientes hasta recibir confirmación central. Revisa errores y conflictos en Historial.',
                ),
                const SizedBox(height: 20),
                OutlinedButton.icon(
                  onPressed: c.busy ? null : c.renew,
                  icon: const Icon(Icons.verified_user_outlined),
                  label: const Text('Renovar autorización online'),
                ),
                const SizedBox(height: 8),
                OutlinedButton.icon(
                  onPressed: c.busy || c.syncBusy
                      ? null
                      : () => c.synchronize(full: true),
                  icon: const Icon(Icons.download),
                  label: const Text('Descargar catálogo completo'),
                ),
                const SizedBox(height: 20),
                TextButton.icon(
                  onPressed: c.busy ? null : c.signOut,
                  icon: const Icon(Icons.logout),
                  label: const Text('Cerrar sesión'),
                ),
                const Text(
                  'Cerrar sesión bloquea este catálogo y conserva los datos locales.',
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  String _date(DateTime? date) {
    if (date == null) return 'Sin descarga';
    final local = date.toLocal();
    String two(int n) => n.toString().padLeft(2, '0');
    return '${two(local.day)}/${two(local.month)}/${local.year} ${two(local.hour)}:${two(local.minute)}';
  }
}
