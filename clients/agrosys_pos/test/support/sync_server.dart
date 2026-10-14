import 'dart:async';
import 'dart:convert';

import 'package:agrosys_pos/core/models.dart';
import 'package:agrosys_pos/core/pos_api.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fixtures.dart';

// Deterministic central contract simulator, not a Laravel integration server.
class CentralApi extends PosApi {
  CentralApi() : super('https://pos.example/api/v1/pos');
  final results = <String, Map<String, dynamic>>{};
  final bytes = <String, String>{};
  final sent = <List<int>>[];
  final scriptedPages = <Object>[];
  int stock = 200000, debt = 4000, payments = 0, cuts = 0, cutBudget = 0;
  int cutAfter = 10;
  int? denied;
  bool failPull = false, inactiveProduct = false, inactiveCustomer = false;
  int price = 5000;
  int externalPayment = 0;
  final rejected = <String, String>{};
  final retryOnce = <String>{};
  final mismatches = <String>{};
  bool malformed = false;
  Completer<void>? gate;
  int calls = 0, queries = 0;
  @override
  Future<Map<String, dynamic>> operation(Session s, String id) async {
    queries++;
    if (denied != null) throw ApiFailure(denied, 'Revalidación necesaria');
    final result = results[id];
    if (result == null) throw ApiFailure(404, 'No encontrada');
    return {
      'operationId': id,
      'originalStatus': result['originalStatus'],
      'result': result,
    };
  }

  @override
  Future<Map<String, dynamic>> push(
    Session s,
    List<Map<String, dynamic>> ops,
  ) async {
    calls++;
    sent.add([for (final op in ops) op['sequence'] as int]);
    if (gate != null) await gate!.future;
    if (denied != null) throw ApiFailure(denied, 'Revalidación necesaria');
    final output = <Map<String, dynamic>>[];
    for (final op in ops) {
      final id = op['operation_id'] as String;
      final encoded = jsonEncode(op);
      if (bytes.containsKey(id)) expect(encoded, bytes[id]);
      if (retryOnce.remove(id)) {
        output.add({'operationId': id, 'originalStatus': 'retry'});
        continue;
      }
      if (!results.containsKey(id)) {
        bytes[id] = encoded;
        final payload = object(op['payload']);
        final item = object((payload['items'] as List).single);
        final code =
            rejected[id] ??
            (inactiveProduct
                ? 'PRODUCT_INACTIVE'
                : inactiveCustomer
                ? 'CUSTOMER_INACTIVE'
                : decimalUnits(item['unitPrice']) != price
                ? 'PRICE_CHANGED'
                : null);
        if (code != null) {
          results[id] = {
            'operationId': id,
            'originalStatus': 'conflict',
            'errorCode': code,
            'message': code,
          };
        } else {
          final total = decimalUnits(payload['total']);
          final payment = decimalUnits(
            object((payload['payments'] as List).single)['amount'],
          );
          stock -= decimalUnits(item['quantity']);
          debt += total;
          payments += payment;
          results[id] = {
            'operationId': id,
            'saleId': op['aggregate_id'],
            'originalStatus': 'confirmed',
            'status': 'confirmed',
            'serverFolio': 'CENTRAL-${results.length + 1}',
          };
        }
      }
      output.add({
        ...results[id]!,
        'status': mismatches.contains(id) ? 'conflict' : 'duplicate',
        if (mismatches.contains(id))
          'errorCode': 'IDEMPOTENCY_PAYLOAD_MISMATCH',
      });
      if (cutBudget > 0 && output.length == cutAfter) {
        cutBudget--;
        cuts++;
        throw ApiFailure(null, 'Respuesta perdida después del commit central');
      }
    }
    if (malformed) {
      return {
        'results': [
          {
            'operationId': ops.first['operation_id'],
            'originalStatus': 'confirmed',
            'saleId': 'wrong',
            'serverFolio': 'wrong',
          },
        ],
      };
    }
    return {'results': output};
  }

  @override
  Future<Map<String, dynamic>> page(
    Session s, {
    String? pageToken,
    String? cursor,
    required bool incremental,
    List<String> knownIds = const [],
  }) async {
    if (denied != null) throw ApiFailure(denied, 'Revalidación necesaria');
    if (failPull) throw ApiFailure(null, 'Pull interrumpido');
    if (scriptedPages.isNotEmpty) {
      final next = scriptedPages.removeAt(0);
      if (next is Exception) throw next;
      return object(next);
    }
    final p = {
      ...product(quantity: decimalText(stock), active: !inactiveProduct),
      'price': decimalText(price),
      'unitPrice': decimalText(price),
    };
    return snapshot(
      products: [p],
      customers: [
        {
          ...customer(
            balance: decimalText(debt - payments - externalPayment),
            active: !inactiveCustomer,
          ),
          'discountPercentage': '0.00',
          'debtTotal': decimalText(debt),
          'paymentTotal': decimalText(payments + externalPayment),
        },
      ],
      receipts: [
        for (final id in knownIds)
          if (results[id]?['originalStatus'] == 'confirmed')
            {
              'operationId': id,
              'originalStatus': 'confirmed',
              'includedInSnapshot': true,
              'result': results[id],
            },
      ],
    );
  }
}

class SyncFixtureApi extends FixtureApi {
  final central = CentralApi();
  SyncFixtureApi() {
    salesEnabled = true;
  }
  @override
  Future<Map<String, dynamic>> request(
    String path, {
    Map<String, dynamic>? body,
    String? token,
    String method = 'POST',
  }) async {
    if (disconnected) throw ApiFailure(null, 'Sin conexión');
    if (deniedStatus != null) {
      throw ApiFailure(deniedStatus, 'Acceso bloqueado');
    }
    if (path == 'sync/push') {
      return central.push(fixtureSession(), [
        for (final op in body!['operations'] as List) object(op),
      ]);
    }
    if (path.startsWith('operations/')) {
      return central.operation(
        fixtureSession(),
        path.substring('operations/'.length),
      );
    }
    if (path == 'bootstrap' || path == 'sync/pull') {
      return central.page(
        fixtureSession(),
        incremental: path == 'sync/pull',
        knownIds: List<String>.from(
          body?['known_operation_ids'] as List? ?? [],
        ),
      );
    }
    return super.request(path, body: body, token: token, method: method);
  }
}
