import 'package:flutter/material.dart';
import 'package:drift/drift.dart' show OrderingTerm;

import '../../core/app_controller.dart';
import '../../core/models.dart';
import '../../data/database.dart';
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

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.all(16),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                'Ventas del dispositivo',
                style: Theme.of(context).textTheme.headlineSmall,
              ),
            ),
            IconButton(
              tooltip: 'Actualizar historial',
              onPressed: _loading ? null : _load,
              icon: const Icon(Icons.refresh),
            ),
          ],
        ),
        const Text(
          'Tickets y pendientes de este contexto. Las ventas terminadas se conservan.',
        ),
        Wrap(
          spacing: 12,
          crossAxisAlignment: WrapCrossAlignment.center,
          children: [
            DropdownButton<String>(
              value: _filter ?? 'all',
              items: const [
                DropdownMenuItem(value: 'all', child: Text('Todas')),
                DropdownMenuItem(
                  value: 'pending_sync',
                  child: Text('Pendientes'),
                ),
                DropdownMenuItem(value: 'conflict', child: Text('En revisión')),
                DropdownMenuItem(
                  value: 'confirmed',
                  child: Text('Confirmadas'),
                ),
              ],
              onChanged: (value) {
                _filter = value == 'all' ? null : value;
                _load();
              },
            ),
            TextButton.icon(
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
          ],
        ),
        if (widget.controller.error != null) Text(widget.controller.error!),
        if (_error != null) Text(_error!),
        if (_loading) const LinearProgressIndicator(),
        const SizedBox(height: 12),
        Expanded(
          child: _sales.isEmpty && !_loading
              ? const Center(child: Text('Todavía no hay ventas guardadas.'))
              : ListView.builder(
                  itemCount: _sales.length + (_more ? 1 : 0),
                  itemBuilder: (context, i) {
                    if (i == _sales.length) {
                      return TextButton(
                        onPressed: _loading ? null : () => _load(append: true),
                        child: const Text('Cargar más ventas'),
                      );
                    }
                    final s = _sales[i];
                    final queue = _queue[s.id];
                    final reason = queue?.error;
                    final when = DateTime.parse(s.occurredAt).toLocal();
                    return Card(
                      margin: const EdgeInsets.only(bottom: 8),
                      child: ListTile(
                        title: Text(
                          '${s.customerName} · ${money(s.totalCents)}',
                        ),
                        subtitle: Text(
                          '${s.localFolio}\n${s.saleType} · ${saleStatusText(s.status)}\nSaldo venta ${money(s.balanceCents)} · ${when.day}/${when.month}/${when.year} ${when.hour}:${when.minute.toString().padLeft(2, '0')}${s.serverFolio == null ? '' : '\nFolio central ${s.serverFolio}'}${reason == null ? '' : '\n$reason'}${s.status == 'conflict' ? '\nSolicita revisión central. El reintento no modifica esta venta.' : ''}',
                        ),
                        trailing: const Icon(Icons.receipt_long_outlined),
                        onTap: () => _open(s),
                      ),
                    );
                  },
                ),
        ),
      ],
    ),
  );
}
