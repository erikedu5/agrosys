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
                width: _menuWidth,
                child: _menu(
                  context,
                  close: () => _scaffoldKey.currentState?.closeDrawer(),
                ),
              ),
        body: Row(
          children: [
            if (wide && _menuOpen)
              SizedBox(width: _menuWidth, child: _menu(context)),
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

  /// Mismo orden y agrupación que el menú lateral de la app web (AppLayout.vue):
  /// venta, inventario y compras, transferencias, clientes; después Usuario.
  static const _navigation = [
    (0, 'Productos', Icons.sell_outlined),
    (2, 'Venta', Icons.attach_money),
    (3, 'Historial', Icons.receipt_long_outlined),
    (5, 'Inventario', Icons.inventory_2_outlined),
    (6, 'Compra a proveedores', Icons.shopping_bag_outlined),
    (7, 'Transferencias', Icons.swap_horiz),
    (1, 'Clientes', Icons.people_outline),
  ];
  static const _menuWidth = 320.0;

  Widget _menu(BuildContext context, {VoidCallback? close}) {
    final c = widget.controller;
    return SafeArea(
      child: ListView(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _menuTitle('Navegación'),
                _menuGrid(3, [
                  for (final item in _navigation)
                    if (_allowed(item.$1)) _menuTile(item, close),
                ]),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: const BoxDecoration(
              color: Color(0xfff9fafb),
              border: Border(top: BorderSide(color: WebTheme.border)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Text(
                  'Usuario',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w600,
                    color: WebTheme.ink,
                  ),
                ),
                const SizedBox(height: 16),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: WebTheme.border),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        c.session!.userName,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          const Icon(
                            Icons.location_on_outlined,
                            size: 14,
                            color: WebTheme.green,
                          ),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              c.session!.branch.name,
                              style: const TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w500,
                                color: WebTheme.green,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                _menuGrid(2, [
                  _menuTile(
                    (4, 'Cuenta', Icons.person_outline),
                    close,
                    background: Colors.white,
                  ),
                ]),
                const SizedBox(height: 16),
                _menuAction(
                  'Cambiar sucursal',
                  Icons.swap_horiz,
                  foreground: const Color(0xff15803d),
                  background: const Color(0xfff0fdf4),
                  border: const Color(0xffbbf7d0),
                  onPressed: c.busy || c.syncBusy
                      ? null
                      : () {
                          close?.call();
                          _changeBranch();
                        },
                ),
                const SizedBox(height: 16),
                _menuAction(
                  'Cerrar sesión',
                  Icons.logout,
                  foreground: const Color(0xffb91c1c),
                  background: const Color(0xfffef2f2),
                  border: const Color(0xfffecaca),
                  onPressed: c.busy ? null : c.signOut,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _menuTitle(String text) => Container(
    margin: const EdgeInsets.only(bottom: 16),
    padding: const EdgeInsets.only(bottom: 8),
    decoration: const BoxDecoration(
      border: Border(bottom: BorderSide(color: WebTheme.border)),
    ),
    child: Text(
      text,
      style: const TextStyle(
        fontSize: 18,
        fontWeight: FontWeight.w600,
        color: WebTheme.ink,
      ),
    ),
  );

  Widget _menuGrid(int columns, List<Widget> children) => GridView.count(
    shrinkWrap: true,
    physics: const NeverScrollableScrollPhysics(),
    crossAxisCount: columns,
    mainAxisSpacing: 12,
    crossAxisSpacing: 12,
    childAspectRatio: columns == 3 ? 1 : 1.6,
    children: children,
  );

  /// Botón de la cuadrícula con los colores del menú web (azul si es la sección actual).
  Widget _menuTile(
    (int, String, IconData) item,
    VoidCallback? close, {
    Color background = const Color(0xfff9fafb),
  }) {
    final selected = _section == item.$1;
    final color = selected ? const Color(0xff1d4ed8) : const Color(0xff374151);
    return OutlinedButton(
      style: OutlinedButton.styleFrom(
        padding: const EdgeInsets.all(6),
        foregroundColor: color,
        backgroundColor: selected ? const Color(0xffdbeafe) : background,
        side: BorderSide(
          color: selected ? const Color(0xff3b82f6) : WebTheme.border,
          width: 2,
        ),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      ),
      onPressed: () {
        _navigate(item.$1);
        close?.call();
      },
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(item.$3, color: color, size: 24),
          const SizedBox(height: 8),
          // Ancho fijo para partir en palabras; si no cabe, se reduce en vez de cortarse.
          Flexible(
            child: FittedBox(
              fit: BoxFit.scaleDown,
              child: SizedBox(
                width: 84,
                child: Text(
                  item.$2,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 12,
                    height: 1.2,
                    fontWeight: FontWeight.w500,
                    color: color,
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _menuAction(
    String label,
    IconData icon, {
    required Color foreground,
    required Color background,
    required Color border,
    required VoidCallback? onPressed,
  }) => OutlinedButton.icon(
    style: OutlinedButton.styleFrom(
      minimumSize: const Size.fromHeight(48),
      foregroundColor: foreground,
      backgroundColor: background,
      side: BorderSide(color: border, width: 2),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
    ),
    onPressed: onPressed,
    icon: Icon(icon, size: 18),
    label: Text(
      label,
      style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500),
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
      RecordField('Distribuidor 1', money(p.preferential1Cents)),
      RecordField('Distribuidor 2', money(p.preferential2Cents)),
      RecordField('Distribuidor 3', money(p.preferential3Cents)),
      RecordField('Id', p.id),
      RecordField('Código de barras', p.barcode),
    ],
    onTap: () => showDialog<void>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(p.name),
        content: Text(
          'Precio público: ${money(p.priceCents)}\nDistribuidor 1: ${money(p.preferential1Cents)}\nDistribuidor 2: ${money(p.preferential2Cents)}\nDistribuidor 3: ${money(p.preferential3Cents)}\nExistencia estimada: ${decimalText(p.estimatedQuantity)}\nCódigo: ${p.barcode}\nActualizado: ${_date(widget.controller.syncInfo?.syncedAt)}',
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
