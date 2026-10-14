import 'package:agrosys_pos/core/models.dart';
import 'package:agrosys_pos/core/pos_api.dart';

import 'inventory_server.dart';

class WorkflowServer extends InventoryServer {
  final purchases = <String, Map<String, dynamic>>{};
  final transfers = <String, Map<String, dynamic>>{};
  WorkflowServer({bool purchasesAccess = true, bool transfersAccess = true}) {
    permissions!.addAll([
      if (purchasesAccess) ...[
        'purchase.read',
        'purchase.create',
        'purchase.payment',
      ],
      if (transfersAccess) ...[
        'transfer.read',
        'transfer.create',
        'transfer.receive',
      ],
    ]);
  }
  Map<String, dynamic> incoming() => {
    'id': '99',
    'folio': 'TRANS-IN',
    'originId': '2',
    'origin': 'Norte',
    'destinationId': '1',
    'destination': 'Centro',
    'status': 'pendiente',
    'date': '2026-10-08',
    'receivedAt': null,
    'notes': 'Insumos',
    'items': [
      {
        'productId': '10',
        'name': 'Semillas de maíz',
        'size': '1L',
        'quantity': '2.50',
      },
    ],
  };
  @override
  Future<Map<String, dynamic>> request(
    String path, {
    Map<String, dynamic>? body,
    String? token,
    String method = 'POST',
  }) async {
    if (!path.startsWith('purchases/') && !path.startsWith('transfers/')) {
      return super.request(path, body: body, token: token, method: method);
    }
    if (disconnected) throw ApiFailure(null, 'Sin conexión');
    final parts = path.split('/'), purchase = parts.first == 'purchases';
    final rows = purchase ? purchases : transfers;
    if (path == 'transfers/destinations') {
      return {
        'destinations': [
          {'id': '2', 'name': 'Norte'},
        ],
      };
    }
    if (parts.last == 'list') {
      return {'items': rows.values.toList(), 'page': 1, 'hasMore': false};
    }
    if (parts.last == 'detail') {
      if (!rows.containsKey(parts[1])) {
        throw ApiFailure(404, 'No existe el registro.');
      }
      return {
        purchase ? 'purchase' : 'transfer': {...rows[parts[1]]!},
      };
    }
    final permission = '${purchase ? 'purchase' : 'transfer'}.${parts.last}';
    if (!permissions!.contains(permission)) {
      throw ApiFailure(403, 'Operación no autorizada.');
    }
    mutationBodies.add(Map<String, dynamic>.from(body!));
    final operation = body['operation_id'] as String;
    if (results.containsKey(operation)) return results[operation]!;
    if (stale) throw ApiFailure(409, 'El saldo cambió. Recarga los datos.');
    final id = parts.last == 'create' ? '${rows.length + 1}' : parts[1];
    if (parts.last == 'create') {
      final items = (body['items'] as List)
          .map(object)
          .map(
            (item) => {
              ...item,
              'name': products[item['productId']]!['name'],
              'size': products[item['productId']]!['size'],
            },
          )
          .toList();
      if (purchase) {
        final total = items.fold<int>(
          0,
          (sum, row) =>
              sum +
              (decimalUnits(row['quantity']) * decimalUnits(row['cost']) +
                      50) ~/
                  100,
        );
        final paid = decimalUnits(body['payment']);
        purchases[id] = {
          'id': id,
          'supplier': body['supplier'],
          'date': body['date'],
          'dueDate': '2026-11-07',
          'total': decimalText(total),
          'debt': decimalText(total - paid),
          'status': paid == total ? 'pagado' : 'adeudo',
          'items': items,
          'payments': <Map<String, dynamic>>[
            if (paid > 0)
              {'id': '1', 'amount': decimalText(paid), 'date': '2026-10-08'},
          ],
        };
        for (final item in items) {
          products[item['productId']]!['serverQuantity'] = decimalText(
            decimalUnits(products[item['productId']]!['serverQuantity']) +
                decimalUnits(item['quantity']),
          );
        }
      } else {
        transfers[id] = {
          'id': id,
          'folio': 'TRANS-$id',
          'originId': '1',
          'origin': 'Centro',
          'destinationId': body['destination_id'],
          'destination': 'Norte',
          'status': 'pendiente',
          'date': '2026-10-08',
          'receivedAt': null,
          'notes': body['notes'],
          'items': items,
        };
      }
    } else if (purchase) {
      final row = purchases[id]!;
      final debt = decimalUnits(row['debt']);
      if (debt != decimalUnits(body['expected_debt'])) {
        throw ApiFailure(409, 'El saldo cambió.');
      }
      final amount = decimalUnits(body['amount']);
      if (amount <= 0 || amount > debt) {
        throw ApiFailure(422, 'El abono supera el saldo.');
      }
      row['debt'] = decimalText(debt - amount);
      if (debt == amount) row['status'] = 'pagado';
      (row['payments'] as List).add({
        'id': '${(row['payments'] as List).length + 1}',
        'amount': body['amount'],
        'date': '2026-10-08',
      });
    } else {
      final row = transfers[id]!;
      if (row['destinationId'] != '1') {
        throw ApiFailure(403, 'Solo la sucursal destino puede recibir.');
      }
      if (row['status'] != 'completado') {
        row['status'] = 'completado';
        row['receivedAt'] = '2026-10-08';
        for (final item in (row['items'] as List).map(object)) {
          products[item['productId']]!['serverQuantity'] = decimalText(
            decimalUnits(products[item['productId']]!['serverQuantity']) +
                decimalUnits(item['quantity']),
          );
        }
      }
    }
    mutations++;
    final result = {
      'operationId': operation,
      'status': 'confirmed',
      purchase ? 'purchaseId' : 'transferId': id,
    };
    results[operation] = result;
    if (loseReply) {
      loseReply = false;
      throw ApiFailure(null, 'La respuesta se perdió.');
    }
    return result;
  }
}
