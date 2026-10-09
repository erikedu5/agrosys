import 'package:agrosys_pos/core/models.dart';
import 'package:agrosys_pos/core/pos_api.dart';
import 'package:uuid/uuid.dart';

import 'fixtures.dart';

class InventoryServer extends FixtureApi {
  final products = <String, Map<String, dynamic>>{
    '10': {...product(), 'classification': 'Semillas'},
  };
  final results = <String, Map<String, dynamic>>{};
  final mutationBodies = <Map<String, dynamic>>[];
  bool loseReply = false, failDownload = false, stale = false;
  int mutations = 0;
  List<Map<String, dynamic>> branchesWithStock = [];
  InventoryServer({bool costs = true, bool destructive = false}) {
    permissions = [
      'catalog.read',
      'product.search',
      'stock.read_estimated',
      'inventory.read',
      'inventory.movements.read',
      'product.create',
      'product.update',
      'product.prices.update',
      'inventory.add',
      if (destructive) ...['inventory.reset', 'product.delete'],
      if (costs) 'product.costs.manage',
    ];
  }

  @override
  Future<Map<String, dynamic>> request(
    String path, {
    Map<String, dynamic>? body,
    String? token,
    String method = 'POST',
  }) async {
    if (disconnected) {
      throw ApiFailure(null, 'Sin conexión');
    }
    if (path == 'device/heartbeat') {
      return {
        'authorized': true,
        'offlineLease': await signedLease(
          fixtureSession(device: device),
          pair: key,
          permissions: List<String>.of(permissions!),
        ),
      };
    }
    if (path == 'bootstrap' || path == 'sync/pull') {
      if (failDownload) {
        throw ApiFailure(null, 'Catálogo no disponible');
      }
      return snapshot(
        revision: const Uuid().v4(),
        products: products.values.toList(),
      );
    }
    if (path == 'inventory/options') {
      return {
        'categories': [
          {'id': '1', 'name': 'Semillas'},
        ],
        'brands': [
          {'id': '1', 'name': 'Marca de prueba'},
        ],
        'manageCosts': permissions!.contains('product.costs.manage'),
      };
    }
    if (path.endsWith('/preview')) {
      final p = products[path.split('/')[1]]!;
      final permission = body!['action'] == 'delete'
          ? 'product.delete'
          : 'inventory.reset';
      if (!permissions!.contains(permission)) {
        throw ApiFailure(403, 'Operación no autorizada.');
      }
      return {
        'productId': path.split('/')[1],
        'name': p['name'],
        'size': p['size'],
        'quantity': p['serverQuantity'],
        'version': 'a' * 64,
        'stockRevision': 'b' * 64,
        'branchesWithStock': branchesWithStock,
      };
    }
    if (path.endsWith('/detail')) {
      final id = path.split('/')[1], p = products[path.split('/')[1]]!;
      return {
        'product': {
          'id': id,
          'nombre': p['name'],
          'tamano': p['size'],
          'id_clasificacion': '1',
          'id_marca': '1',
          'barcode': p['barcode'],
          'ingrediente_activo': '',
          'precio_ieps': p['price'],
          'version': 'a' * 64,
          if (permissions!.contains('product.costs.manage')) ...{
            'precio_unitario': '40.00',
            'ieps': '25.00',
          },
        },
      };
    }
    if (path == 'inventory/products' ||
        [
          'add',
          'update',
          'prices',
          'reset',
          'delete',
        ].any((action) => path.endsWith('/$action'))) {
      mutationBodies.add(Map<String, dynamic>.from(body!));
      final operation = body['operation_id'] as String;
      if (results.containsKey(operation)) return results[operation]!;
      if (stale) {
        throw ApiFailure(
          409,
          'El producto cambió. Recarga sus datos antes de guardar.',
        );
      }
      final id = path == 'inventory/products'
          ? '${10 + products.length}'
          : path.split('/')[1];
      if (path.endsWith('/reset')) {
        products[id]!['serverQuantity'] = '0.00';
      } else if (path.endsWith('/delete')) {
        if (branchesWithStock.isNotEmpty) {
          throw ApiFailure(422, 'Hay existencias en otra sucursal.');
        }
        products[id]!['active'] = false;
      } else if (path.endsWith('/add')) {
        final p = products[id]!;
        p['serverQuantity'] = decimalText(
          decimalUnits(p['serverQuantity']) + decimalUnits(body['cantidad']),
        );
      } else {
        products[id] = {
          ...products[id] ?? product(id: id, quantity: '0.00'),
          if (body['nombre'] != null) 'name': body['nombre'],
          if (body['tamano'] != null) 'size': body['tamano'],
          if (body['barcode'] != null) 'barcode': body['barcode'],
          'price': body['precio_ieps'],
          'classification': 'Semillas',
        };
      }
      mutations++;
      final result = {
        'operationId': operation,
        'status': 'confirmed',
        'productId': id,
      };
      results[operation] = result;
      if (loseReply) {
        loseReply = false;
        throw ApiFailure(null, 'La respuesta se perdió.');
      }
      return result;
    }
    return super.request(path, body: body, token: token, method: method);
  }
}
