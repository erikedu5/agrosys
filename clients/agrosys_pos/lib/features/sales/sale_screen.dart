import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:uuid/uuid.dart';

import '../../core/app_controller.dart';
import '../../core/models.dart';
import '../../ui/web_theme.dart';
import 'daily_sales.dart';
import '../receipts/receipt_dialog.dart';
import 'sale_domain.dart';

class SaleScreen extends StatefulWidget {
  final AppController controller;
  final bool active;
  const SaleScreen({super.key, required this.controller, this.active = true});
  @override
  State<SaleScreen> createState() => _SaleScreenState();
}

class _SaleScreenState extends State<SaleScreen> {
  final _search = TextEditingController(),
      _initial = TextEditingController(text: '0.00'),
      _received = TextEditingController();
  final _searchFocus = FocusNode();
  VoidCallback? _refreshCart, _refreshPayment;
  final _cart = <String, int>{};
  List<ProductView> _products = [];
  CustomerView? _customer;
  SaleQuote? _quote;
  String _type = 'Contado', _saleId = const Uuid().v4();
  String? _error;
  bool _loading = true, _closing = false, _stockAcknowledged = false;
  int _request = 0, _generation = -1;
  Timer? _debounce;
  SaleRequest? _closingRequest;
  bool get _editable =>
      !_closing && _closingRequest == null && !widget.controller.busy;
  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void didUpdateWidget(SaleScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (_generation != widget.controller.generation &&
        _closingRequest == null) {
      _load();
    }
  }

  @override
  void setState(VoidCallback fn) {
    super.setState(fn);
    _refreshCart?.call();
    _refreshPayment?.call();
  }

  @override
  void dispose() {
    _searchFocus.dispose();
    _search.dispose();
    _initial.dispose();
    _received.dispose();
    _debounce?.cancel();
    super.dispose();
  }

  Future<void> _load() async {
    final request = ++_request;
    _generation = widget.controller.generation;
    try {
      final repo = widget.controller.repository!;
      final products = await repo.products(_search.text);
      final defaultId = await repo.defaultCustomer();
      final selectedId = _customer?.id ?? defaultId;
      final customer = selectedId == null
          ? null
          : await repo.customerById(selectedId);
      SaleQuote? quote;
      if (customer != null && _cart.isNotEmpty) {
        quote = await widget.controller.sales!.quote(customer.id, [
          for (final e in _cart.entries) CartLine(e.key, e.value),
        ]);
      }
      if (!mounted || request != _request) return;
      setState(() {
        _products = products;
        _customer = customer;
        _quote = quote;
        _loading = false;
        _error = null;
      });
    } on SaleInputException catch (e) {
      if (mounted && request == _request) {
        setState(() {
          _quote = null;
          _loading = false;
          _error = e.message;
        });
      }
    } catch (_) {
      if (mounted && request == _request) {
        setState(() {
          _loading = false;
          _error =
              'No se pudo leer el carrito. Los datos guardados se conservaron.';
        });
      }
    }
  }

