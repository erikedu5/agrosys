import 'package:drift/drift.dart' show Variable;
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../core/app_controller.dart';
import '../../core/models.dart';
import '../../data/database.dart';

class DailySales {
  final int count, total, paid, balance, pending, conflicts;
  const DailySales(
    this.count,
    this.total,
    this.paid,
    this.balance,
    this.pending,
    this.conflicts,
  );
  static Future<DailySales> load(PosDatabase db, DateTime day) async {
    final start = DateTime(
      day.year,
      day.month,
      day.day,
    ).toUtc().toIso8601String();
    final end = DateTime(
      day.year,
      day.month,
      day.day + 1,
    ).toUtc().toIso8601String();
    final r = await db
        .customSelect(
          "SELECT COUNT(*) AS n, COALESCE(SUM(total_cents),0) AS total, COALESCE(SUM(applied_cents),0) AS paid, COALESCE(SUM(balance_cents),0) AS balance, COALESCE(SUM(CASE WHEN status='pending_sync' THEN 1 ELSE 0 END),0) AS pending, COALESCE(SUM(CASE WHEN status='conflict' THEN 1 ELSE 0 END),0) AS conflicts FROM local_sales WHERE context_id=? AND occurred_at>=? AND occurred_at<?",
          variables: [Variable(db.contextId), Variable(start), Variable(end)],
          readsFrom: {db.localSales},
        )
        .getSingle();
    return DailySales(
      r.read<int>('n'),
      r.read<int>('total'),
      r.read<int>('paid'),
      r.read<int>('balance'),
      r.read<int>('pending'),
      r.read<int>('conflicts'),
    );
  }

  String text(String branch) =>
      'AGROSYS · Ventas del día · $branch\nSólo ventas de este dispositivo/contexto\nVentas: $count\nTotal: ${money(total)}\nEfectivo aplicado: ${money(paid)}\nSaldo de ventas a crédito: ${money(balance)}\nPendientes: $pending\nEn revisión: $conflicts\nIncluye cobros locales en revisión; conciliar con el servidor.';
}

Future<void> showDailySales(BuildContext context, AppController c) =>
    showDialog<void>(
      context: context,
      builder: (_) => _DailySalesDialog(controller: c),
    );

class _DailySalesDialog extends StatefulWidget {
  final AppController controller;
  const _DailySalesDialog({required this.controller});
  @override
  State<_DailySalesDialog> createState() => _DailySalesDialogState();
}

class _DailySalesDialogState extends State<_DailySalesDialog> {
  late final contextId = widget.controller.session!.contextId;
  late final report = DailySales.load(
    widget.controller.repository!.db,
    DateTime.now(),
  );
  @override
  Widget build(BuildContext context) => ListenableBuilder(
    listenable: widget.controller,
    builder: (context, _) => AlertDialog(
      title: const Text('Ventas del día'),
      content: SizedBox(
        width: 480,
        child:
            !widget.controller.canConsult ||
                widget.controller.session?.contextId != contextId
            ? const Text('Revalida tu autorización.')
            : FutureBuilder(
                future: report,
                builder: (context, snapshot) {
                  if (snapshot.hasError) {
                    return const Text(
                      'No se pudo leer el resumen. Las ventas se conservaron.',
                    );
                  }
                  if (!snapshot.hasData) {
                    return const SizedBox(
                      height: 80,
                      child: Center(child: CircularProgressIndicator()),
                    );
                  }
                  final r = snapshot.data!;
                  return SingleChildScrollView(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(widget.controller.session!.branch.name),
                        const SizedBox(height: 12),
                        const Text(
                          'Resumen local de este dispositivo. Incluye ventas pendientes y en revisión; concilia con el servidor.',
                        ),
                        const Divider(),
                        _row('Ventas', '${r.count}'),
                        _row('Total', money(r.total)),
                        _row('Efectivo aplicado', money(r.paid)),
                        _row('Saldo de crédito', money(r.balance)),
                        _row('Pendientes', '${r.pending}'),
                        _row('En revisión', '${r.conflicts}'),
                        const SizedBox(height: 12),
                        TextButton.icon(
                          onPressed: () => Clipboard.setData(
                            ClipboardData(
                              text: r.text(
                                widget.controller.session!.branch.name,
                              ),
                            ),
                          ),
                          icon: const Icon(Icons.copy),
                          label: const Text('Copiar para conciliación'),
                        ),
                      ],
                    ),
                  );
                },
              ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Cerrar'),
        ),
      ],
    ),
  );
  Widget _row(String label, String value) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 8),
    child: Row(
      children: [
        Expanded(child: Text(label)),
        Text(value, style: const TextStyle(fontWeight: FontWeight.w700)),
      ],
    ),
  );
}
