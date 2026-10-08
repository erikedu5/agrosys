import 'dart:convert';

import 'package:drift/drift.dart';

import '../../core/models.dart';
import '../../data/database.dart';

Future<void> confirmOperation(
  PosDatabase db,
  OutboxData row,
  Map<String, dynamic> result,
) async {
  if ([
        'IDEMPOTENCY_PAYLOAD_MISMATCH',
        'LOCAL_PAYLOAD_CORRUPT',
      ].contains(row.errorCode) ||
      result['operationId'] != row.operationId ||
      result['saleId'] != row.saleId ||
      result['originalStatus'] != 'confirmed' ||
      result['serverFolio'] is! String ||
      (result['serverFolio'] as String).isEmpty) {
    throw const FormatException('La confirmación no corresponde a esta venta.');
  }
  await (db.update(db.localSales)..where(
        (t) => t.id.equals(row.saleId) & t.contextId.equals(row.contextId),
      ))
      .write(
        LocalSalesCompanion(
          status: const Value('confirmed'),
          serverFolio: Value(result['serverFolio'] as String),
        ),
      );
  await (db.update(
    db.outbox,
  )..where((t) => t.operationId.equals(row.operationId))).write(
    OutboxCompanion(
      status: const Value('confirmed'),
      serverResult: Value(jsonEncode(result)),
      error: const Value(null),
      errorCode: const Value(null),
      workerLease: const Value(null),
      nextAttemptAt: const Value(null),
    ),
  );
  await (db.update(db.localEffects)
        ..where((t) => t.operationId.equals(row.operationId)))
      .write(const LocalEffectsCompanion(originalStatus: Value('confirmed')));
}

Map<String, dynamic> originalResult(Map<String, dynamic> result) {
  final nested = result['result'];
  if (nested is Map) return {...result, ...object(nested)};
  return result;
}