  void _searchChanged(String _) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 150), _load);
  }

  Future<void> _selectCustomer() async {
    final chosen = await showDialog<CustomerView>(
      context: context,
      builder: (_) => _CustomerPicker(controller: widget.controller),
    );
    if (!mounted || chosen == null) return;
    setState(() {
      _customer = chosen;
      _stockAcknowledged = false;
    });
    await _load();
  }

  Future<void> _add(ProductView p) async {
    if (!_editable) return;
    if (_cart.length >= 200 && !_cart.containsKey(p.id)) {
      setState(() => _error = 'Máximo 200 productos por venta.');
      return;
    }
    final quantity = (_cart[p.id] ?? 0) + 100;
    if (quantity > maxQuantityUnits) return;
    setState(() {
      _cart[p.id] = quantity;
      _stockAcknowledged = false;
    });
    await _load();
  }

  Future<void> _scan(String value) async {
    await _load();
    final exact = _products
        .where((p) => p.barcode == value.trim() || p.id == value.trim())
        .toList();
    if (exact.length == 1) await _add(exact.single);
  }

  Future<void> _quantity(ProductView p) async {
    final value = await showDialog<String>(
      context: context,
      builder: (_) => _QuantityDialog(
        name: p.name,
        initial: decimalText(_cart[p.id] ?? 100),
      ),
    );
    if (!mounted || value == null) return;
    try {
      final units = decimalUnits(value.trim());
      if (units <= 0 || units > maxQuantityUnits) {
        throw const SaleInputException('Cantidad fuera de rango.');
      }
      if (_cart.length >= 200 && !_cart.containsKey(p.id)) {
        throw const SaleInputException('Máximo 200 productos por venta.');
      }
      setState(() {
        _cart[p.id] = units;
        _stockAcknowledged = false;
      });
      await _load();
    } catch (_) {
      if (mounted) {
        setState(
          () => _error = 'Usa una cantidad positiva de hasta dos decimales, máximo 999999.99.',
        );
      }
    }
  }

  SalePayment? _payment() {
    if (_quote == null) return null;
    try {
      return SalePayment.calculate(
        type: _type,
        total: _quote!.totalCents,
        initial: _type == 'Credito' ? decimalUnits(_initial.text.trim()) : 0,
        received: decimalUnits(_received.text.trim()),
      );
    } catch (_) {
      return null;
    }
  }

  Future<void> _complete() async {
    if (_closing || widget.controller.busy || _quote == null) return;
    final payment = _payment();
    if (_closingRequest == null && payment == null) {
      setState(
        () => _error = 'Revisa el inicial y el efectivo recibido. Usa importes con hasta dos decimales.',
      );
      return;
    }
    if (_quote!.insufficientStock && !_stockAcknowledged) {
      setState(
        () => _error =
            'Confirma la advertencia de existencia estimada para continuar.',
      );
      return;
    }
    final request =
        _closingRequest ??
        SaleRequest(
          saleId: _saleId,
          customerId: _quote!.customer.id,
          type: _type,
          lines: [for (final e in _cart.entries) CartLine(e.key, e.value)],
          initialCents: _type == 'Credito'
              ? decimalUnits(_initial.text.trim())
              : 0,
          receivedCents: payment!.receivedCents,
          expectedPriceFingerprint: _quote!.priceFingerprint,
          acknowledgeStock: _stockAcknowledged,
        );
    setState(() {
      _closing = true;
      _closingRequest = request;
      _error = null;
    });
    final receipt = await widget.controller.completeSale(request);
    if (!mounted) return;
    if (receipt == null) {
      setState(() {
        _closing = false;
        _error = widget.controller.error ?? 'No se confirmó el cierre. Reintenta el mismo cierre o revisa el historial.';
      });
      return;
    }
    setState(() {
      _closing = false;
      _closingRequest = null;
      _saleId = const Uuid().v4();
      _cart.clear();
      _quote = null;
      _stockAcknowledged = false;
      _received.clear();
      _initial.text = '0.00';
    });
    await showReceipt(context, widget.controller, receipt);
    if (mounted) await _load();
  }

  Future<bool> _showExistingClosure() async {
    try {
      final sales = widget.controller.sales!;
      final saved = await sales.findSale(_saleId);
      if (saved == null) return false;
      final receipt = await sales.receipt(saved.id);
      if (!mounted) return true;
      await showReceipt(context, widget.controller, receipt);
      return true;
    } catch (_) {
      if (mounted) {
        setState(
          () => _error = 'No se pudo comprobar el cierre. Conserva el carrito y revisa el historial antes de cobrar otra vez.',
        );
      }
      return true;
    }
  }

  Future<void> _reviewClosure() async {
    if (_closing || await _showExistingClosure() || !mounted) return;
    setState(() {
      _closingRequest = null;
      _stockAcknowledged = false;
    });
    await _load();
  }

  Future<void> _discard() async {
    if (_closingRequest != null && await _showExistingClosure()) return;
    if (!mounted) return;
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Descartar borrador'),
        content: const Text(
          'Se vaciará este carrito. Las ventas guardadas se conservan en el historial.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Volver'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Descartar'),
          ),
        ],
      ),
    );
    if (confirm != true || !mounted) return;
    setState(() {
      _cart.clear();
      _quote = null;
      _closingRequest = null;
      _saleId = const Uuid().v4();
      _received.clear();
      _initial.text = '0.00';
      _stockAcknowledged = false;
    });
    await _load();
  }

  Future<void> _showCart() async {
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.white,
      builder: (sheetContext) => ListenableBuilder(
        listenable: widget.controller,
        builder: (context, _) => StatefulBuilder(
          builder: (context, refresh) {
            _refreshCart = () {
              if (context.mounted) refresh(() {});
            };
            return SizedBox(
              height: MediaQuery.sizeOf(context).height * .86,
              child: _cartPanel(
                context,
                onClose: () => Navigator.pop(sheetContext),
                onPay: () {
                  Navigator.pop(sheetContext);
                  _showPayment();
                },
              ),
            );
          },
        ),
      ),
    );
    _refreshCart = null;
  }

  Future<void> _showPayment() async {
    if (_quote == null || _closing || widget.controller.busy) return;
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      constraints: const BoxConstraints(maxWidth: 520),
      backgroundColor: Colors.white,
      builder: (sheetContext) => ListenableBuilder(
        listenable: widget.controller,
        builder: (context, _) => StatefulBuilder(
          builder: (context, refresh) {
            _refreshPayment = () {
              if (context.mounted) refresh(() {});
            };
            if (_quote == null || !mounted || !widget.controller.canSell) {
              return const Padding(
                padding: EdgeInsets.all(24),
                child: Text('Revisa la venta o revalida tu autorización.'),
              );
            }
            final payment = _payment();
            return Padding(
              padding: EdgeInsets.fromLTRB(
                20,
                20,
                20,
                20 + MediaQuery.viewInsetsOf(context).bottom,
              ),
              child: SingleChildScrollView(
                key: const Key('payment-scroll'),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            _type == 'Credito'
                                ? 'Venta a crédito'
                                : 'Cobro en efectivo',
                            style: Theme.of(context).textTheme.titleLarge,
                          ),
                        ),
                        IconButton(
                          tooltip: 'Volver a la venta',
                          onPressed: () => Navigator.pop(sheetContext),
                          icon: const Icon(Icons.close),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    Container(
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        color: WebTheme.canvas,
                        border: Border.all(color: WebTheme.border),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Column(
                        children: [
                          Text(
                            'Total a pagar · ${_customer?.name ?? ''}',
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 8),
                          Text(
                            money(_quote!.totalCents),
                            style: const TextStyle(
                              fontSize: 36,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),
                    if (_type == 'Credito') ...[
                      TextField(
                        controller: _initial,
                        enabled: _editable,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        onChanged: (_) {
                          setState(() {});
                          refresh(() {});
                        },
                        decoration: const InputDecoration(
                          labelText: 'Abono inicial (0 hasta el total)',
                          prefixText: '\$ ',
                        ),
                      ),
                      const SizedBox(height: 16),
                    ],
                    TextField(
                      controller: _received,
                      enabled: _editable,
                      autofocus: true,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                      ),
                      onChanged: (_) {
                        setState(() {});
                        refresh(() {});
                      },
                      decoration: const InputDecoration(
                        labelText: 'Efectivo recibido',
                        prefixText: '\$ ',
                      ),
                    ),
                    const SizedBox(height: 12),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        for (final amount
                            in {_quote!.totalCents, 10000, 20000, 50000}.where(
                              (v) =>
                                  v >=
                                  (_type == 'Credito' ? 0 : _quote!.totalCents),
                            ))
                          OutlinedButton(
                            onPressed: _editable
                                ? () {
                                    _received.text = decimalText(amount);
                                    setState(() {});
                                    refresh(() {});
                                  }
                                : null,
                            child: Text(
                              amount == _quote!.totalCents
                                  ? 'Exacto'
                                  : money(amount),
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: payment == null
                            ? WebTheme.canvas
                            : const Color(0xffdcfce7),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        children: [
                          const Expanded(child: Text('Cambio a entregar')),
                          Text(
                            payment == null ? '—' : money(payment.changeCents),
                            style: const TextStyle(
                              fontSize: 24,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ],
                      ),
                    ),
                    if (_type == 'Credito')
                      Padding(
                        padding: const EdgeInsets.only(top: 12),
                        child: Text(
                          'Saldo de esta venta: ${payment == null ? '—' : money(payment.balanceCents)}',
                        ),
                      ),
                    if (_error != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 12),
                        child: Text(
                          _error!,
                          style: const TextStyle(color: Colors.red),
                        ),
                      ),
                    const SizedBox(height: 16),
                    FilledButton.icon(
                      style: FilledButton.styleFrom(
                        backgroundColor: WebTheme.green,
                        minimumSize: const Size.fromHeight(60),
                      ),
                      onPressed:
                          !_closing &&
                              !widget.controller.busy &&
                              (_closingRequest != null || payment != null) &&
                              (_quote!.insufficientStock == false ||
                                  _stockAcknowledged)
                          ? () {
                              Navigator.pop(sheetContext);
                              _complete();
                            }
                          : null,
                      icon: const Icon(Icons.check),
                      label: Text(
                        _closingRequest != null
                            ? 'Reintentar el mismo cierre'
                            : 'Finalizar venta',
                      ),
                    ),
                    const SizedBox(height: 8),
                    const Text(
                      'La venta queda guardada en este dispositivo antes de sincronizar.',
                      textAlign: TextAlign.center,
                    ),
                  ],
                ),
              ),
            );
          },
        ),
      ),
    );
    _refreshPayment = null;
  }

  Future<void> _lastReceipt() async {
    try {
      final rows = await widget.controller.sales!.history(limit: 1);
      if (!mounted) return;
      if (rows.isEmpty) {
        setState(() => _error = 'Todavía no hay tickets guardados.');
        return;
      }
      final r = await widget.controller.sales!.receipt(rows.single.id);
      if (mounted && widget.controller.canConsult) {
        await showReceipt(context, widget.controller, r);
      }
    } catch (_) {
      if (mounted) {
        setState(() => _error = 'No se pudo abrir el último ticket.');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!widget.controller.canSell) {
      return const Center(
        child: Text(
          'Tu autorización permite consulta, pero no registrar ventas.',
        ),
      );
    }
    return CallbackShortcuts(
      bindings: {
        const SingleActivator(LogicalKeyboardKey.f3): () =>
            _searchFocus.requestFocus(),
        const SingleActivator(LogicalKeyboardKey.f4): _showPayment,
      },
      child: Focus(
        canRequestFocus: widget.active,
        child: LayoutBuilder(
          builder: (context, constraints) {
            final wide = constraints.maxWidth >= 900;
            return Column(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 10,
                  ),
                  decoration: const BoxDecoration(
                    color: Colors.white,
                    border: Border(bottom: BorderSide(color: WebTheme.border)),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Punto de venta',
                              style: Theme.of(context).textTheme.titleLarge,
                            ),
                            Text(
                              widget.controller.session!.branch.name,
                              style: const TextStyle(color: WebTheme.muted),
                            ),
                          ],
                        ),
                      ),
                      IconButton(
                        tooltip: 'Ventas del día',
                        onPressed: () =>
                            showDailySales(context, widget.controller),
                        icon: const Icon(Icons.bar_chart_outlined),
                      ),
                      IconButton(
                        tooltip: 'Reimprimir último ticket',
                        onPressed: _lastReceipt,
                        icon: const Icon(Icons.print_outlined),
                      ),
                    ],
                  ),
                ),
                Expanded(
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Expanded(child: _catalog(context)),
                      if (wide)
                        Container(
                          width: 400,
                          decoration: const BoxDecoration(
                            color: Colors.white,
                            border: Border(
                              left: BorderSide(color: WebTheme.border),
                            ),
                          ),
                          child: _cartPanel(context),
                        ),
                    ],
                  ),
                ),
                if (!wide)
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: const BoxDecoration(
                      color: Colors.white,
                      border: Border(top: BorderSide(color: WebTheme.border)),
                    ),
                    child: Row(
                      children: [
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: _showCart,
                            icon: const Icon(Icons.shopping_cart_outlined),
                            label: Text('Ver venta · ${_cart.length}'),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: FilledButton(
                            onPressed: _quote == null ? null : _showCart,
                            child: Text(
                              'Cobrar ${_quote == null ? '' : money(_quote!.totalCents)}',
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _catalog(BuildContext context) => CustomScrollView(
    key: const Key('sale-catalog-scroll'),
    slivers: [
      SliverPadding(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
        sliver: SliverToBoxAdapter(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              if (!widget.controller.online)
                Container(
                  padding: const EdgeInsets.all(14),
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    color: const Color(0xfffffbeb),
                    border: Border.all(color: const Color(0xfffde68a)),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Text(
                    'Catálogo local. Puedes seguir vendiendo; las existencias y adeudos son estimados.',
                    style: TextStyle(color: Color(0xff92400e)),
                  ),
                ),
              if (_error != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Text(
                    _error!,
                    style: const TextStyle(color: Colors.red),
                  ),
                ),
              TextField(
                controller: _search,
                focusNode: _searchFocus,
                enabled: _editable,
                onChanged: _searchChanged,
                onSubmitted: _scan,
                decoration: const InputDecoration(
                  hintText: 'Buscar producto o escanear código',
                  labelText: 'Buscar producto o escanear código',
                  prefixIcon: Icon(Icons.search),
                ),
              ),
              const SizedBox(height: 14),
              Text(
                '${_products.length} productos · Toca para agregar',
                style: const TextStyle(color: WebTheme.muted),
              ),
              const SizedBox(height: 12),
              if (_loading) const LinearProgressIndicator(),
            ],
          ),
        ),
      ),
      SliverPadding(
        padding: const EdgeInsets.all(16),
        sliver: SliverLayoutBuilder(
          builder: (context, constraints) {
            final columns = (constraints.crossAxisExtent / 200).floor().clamp(
              2,
              6,
            );
            return SliverGrid(
              delegate: SliverChildBuilderDelegate((context, i) {
                final p = _products[i],
                    selected = _cart.containsKey(_products[i].id);
                return Tooltip(
                  message: 'Agregar ${p.name}',
                  child: Material(
                    color: selected ? const Color(0xffeff6ff) : Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                      side: BorderSide(
                        color: selected ? WebTheme.blue : WebTheme.border,
                        width: selected ? 2 : 1,
                      ),
                    ),
                    clipBehavior: Clip.antiAlias,
                    child: InkWell(
                      onTap: _editable ? () => _add(p) : null,
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    p.size,
                                    style: const TextStyle(
                                      color: WebTheme.muted,
                                      fontSize: 12,
                                    ),
                                  ),
                                ),
                                if (selected)
                                  Container(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 8,
                                      vertical: 3,
                                    ),
                                    decoration: BoxDecoration(
                                      color: WebTheme.blue,
                                      borderRadius: BorderRadius.circular(16),
                                    ),
                                    child: Text(
                                      decimalText(_cart[p.id]!),
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                                  ),
                              ],
                            ),
                            const SizedBox(height: 10),
                            Text(
                              p.name,
                              maxLines: 3,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontSize: 17,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const Spacer(),
                            Text(
                              money(p.priceCents),
                              style: const TextStyle(
                                fontSize: 22,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                            const SizedBox(height: 6),
                            Text(
                              '${decimalText(p.estimatedQuantity)} en exist. estimada',
                              maxLines: 2,
                              style: TextStyle(
                                fontSize: 12,
                                color: p.estimatedQuantity <= 500
                                    ? const Color(0xff92400e)
                                    : WebTheme.green,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                );
              }, childCount: _products.length),
              gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: columns,
                mainAxisExtent: 240,
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
              ),
            );
          },
        ),
      ),
      if (_products.isEmpty && !_loading)
        const SliverToBoxAdapter(
          child: Padding(
            padding: EdgeInsets.all(24),
            child: Text(
              'No encontramos productos en esta sucursal. Revisa la búsqueda.',
            ),
          ),
        ),
    ],
  );

  Widget _cartPanel(
    BuildContext context, {
    VoidCallback? onClose,
    VoidCallback? onPay,
  }) {
    if (!mounted || !widget.controller.canSell) {
      return const Center(child: Text('Revalida tu autorización.'));
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 16, 12, 12),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Venta actual',
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                    Text(
                      'Carrito · ${_cart.length} productos',
                      style: const TextStyle(color: WebTheme.muted),
                    ),
                  ],
                ),
              ),
              if (_cart.isNotEmpty)
                TextButton(
                  onPressed: _closing ? null : _discard,
                  child: const Text('Vaciar'),
                ),
              if (onClose != null)
                IconButton(
                  tooltip: 'Cerrar venta',
                  onPressed: onClose,
                  icon: const Icon(Icons.close),
                ),
            ],
          ),
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20),
          child: OutlinedButton.icon(
            onPressed: _editable ? _selectCustomer : null,
            icon: const Icon(Icons.person_outline, color: WebTheme.blue),
            label: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(_customer?.name ?? 'Seleccionar cliente'),
                if (_customer != null)
                  Text(
                    'Descuento ${decimalText(_customer!.discountBasisPoints)}% · Adeudo ${money(_customer!.balanceCents)}',
                    style: const TextStyle(fontSize: 11),
                  ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),
        Expanded(
          child: ListView(
            key: const Key('sale-form-scroll'),
            padding: const EdgeInsets.symmetric(horizontal: 20),
            children: [
              if (_cart.isEmpty)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 32),
                  child: Text(
                    'Agrega productos para iniciar la venta.',
                    textAlign: TextAlign.center,
                  ),
                ),
              for (final e in _cart.entries)
                Builder(
                  builder: (context) {
                    final l = _quote?.lines
                        .where((l) => l.product.id == e.key)
                        .firstOrNull;
                    return Padding(
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  l?.product.name ?? 'Producto ${e.key}',
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                              ),
                              IconButton(
                                tooltip: 'Quitar producto',
                                onPressed: _editable
                                    ? () {
                                        setState(() {
                                          _cart.remove(e.key);
                                          _stockAcknowledged = false;
                                        });
                                        _load();
                                      }
                                    : null,
                                icon: const Icon(Icons.close, size: 18),
                              ),
                            ],
                          ),
                          Row(
                            children: [
                              OutlinedButton(
                                onPressed: _editable && l != null
                                    ? () => _quantity(l.product)
                                    : null,
                                child: Text(
                                  '${decimalText(e.value)} × ${l == null ? '—' : money(l.unitPriceCents)}',
                                ),
                              ),
                              const Spacer(),
                              Text(
                                l == null ? '—' : money(l.totalCents),
                                style: const TextStyle(
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    );
                  },
                ),
              const Divider(),
              DropdownButtonFormField<String>(
                initialValue: _type,
                decoration: const InputDecoration(labelText: 'Tipo de venta'),
                items: [
                  const DropdownMenuItem(
                    value: 'Contado',
                    child: Text('Contado'),
                  ),
                  if (widget.controller.session!.permissions.contains(
                    'sale.credit',
                  ))
                    const DropdownMenuItem(
                      value: 'Credito',
                      child: Text('Crédito'),
                    ),
                ],
                onChanged: _editable
                    ? (v) {
                        setState(() {
                          _type = v!;
                          _received.clear();
                        });
                        _refreshCart?.call();
                      }
                    : null,
              ),
              if (_quote?.insufficientStock == true)
                CheckboxListTile(
                  contentPadding: EdgeInsets.zero,
                  value: _stockAcknowledged,
                  onChanged: _editable
                      ? (v) {
                          setState(() => _stockAcknowledged = v!);
                          _refreshCart?.call();
                        }
                      : null,
                  title: const Text(
                    'La cantidad supera la existencia estimada',
                  ),
                  subtitle: const Text(
                    'Revisé la disponibilidad; puede requerir revisión central.',
                  ),
                ),
              if (_closingRequest != null)
                OutlinedButton(
                  onPressed: _closing ? null : _reviewClosure,
                  child: const Text('Revisar cierre y carrito'),
                ),
            ],
          ),
        ),
        Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                children: [
                  const Expanded(
                    child: Text(
                      'TOTAL',
                      style: TextStyle(fontWeight: FontWeight.w700),
                    ),
                  ),
                  Text(
                    _quote == null ? '\$0.00' : money(_quote!.totalCents),
                    style: const TextStyle(
                      fontSize: 30,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              FilledButton.icon(
                onPressed: _quote == null || _closing
                    ? null
                    : onPay ?? _showPayment,
                icon: const Icon(Icons.payments_outlined),
                label: Text(_closing ? 'Guardando…' : 'Cobrar'),
              ),
              const SizedBox(height: 8),
              const Text(
                'Venta local · sincronización automática',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 11, color: WebTheme.muted),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _QuantityDialog extends StatefulWidget {
  final String name, initial;
  const _QuantityDialog({required this.name, required this.initial});
  @override
  State<_QuantityDialog> createState() => _QuantityDialogState();
}

class _QuantityDialogState extends State<_QuantityDialog> {
  late final _value = TextEditingController(text: widget.initial);
  String? _error;
  @override
  void dispose() {
    _value.dispose();
    super.dispose();
  }

  void _save() {
    try {
      final quantity = decimalUnits(_value.text.trim());
      if (quantity <= 0 || quantity > maxQuantityUnits) {
        throw const FormatException();
      }
      Navigator.pop(context, _value.text.trim());
    } catch (_) {
      setState(() => _error = 'Cantidad positiva, máximo dos decimales.');
    }
  }

  @override
  Widget build(BuildContext context) => AlertDialog(
    title: Text(widget.name),
    content: TextField(
      controller: _value,
      autofocus: true,
      keyboardType: const TextInputType.numberWithOptions(decimal: true),
      onSubmitted: (_) => _save(),
      decoration: InputDecoration(labelText: 'Cantidad', errorText: _error),
    ),
    actions: [
      TextButton(
        onPressed: () => Navigator.pop(context),
        child: const Text('Cancelar'),
      ),
      FilledButton(onPressed: _save, child: const Text('Agregar')),
    ],
  );
}

class _CustomerPicker extends StatefulWidget {
  final AppController controller;
  const _CustomerPicker({required this.controller});
  @override
  State<_CustomerPicker> createState() => _CustomerPickerState();
}

class _CustomerPickerState extends State<_CustomerPicker> {
  final _search = TextEditingController();
  List<CustomerView> _customers = [];
  int _request = 0;
  String? _error;
  @override
  void initState() {
    super.initState();
    _load('');
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  Future<void> _load(String query) async {
    final request = ++_request;
    try {
      final customers = await widget.controller.repository!.customers(query);
      if (mounted && request == _request) {
        setState(() => _customers = customers);
      }
    } catch (_) {
      if (mounted) {
        setState(() => _error = 'No se pudieron consultar los clientes.');
      }
    }
  }

  @override
  Widget build(BuildContext context) => ListenableBuilder(
    listenable: widget.controller,
    builder: (context, _) => AlertDialog(
      title: const Text('Cliente existente'),
      content: SizedBox(
        width: 480,
        height: 360,
        child: !widget.controller.canConsult
            ? const Text('Revalida tu autorización.')
            : Column(
                children: [
                  TextField(
                    controller: _search,
                    onChanged: _load,
                    decoration: const InputDecoration(labelText: 'Nombre o ID'),
                  ),
                  if (_error != null) Text(_error!),
                  Expanded(
                    child: ListView.builder(
                      itemCount: _customers.length,
                      itemBuilder: (context, i) {
                        final c = _customers[i];
                        return ListTile(
                          title: Text(c.name),
                          subtitle: Text(
                            'Descuento ${decimalText(c.discountBasisPoints)}% · Adeudo estimado ${money(c.balanceCents)}',
                          ),
                          onTap: () => Navigator.pop(context, c),
                        );
                      },
                    ),
                  ),
                ],
              ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Cancelar'),
        ),
      ],
    ),
  );
}
