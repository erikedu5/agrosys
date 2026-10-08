import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../core/app_controller.dart';
import '../../core/models.dart';
import '../../ui/web_theme.dart';
import '../sales/sale_screen.dart';
import '../sales/history_screen.dart';

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
      final customers = await repo.customers(_query.text);
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

  void _navigate(int section) {
    setState(() {
      _section = section;
      _query.clear();
    });
    _load();
  }

  @override
  Widget build(BuildContext context) {
    final c = widget.controller;
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
                index: _section == 2
                    ? 1
                    : _section == 3
                    ? 2
                    : 0,
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
                        Text(
                          _section == 0
                              ? 'Productos'
                              : _section == 1
                              ? 'Clientes'
                              : 'Tu dispositivo',
                          style: Theme.of(context).textTheme.headlineMedium,
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Última actualización: ${_date(c.syncInfo?.syncedAt)} · ${_section == 1 ? 'Adeudos estimados' : 'Existencias estimadas'}',
                        ),
                        const SizedBox(height: 20),
                        if (_section != 4) ...[
                          TextField(
                            controller: _query,
                            focusNode: _focus,
                            onChanged: _search,
                            onSubmitted: (_) => _load(),
                            decoration: InputDecoration(
                              prefixIcon: const Icon(Icons.search),
                              labelText: _section == 0
                                  ? 'Nombre, identificador o código de barras'
                                  : 'Nombre o identificador del cliente',
                              suffixIcon: IconButton(
                                tooltip: 'Limpiar búsqueda',
                                icon: const Icon(Icons.clear),
                                onPressed: () {
                                  _query.clear();
                                  _load();
                                },
                              ),
                            ),
                          ),
                          const SizedBox(height: 16),
                          if (_searchError != null) Text(_searchError!),
                        ],
                        Expanded(
                          child: _section == 4
                              ? _account(context)
                              : _loading
                              ? const Center(child: CircularProgressIndicator())
                              : _section == 0
                              ? _productList(context)
                              : _customerList(context),
                        ),
                        if (_section != 4)
                          const Padding(
                            padding: EdgeInsets.only(top: 8),
                            child: Text(
                              'Hasta 200 resultados. Refina tu búsqueda para encontrar más.',
                            ),
                          ),
                      ],
                    ),
                  ),
                  SaleScreen(controller: c, active: _section == 2),
                  HistoryScreen(controller: c),
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
              (2, 'Venta', Icons.point_of_sale),
              (1, 'Clientes', Icons.people_outline),
              (3, 'Historial', Icons.receipt_long_outlined),
              (4, 'Cuenta', Icons.person_outline),
            ])
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

  Widget _productList(BuildContext context) {
    if (MediaQuery.sizeOf(context).width >= 900 && _products.isNotEmpty) {
      return _table(context, products: true);
    }
    if (_products.isEmpty) {
      return const Center(
        child: Text('No hay productos activos para esta búsqueda.'),
      );
    }
    return ListView.separated(
      itemCount: _products.length,
      separatorBuilder: (_, _) => const SizedBox(height: 8),
      itemBuilder: (context, index) {
        final p = _products[index];
        return Card(
          child: ListTile(
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 16,
              vertical: 8,
            ),
            title: Text(
              p.name,
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            subtitle: Text(
              'ID ${p.id} · ${p.size}\n${p.barcode.isEmpty ? 'Sin código de barras' : p.barcode}\nExistencia estimada: ${decimalText(p.estimatedQuantity)}',
            ),
            isThreeLine: true,
            trailing: Text(
              money(p.priceCents),
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w700,
                color: Theme.of(context).colorScheme.primary,
              ),
            ),
            onTap: () => showDialog<void>(
              context: context,
              builder: (context) => AlertDialog(
                title: Text(p.name),
                content: Text(
                  'Precio: ${money(p.priceCents)}\nExistencia estimada: ${decimalText(p.estimatedQuantity)}\nCódigo: ${p.barcode}\nActualizado: ${_date(widget.controller.syncInfo?.syncedAt)}',
                ),
                actions: [
                  TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('Cerrar'),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  Widget _customerList(BuildContext context) {
    if (MediaQuery.sizeOf(context).width >= 900 && _customers.isNotEmpty) {
      return _table(context, products: false);
    }
    if (_customers.isEmpty) {
      return const Center(
        child: Text('No hay clientes activos para esta búsqueda.'),
      );
    }
    return ListView.separated(
      itemCount: _customers.length,
      separatorBuilder: (_, _) => const SizedBox(height: 8),
      itemBuilder: (context, index) {
        final c = _customers[index];
        return Card(
          child: ListTile(
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 16,
              vertical: 8,
            ),
            title: Text(
              c.name,
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            subtitle: Text(
              'ID ${c.id} · Descuento ${decimalText(c.discountBasisPoints)}%',
            ),
            trailing: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                const Text('Adeudo estimado', style: TextStyle(fontSize: 12)),
                Text(
                  money(c.balanceCents),
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _table(
    BuildContext context, {
    required bool products,
  }) => LayoutBuilder(
    builder: (context, constraints) => Card(
      child: SingleChildScrollView(
        child: SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          child: ConstrainedBox(
            constraints: BoxConstraints(minWidth: constraints.maxWidth),
            child: DataTable(
              headingRowColor: const WidgetStatePropertyAll(WebTheme.canvas),
              columns: products
                  ? const [
                      DataColumn(label: Text('Producto')),
                      DataColumn(label: Text('Tamaño')),
                      DataColumn(label: Text('Precio')),
                      DataColumn(label: Text('Existencia estimada')),
                      DataColumn(label: Text('Código')),
                    ]
                  : const [
                      DataColumn(label: Text('Cliente')),
                      DataColumn(label: Text('ID')),
                      DataColumn(label: Text('Descuento')),
                      DataColumn(label: Text('Adeudo estimado')),
                    ],
              rows: products
                  ? [
                      for (final p in _products)
                        DataRow(
                          cells: [
                            DataCell(Text(p.name)),
                            DataCell(Text(p.size)),
                            DataCell(Text(money(p.priceCents))),
                            DataCell(Text(decimalText(p.estimatedQuantity))),
                            DataCell(Text(p.barcode)),
                          ],
                        ),
                    ]
                  : [
                      for (final c in _customers)
                        DataRow(
                          cells: [
                            DataCell(Text(c.name)),
                            DataCell(Text(c.id)),
                            DataCell(
                              Text('${decimalText(c.discountBasisPoints)}%'),
                            ),
                            DataCell(Text(money(c.balanceCents))),
                          ],
                        ),
                    ],
            ),
          ),
        ),
      ),
    ),
  );

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
