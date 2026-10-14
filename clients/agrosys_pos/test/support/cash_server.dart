import 'dart:convert';

import 'package:agrosys_pos/core/models.dart';
import 'package:agrosys_pos/core/pos_api.dart';

import 'fixtures.dart';

class CashServer extends FixtureApi {
  Map<String, dynamic>? current;
  final history = <Map<String, dynamic>>[];
  final responses = <String, Map<String, dynamic>>{};
  bool loseReply = false, invalidReply = false;
  int mutations = 0;
  CashServer() {
    permissions = [
      'catalog.read',
      'product.search',
      'stock.read_estimated',
      'account.read_estimated',
      'sale.create',
      'sale.credit',
      'sale.print_local_ticket',
      'customer.read',
      'sale.history',
      'cash.manage',
    ];
  }
  @override
  Future<Map<String, dynamic>> request(
    String path, {
    Map<String, dynamic>? body,
    String? token,
    String method = 'POST',
  }) async {
    if (!path.startsWith('cash/')) {
      return super.request(path, body: body, token: token, method: method);
    }
    if (disconnected) throw ApiFailure(null, 'Sin conexión');
    if (path == 'cash/status') {
      return jsonDecode(
        jsonEncode({'current': current, 'history': history, 'movements': []}),
      ) as Map<String, dynamic>;
    }
    final id = body!['operation_id'] as String;
    if (responses.containsKey(id)) return responses[id]!;
    if (path == 'cash/open') {
      current = {
        'id': 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
        'openedAt': '2026-10-13T08:00:00-06:00',
        'closedAt': null,
        'opening': body['opening'],
        'expected': body['opening'],
        'counted': null,
        'difference': null,
        'notes': null,
        'totals': <String, dynamic>{},
      };
    } else if (path == 'cash/movement') {
      final amount =
          decimalUnits(body['amount']) * (body['kind'] == 'out' ? -1 : 1);
      current!['expected'] = decimalText(
        decimalUnits(current!['expected']) + amount,
      );
      (current!['totals'] as Map<String, dynamic>)[body['kind'] as String] =
          decimalText(amount);
    } else if (path == 'cash/close') {
      current!.addAll({
        'closedAt': '2026-10-13T12:00:00-06:00',
        'counted': body['counted'],
        'difference': decimalText(
          decimalUnits(body['counted']) - decimalUnits(current!['expected']),
        ),
        'notes': body['notes'],
      });
    }
    final cut = jsonDecode(jsonEncode(current)) as Map<String, dynamic>;
    final result = {
      'operationId': id,
      'status': 'confirmed',
      'sessionId': cut['id'],
      if (!invalidReply) 'session': cut,
    };
    responses[id] = result;
    mutations++;
    if (path == 'cash/close') {
      history.insert(0, cut);
      current = null;
    }
    if (loseReply) {
      loseReply = false;
      disconnected = true;
      throw ApiFailure(null, 'Se perdió la respuesta');
    }
    return result;
  }
}
