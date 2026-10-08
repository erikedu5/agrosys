import 'dart:convert';
import 'dart:math';

import 'package:crypto/crypto.dart';
import 'package:drift/drift.dart';
import 'package:uuid/uuid.dart';

import '../../core/models.dart';
import '../../core/pos_api.dart';
import '../../data/catalog_repository.dart';
import '../../data/catalog_sync.dart';
import '../../data/database.dart';
import 'sync_results.dart';

class SyncWorker {
  final PosApi api;
  final CatalogRepository catalog;
  final Session session;
  final DateTime Function() clock;
  final double Function() random;
  final bool Function() active;
  final void Function()? onChanged;
  final String owner = const Uuid().v4();
  Future<void>? _running;
  PosDatabase get db => catalog.db;
  SyncWorker(
    this.api,
    this.catalog,
    this.session, {
    DateTime Function()? clock,
    double Function()? random,
    bool Function()? active,
    this.onChanged,
  }) : clock = clock ?? DateTime.now,
       random = random ?? Random().nextDouble,
       active = active ?? (() => true);
  int get now => clock().toUtc().millisecondsSinceEpoch;
  Future<void> run({bool full = false, bool manual = false}) {
    if (_running != null) return _running!;
    final future = _run(full: full, manual: manual);
    _running = future.whenComplete(() {
      _running = null;
    });
    return _running!;
  }

  Future<bool> _acquire() => db.transaction(() async {
    if (session.contextId != db.contextId ||
        session.blocked ||
        !active() ||
        await catalog.isLocked()) {
      return false;
    }
    final changed = await db.customUpdate(
      'UPDATE sync_workers SET owner=?, expires_at=? WHERE context_id=? AND (owner IS NULL OR expires_at<=?)',
      variables: [
        Variable(owner),
        Variable(now + 120000),
        Variable(session.contextId),
        Variable(now),
      ],
      updates: {db.syncWorkers},
    );
    return changed == 1;
  });
  Future<void> _guard() async {
    if (!active() ||
        session.contextId != db.contextId ||
        session.blocked ||
        await catalog.isLocked()) {
      throw ApiFailure(403, 'Este contexto requiere revalidación.');
    }
    final changed = await db.customUpdate(
      'UPDATE sync_workers SET expires_at=? WHERE context_id=? AND owner=? AND expires_at>?',
      variables: [
        Variable(now + 120000),
        Variable(session.contextId),
        Variable(owner),
        Variable(now),
      ],
      updates: {db.syncWorkers},
    );
    if (changed != 1) {
      throw StateError('Otro ejecutor recuperó la sincronización.');
    }
  }

  Future<List<OutboxData>> _claim({required bool manual}) =>
      db.transaction(() async {
        await _guard();
        final rows =
            await (db.select(db.outbox)
                  ..where(
                    (t) =>
                        t.contextId.equals(session.contextId) &
                        t.status.isIn(['pending', 'retry', 'sending']),
                  )
                  ..orderBy([(t) => OrderingTerm.asc(t.sequence)])
                  ..limit(50))
                .get();
        final claimed = <OutboxData>[];
        for (final row in rows) {
          if (!manual &&
              row.nextAttemptAt != null &&
              DateTime.parse(row.nextAttemptAt!).millisecondsSinceEpoch > now &&
              row.status != 'sending') {
            break;
          }
          if (sha256.convert(utf8.encode(row.payload)).toString() !=
              row.payloadHash) {
            await _conflict(
              row,
              'LOCAL_PAYLOAD_CORRUPT',
              'La operación local requiere revisión; no se envió.',
            );
            continue;
          }
          await (db.update(
            db.outbox,
          )..where((t) => t.operationId.equals(row.operationId))).write(
            OutboxCompanion(
              status: const Value('sending'),
              workerLease: Value(owner),
              attempts: Value(row.attempts + 1),
            ),
          );
          claimed.add(row);
        }
        return claimed;
      });
  Future<void> _conflict(OutboxData row, String code, String message) async {
    if (row.status == 'confirmed') return;
    await (db.update(db.outbox)..where(
          (t) =>
              t.operationId.equals(row.operationId) &
              t.status.equals('confirmed').not(),
        ))
        .write(
          OutboxCompanion(
            status: const Value('conflict'),
            errorCode: Value(code),
            error: Value(message),
            workerLease: const Value(null),
            nextAttemptAt: const Value(null),
          ),
        );
    await (db.update(db.localSales)..where(
          (t) => t.id.equals(row.saleId) & t.status.equals('confirmed').not(),
        ))
        .write(const LocalSalesCompanion(status: Value('conflict')));
  }

  Future<void> _retry(OutboxData row, String message) async {
    final seconds = min(900, 2 * pow(2, min(row.attempts, 9)).toInt());
    final date = clock().toUtc().add(
      Duration(
        milliseconds: (seconds * 1000 * (0.75 + random() * 0.5)).round(),
      ),
    );
    await (db.update(db.outbox)..where(
          (t) =>
              t.operationId.equals(row.operationId) &
              t.workerLease.equals(owner),
        ))
        .write(
          OutboxCompanion(
            status: const Value('retry'),
            error: Value(message),
            nextAttemptAt: Value(date.toIso8601String()),
            workerLease: const Value(null),
          ),
        );
  }

