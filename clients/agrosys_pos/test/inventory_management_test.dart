import 'dart:io';

import 'package:agrosys_pos/core/app_controller.dart';
import 'package:agrosys_pos/core/session_vault.dart';
import 'package:agrosys_pos/data/database.dart';
import 'package:drift/native.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/inventory_server.dart';
import 'support/fixtures.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  late Directory folder;
  late InventoryServer api;
  late AppController c;
  late MemorySecretStore secrets;
  AppController controller() => AppController(
    enableAutomaticSync: false,
    vault: SessionVault(secrets),
    apiFactory: (_) => api,
    openDatabase: (id) async =>
        PosDatabase(NativeDatabase(File('${folder.path}/pos.sqlite')), id),
  );
  setUp(() async {
    folder = await Directory.systemTemp.createTemp('inventory-management-');
    api = InventoryServer();
    secrets = MemorySecretStore();
    c = controller();
    await c.initialize();
    await c.signIn(
      'https://pos.example/api/v1/pos',
      'qa@example.com',
      'password',
    );
    await c.selectBranch(c.branches.single);
  });
  tearDown(() async {
    c.dispose();
    await c.repository?.db.close();
    await folder.delete(recursive: true);
  });

  test('lost response survives restart and retries original stock entry exactly once', () async {
    final id = await c.prepareInventoryRequest(
      'inventory/10/add',
      'inventory.add',
      {'cantidad': '1.25'},
    );
    api.loseReply = true;
    await expectLater(c.retryInventoryRequest(id), throwsException);
    expect(api.mutations, 1);
    expect(
      (await c.repository!.db
              .select(c.repository!.db.inventoryRequests)
              .getSingle())
          .status,
      'pending',
    );
    c.dispose();
    await c.repository!.db.close();
    c = controller();
    await c.initialize();
    await c.checkAccess();
    final result = await c.retryInventoryRequest(id);
    expect(result['status'], 'confirmed');
    expect(api.mutations, 1);
    expect(api.mutationBodies[0], api.mutationBodies[1]);
    expect((await c.repository!.products('10')).single.estimatedQuantity, 1125);
    expect(
      (await c.repository!.db
              .select(c.repository!.db.inventoryRequests)
              .getSingle())
          .status,
      'confirmed',
    );
  });

  test('successful mutation replaces an older staged catalog with a fresh snapshot', () async {
    final repo = c.repository!;
    await repo.startDownload(incremental: false);
    await repo.stage(snapshot());
    final id = await c.prepareInventoryRequest(
      'inventory/10/add',
      'inventory.add',
      {'cantidad': '1.00'},
    );
    await c.retryInventoryRequest(id);
    expect((await repo.products('10')).single.estimatedQuantity, 1100);
  });

  test(
    'confirmed write remains confirmed when subsequent catalog download fails',
    () async {
      final id = await c.prepareInventoryRequest(
        'inventory/10/add',
        'inventory.add',
        {'cantidad': '2.00'},
      );
      api.failDownload = true;
      final result = await c.retryInventoryRequest(id);
      expect(result['syncWarning'], contains('Guardado en el servidor'));
      expect(
        (await c.repository!.db
                .select(c.repository!.db.inventoryRequests)
                .getSingle())
            .status,
        'confirmed',
      );
      await c.retryInventoryRequest(id);
      expect(api.mutations, 1);
      api.failDownload = false;
      await c.synchronize();
      expect(
        (await c.repository!.products('10')).single.estimatedQuantity,
        1200,
      );
    },
  );

  test('stale product rejects mutation and disconnect prevents preparing a new write', () async {
    final id = await c.prepareInventoryRequest(
      'inventory/10/prices',
      'product.prices.update',
      {'precio_ieps': '60.00', 'version': 'a' * 64},
    );
    api.stale = true;
    await expectLater(c.retryInventoryRequest(id), throwsException);
    expect(
      (await c.repository!.db
              .select(c.repository!.db.inventoryRequests)
              .getSingle())
          .status,
      'rejected',
    );
    expect(api.mutations, 0);
    c.online = false;
    await expectLater(
      c.prepareInventoryRequest('inventory/10/add', 'inventory.add', {
        'cantidad': '1.00',
      }),
      throwsException,
    );
    expect(
      (await c.repository!.db.select(c.repository!.db.inventoryRequests).get())
          .length,
      1,
    );
  });

  for (final action in ['reset', 'delete']) {
    test(
      '$action lost response survives restart and does not repeat the destructive action',
      () async {
        api.permissions!.addAll(['inventory.reset', 'product.delete']);
        await c.checkAccess();
        final preview = await c.inventoryDestructivePreview('10', action);
        final id = await c.prepareInventoryRequest(
          'inventory/10/$action',
          action == 'delete' ? 'product.delete' : 'inventory.reset',
          {
            'version': preview['version'],
            'stock_revision': preview['stockRevision'],
            'confirmed': true,
          },
        );
        api.loseReply = true;
        await expectLater(c.retryInventoryRequest(id), throwsException);
        expect(api.mutations, 1);
        if (action == 'reset') api.products['10']!['serverQuantity'] = '3.00';
        c.dispose();
        await c.repository!.db.close();
        c = controller();
        await c.initialize();
        await c.checkAccess();
        await c.retryInventoryRequest(id);
        expect(api.mutations, 1);
        expect(api.mutationBodies[0], api.mutationBodies[1]);
        final products = await c.repository!.products('10');
        if (action == 'delete') {
          expect(products, isEmpty);
        } else {
          expect(products.single.estimatedQuantity, 300);
        }
      },
    );
  }

  test(
    'destructive preview rejects inventory employees and offline access',
    () async {
      await expectLater(
        c.inventoryDestructivePreview('10', 'delete'),
        throwsException,
      );
      api.permissions!.add('inventory.reset');
      await c.checkAccess();
      c.online = false;
      await expectLater(
        c.inventoryDestructivePreview('10', 'reset'),
        throwsException,
      );
      expect(
        await c.repository!.db.select(c.repository!.db.inventoryRequests).get(),
        isEmpty,
      );
    },
  );

  test('v5 migration preserves products and creates durable online inventory requests', () async {
    c.dispose();
    await c.repository!.db.close();
    final legacy = PosDatabase(
      NativeDatabase(File('${folder.path}/pos.sqlite')),
      c.session!.contextId,
    );
    await legacy.customStatement('DROP TABLE inventory_requests');
    await legacy.customStatement('PRAGMA user_version=5');
    await legacy.close();
    c = controller();
    await c.initialize();
    await c.checkAccess();
    expect(
      (await c.repository!.products('10')).single.name,
      'Semillas de maíz',
    );
    final id = await c.prepareInventoryRequest(
      'inventory/10/add',
      'inventory.add',
      {'cantidad': '1.00'},
    );
    expect(
      (await c.repository!.db
              .select(c.repository!.db.inventoryRequests)
              .getSingle())
          .operationId,
      id,
    );
  });
}
