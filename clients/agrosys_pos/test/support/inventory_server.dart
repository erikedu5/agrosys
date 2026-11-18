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
      'inventory.dose',
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
          'precio_publico': p['price'],
          'precio_preferencial_1': _prices(p)['preferencial_1'],
          'precio_preferencial_2': _prices(p)['preferencial_2'],
          'precio_ieps': p['price'],
          'version': 'a' * 64,
          if (permissions!.contains('product.costs.manage'))
            'precio_unitario': '40.00',
        },
      };
    }
    if (path == 'inventory/products' ||
        [
          'add',
          'dose',
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
      } else if (path.endsWith('/dose')) {
        return _dose(id, operation, body);
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
          ..._priceUpdate(products[id], body),
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

  Map<String, dynamic> _prices(Map<String, dynamic> p) =>
      Map<String, dynamic>.from(p['prices'] as Map? ?? {});

  /// Igual que InventoryManagement::prices: preferenciales vacíos toman el
  /// público en productos nuevos y conservan el suyo en existentes.
  Map<String, dynamic> _priceUpdate(
    Map<String, dynamic>? old,
    Map<String, dynamic> body,
  ) {
    final public = body['precio_publico'] ?? body['precio_ieps'];
    final previous = old == null ? null : _prices(old);
    return {
      'price': public,
      'prices': {
        'publico': public,
        'preferencial_1':
            body['precio_preferencial_1'] ??
            previous?['preferencial_1'] ??
            public,
        'preferencial_2':
            body['precio_preferencial_2'] ??
            previous?['preferencial_2'] ??
            public,
      },
    };
  }

  /// Igual que InventoryManagement::dose: baja 1 unidad y alta de N medidas.
  Map<String, dynamic> _dose(
    String id,
    String operation,
    Map<String, dynamic> body,
  ) {
    final source = products[id]!;
    final content = decimalUnits(body['contenido']),
        measure = decimalUnits(body['medida']);
    final count = content ~/ measure;
    if (content % measure != 0 && body['acepta_perdida'] != true) {
      throw ApiFailure(
        422,
        'Confirma la pérdida para continuar.',
        errors: {
          'acepta_perdida': ['Confirma la pérdida para continuar.'],
        },
      );
    }
    if (decimalUnits(source['serverQuantity'], signed: true) < 100) {
      throw ApiFailure(
        422,
        'No hay existencias suficientes.',
        errors: {
          'producto': [
            'No hay existencias suficientes para dosificar una unidad.',
          ],
        },
      );
    }
    final name = 'Medida ${source['name']}';
    final size = '${body['medida']} ${body['unidad']}';
    final targetId =
        products.entries
            .where((e) => e.value['name'] == name && e.value['size'] == size)
            .firstOrNull
            ?.key ??
        '${10 + products.length}';
    final target = products[targetId];
    source['serverQuantity'] = decimalText(
      decimalUnits(source['serverQuantity'], signed: true) - 100,
    );
    products[targetId] = {
      ...target ?? product(id: targetId, name: name, quantity: '0.00'),
      'name': name,
      'size': size,
      'barcode': '',
      ..._priceUpdate(target, body),
      'serverQuantity': decimalText(
        decimalUnits(target?['serverQuantity'] ?? '0.00') + count * 100,
      ),
    };
    mutations++;
    return results[operation] = {
      'operationId': operation,
      'status': 'confirmed',
      'productId': id,
      'quantity': source['serverQuantity'],
      'doseProductId': targetId,
      'doseUnits': count,
      'doseQuantity': products[targetId]!['serverQuantity'],
    };
  }
}
