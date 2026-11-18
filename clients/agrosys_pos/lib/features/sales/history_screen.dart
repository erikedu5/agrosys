import 'package:flutter/material.dart';
import 'package:drift/drift.dart' show OrderingTerm;

import '../../core/app_controller.dart';
import '../../core/models.dart';
import '../../data/database.dart';
import '../../ui/components.dart';
import '../receipts/receipt_dialog.dart';
import 'sale_domain.dart';

class HistoryScreen extends StatefulWidget {
  final AppController controller;
  const HistoryScreen({super.key, required this.controller});
  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  List<LocalSale> _sales = [];
  Map<String, OutboxData> _queue = {};
  String? _filter;
  bool _loading = true, _more = false;
  String? _error;
  int _generation = -1, _request = 0;
  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void didUpdateWidget(HistoryScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (_generation != widget.controller.generation) _load();
  }

  Future<void> _load({bool append = false}) async {
    if (append && _loading) return;
    final request = ++_request;
    _generation = widget.controller.generation;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final rows = await widget.controller.sales!.history(
        offset: append ? _sales.length : 0,
        status: _filter,
      );
      final db = widget.controller.repository!.db;
      final queue = await (db.select(
        db.outbox,
      )..orderBy([(t) => OrderingTerm.asc(t.sequence)])).get();
      if (mounted && request == _request) {
        setState(() {
          _queue = {
            if (append) ..._queue,
            for (final row in queue) row.saleId: row,
          };
          _sales = append ? [..._sales, ...rows] : rows;
          _more = rows.length == 100;
        });
      }
    } catch (_) {
      if (mounted && request == _request) {
        setState(
          () => _error =
              'No se pudo abrir el historial. Las ventas se conservaron.',
        );
      }
    } finally {
      if (mounted && request == _request) setState(() => _loading = false);
    }
  }

  Future<void> _open(LocalSale sale) async {
    try {
      final receipt = await widget.controller.sales!.receipt(sale.id);
      if (mounted && widget.controller.canConsult) {
        await showReceipt(context, widget.controller, receipt);
      }
    } catch (_) {
      if (mounted) {
        setState(
          () => _error =
              'No se pudo abrir el comprobante. La venta permanece guardada.',
        );
      }
    }
  }

  static const _filters = <(String?, String, Color?)>[
    (null, 'Todas', null),
    ('pending_sync', 'Pendientes', Palette.amber400),
    ('conflict', 'En revisión', Palette.red500),
    ('confirmed', 'Confirmadas', Palette.green600),
  ];

  Tone _tone(String status) => switch (status) {
    'confirmed' => Tone.green,
    'conflict' => Tone.red,
    'pending_sync' => Tone.amber,
    _ => Tone.gray,
  };

  Widget _card(LocalSale s) {
    final reason = _queue[s.id]?.error;
    final when = DateTime.parse(s.occurredAt).toLocal();
    return RecordCard(
      key: ValueKey('history-sale-${s.id}'),
      eyebrow: s.localFolio,
      title: s.customerName,
      badges: [UiBadge(saleStatusText(s.status), tone: _tone(s.status))],
      fields: [
        RecordField('Tipo', s.saleType == 'Credito' ? 'Crédito' : 'Contado'),
        RecordField('Saldo de la venta', money(s.balanceCents)),
        RecordField(
          'Fecha',
          '${when.day}/${when.month}/${when.year} ${when.hour}:${when.minute.toString().padLeft(2, '0')}',
        ),
        RecordField('Folio central', s.serverFolio ?? 'Pendiente'),
      ],
      actions: [
        OutlinedButton.icon(
          onPressed: () => _open(s),
          icon: const Icon(Icons.receipt_long_outlined, size: 20),
          label: const Text('Ver ticket'),
        ),
      ],
      onTap: () => _open(s),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          AmountLine(money(s.totalCents), label: 'Total'),
          if (reason != null) ...[
            const SizedBox(height: 8),
            UiAlert(reason, tone: Tone.red),
          ],
          if (s.status == 'conflict') ...[
            const SizedBox(height: 8),
            const Muted(
              'Solicita revisión central. El reintento no modifica esta venta.',
            ),
          ],
        ],
      ),
    );
  }

  /// Encabezado, filtros y avisos; se desplazan con las tarjetas, como en la web.
  List<Widget> _top() => [
    PageHeader(
      title: 'Ventas del dispositivo',
      subtitle: 'Tickets y pendientes de este contexto. Las ventas terminadas se conservan.',
      actions: [
        OutlinedButton.icon(
          onPressed: widget.controller.syncBusy || widget.controller.busy
              ? null
              : () => widget.controller.synchronize(),
          icon: const Icon(Icons.sync),
          label: Text(
            widget.controller.syncBusy
                ? 'Sincronizando…'
                : 'Reintentar pendientes',
          ),
        ),
        IconButton.outlined(
          tooltip: 'Actualizar historial',
          onPressed: _loading ? null : _load,
          icon: const Icon(Icons.refresh),
        ),
      ],
    ),
    const SizedBox(height: 12),
    FilterChips<String?>(
      key: const Key('history-filter'),
      options: _filters,
      selected: _filter,
      onSelected: (value) {
        _filter = value;
        _load();
      },
    ),
    const SizedBox(height: 12),
    if (widget.controller.error != null)
      Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: UiAlert(widget.controller.error!, tone: Tone.red),
      ),
    if (_error != null)
      Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: UiAlert(_error!, tone: Tone.red),
      ),
  ];

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.all(16),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (_loading) const LinearProgressIndicator(),
        Expanded(
          child: RecordGrid(
            scrollKey: const Key('history-scroll'),
            itemCount: _sales.length,
            itemBuilder: (context, i) => _card(_sales[i]),
            header: [
              ..._top(),
              if (_sales.isEmpty && !_loading)
                const EmptyState(
                  title: 'Todavía no hay ventas guardadas.',
                  text: 'Las ventas que cobres en este dispositivo aparecen aquí.',
                ),
            ],
            footer: [
              if (_more)
                TextButton(
                  onPressed: _loading ? null : () => _load(append: true),
                  child: const Text('Cargar más ventas'),
                ),
            ],
          ),
        ),
      ],
    ),
  );
}