  Future<void> _apply(OutboxData row, Map<String, dynamic> raw) =>
      db.transaction(() async {
        await _guard();
        final current = await (db.select(
          db.outbox,
        )..where((t) => t.operationId.equals(row.operationId))).getSingle();
        if (current.status == 'confirmed') return;
        if (raw['operationId'] != row.operationId) {
          throw const FormatException('Respuesta de otra operación.');
        }
        final result = originalResult(raw);
        if (raw['errorCode'] == 'IDEMPOTENCY_PAYLOAD_MISMATCH') {
          await _conflict(
            row,
            'IDEMPOTENCY_PAYLOAD_MISMATCH',
            'El servidor tiene otro contenido para este ID. Solicita revisión central.',
          );
        } else if (raw['originalStatus'] == 'confirmed') {
          await confirmOperation(db, row, result);
        } else if (['conflict', 'rejected'].contains(raw['originalStatus'])) {
          await _conflict(
            row,
            raw['errorCode'] as String? ?? 'BUSINESS_CONFLICT',
            raw['message'] as String? ?? 'La venta requiere revisión central.',
          );
          await (db.update(db.outbox)
                ..where((t) => t.operationId.equals(row.operationId)))
              .write(OutboxCompanion(serverResult: Value(jsonEncode(raw))));
        } else {
          await _retry(
            row,
            raw['message'] as String? ??
                'La operación aún no tiene confirmación.',
          );
        }
      });
  Future<void> _send(List<OutboxData> rows) async {
    final push = <OutboxData>[];
    for (final row in rows) {
      if (row.attempts > 0 || row.status == 'sending') {
        await _guard();
        try {
          await _apply(row, await api.operation(session, row.operationId));
          continue;
        } on ApiFailure catch (e) {
          if (e.status != 404) rethrow;
        }
      }
      push.add(row);
    }
    if (push.isEmpty) return;
    await _guard();
    final response = await api.push(session, [
      for (final row in push) object(jsonDecode(row.payload)),
    ]);
    final results = response['results'];
    if (results is! List) {
      throw const FormatException('Respuesta de sincronización inválida.');
    }
    final indexed = <String, Map<String, dynamic>>{};
    for (final raw in results) {
      final r = object(raw);
      final id = r['operationId'];
      if (id is! String ||
          !push.any((row) => row.operationId == id) ||
          indexed.containsKey(id)) {
        throw const FormatException(
          'Resultados de operaciones inconsistentes.',
        );
      }
      indexed[id] = r;
    }
    for (final row in push) {
      final result = indexed[row.operationId];
      if (result == null) {
        await _guard();
        await _retry(row, 'No llegó resultado de esta operación.');
      } else {
        await _apply(row, result);
      }
    }
  }

  Future<void> _run({required bool full, required bool manual}) async {
    if (!await _acquire()) return;
    try {
      // A valid acquired context has already passed durable revalidation.
      await (db.update(db.outbox)..where(
            (t) =>
                t.contextId.equals(session.contextId) &
                t.status.equals('blocked'),
          ))
          .write(
            const OutboxCompanion(
              status: Value('retry'),
              nextAttemptAt: Value(null),
            ),
          );
      final download = CatalogSync(api, catalog, beforePage: _guard);
      if (await catalog.download() != null) {
        await download.synchronize(full: full);
      }
      while (true) {
        final rows = await _claim(manual: manual);
        if (rows.isEmpty) break;
        try {
          await _send(rows);
        } catch (e) {
          await _guard();
          for (final row in rows) {
            if (e is ApiFailure && e.blocksAccess) {
              await (db.update(db.outbox)..where(
                    (t) =>
                        t.operationId.equals(row.operationId) &
                        t.workerLease.equals(owner),
                  ))
                  .write(
                    OutboxCompanion(
                      status: const Value('blocked'),
                      errorCode: Value('HTTP_${e.status}'),
                      error: Value(e.message),
                      workerLease: const Value(null),
                    ),
                  );
            } else {
              await _retry(
                row,
                e is ApiFailure ? e.message : 'No llegó una confirmación válida. Se conservó la operación.',
              );
            }
          }
          rethrow;
        }
        onChanged?.call();
        // A retry at the front stops this pass even on a manual trigger.
        final pending =
            await (db.select(db.outbox)
                  ..where(
                    (t) =>
                        t.contextId.equals(session.contextId) &
                        t.status.isIn(['retry', 'sending']),
                  )
                  ..limit(1))
                .get();
        if (pending.isNotEmpty) break;
      }
      await download.synchronize(full: full);
      // More than 2000 proofs require another snapshot. Stop if no progress.
      var previous = 1 << 30;
      while (true) {
        final count =
            (await db
                    .customSelect(
                      "SELECT COUNT(*) AS n FROM outbox o WHERE o.status='confirmed' AND EXISTS(SELECT 1 FROM local_effects e WHERE e.operation_id=o.operation_id AND reflected=0)",
                    )
                    .getSingle())
                .read<int>('n');
        if (count == 0 || count >= previous) break;
        previous = count;
        await download.synchronize();
      }
      onChanged?.call();
    } on ApiFailure catch (e) {
      if (e.blocksAccess && active()) await catalog.setLocked(true);
      rethrow;
    } finally {
      await db.customUpdate(
        'UPDATE sync_workers SET owner=NULL, expires_at=0 WHERE context_id=? AND owner=?',
        variables: [Variable(session.contextId), Variable(owner)],
        updates: {db.syncWorkers},
      );
    }
  }
}
