import 'dart:io';

import 'package:agrosys_pos/core/app_controller.dart';
import 'package:agrosys_pos/core/session_vault.dart';
import 'package:agrosys_pos/data/database.dart';
import 'package:drift/native.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/fixtures.dart';
import 'support/workflow_server.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  late Directory folder;
  late WorkflowServer api;
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
    folder = await Directory.systemTemp.createTemp('stock-workflows-');
    api = WorkflowServer();
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
  final purchase = {
    'supplier': 'Proveedor',
    'date': '2026-10-08',
    'payment': '5.00',
    'items': [
      {'productId': '10', 'quantity': '1.25', 'cost': '20.10'},
    ],
  };
  for (final action in ['purchase', 'payment', 'transfer', 'receive']) {
    test(
      '$action survives lost response and restart without duplicate stock or payments',
      () async {
        String path, permission;
        Map<String, dynamic> payload;
        if (action == 'payment') {
          final id = await c.prepareInventoryRequest(
            'purchases/create',
            'purchase.create',
            purchase,
          );
          await c.retryInventoryRequest(id);
          path = 'purchases/1/payment';
          permission = 'purchase.payment';
          payload = {'amount': '10.00', 'expected_debt': '20.13'};
        } else if (action == 'purchase') {
          path = 'purchases/create';
          permission = 'purchase.create';
          payload = purchase;
        } else if (action == 'transfer') {
          path = 'transfers/create';
          permission = 'transfer.create';
          payload = {
            'destination_id': '2',
            'notes': 'Insumos',
            'items': [
              {'productId': '10', 'quantity': '2.50'},
            ],
          };
        } else {
          api.transfers['99'] = api.incoming();
          path = 'transfers/99/receive';
          permission = 'transfer.receive';
          payload = {'confirmed': true};
        }
        final id = await c.prepareInventoryRequest(path, permission, payload);
        final before = api.mutations;
        api.loseReply = true;
        await expectLater(c.retryInventoryRequest(id), throwsException);
        expect(api.mutations, before + 1);
        final sent = api.mutationBodies.last;
        c.dispose();
        await c.repository!.db.close();
        c = controller();
        await c.initialize();
        await c.checkAccess();
        final result = await c.retryInventoryRequest(id);
        expect(result['status'], 'confirmed');
        expect(api.mutations, before + 1);
        expect(api.mutationBodies.last, sent);
        final requests = await c.repository!.db
            .select(c.repository!.db.inventoryRequests)
            .get();
        expect(requests.every((row) => row.status == 'confirmed'), isTrue);
        if (action == 'purchase' || action == 'payment') {
          expect(api.purchases, hasLength(1));
          expect(
            api.purchases['1']!['debt'],
            action == 'payment' ? '10.13' : '20.13',
          );
          expect(
            (await c.repository!.products('10')).single.estimatedQuantity,
            1125,
          );
        } else if (action == 'receive') {
          expect(api.transfers['99']!['status'], 'completado');
          expect(
            (await c.repository!.products('10')).single.estimatedQuantity,
            1250,
          );
        } else {
          expect(api.transfers, hasLength(1));
          expect(
            (await c.repository!.products('10')).single.estimatedQuantity,
            1000,
          );
        }
      },
    );
  }
  test(
    'purchase and transfer reads enforce live permissions and connection',
    () async {
      api.permissions!.removeWhere((v) => v.startsWith('purchase.'));
      await c.checkAccess();
      await expectLater(
        c.stockWorkflowData('purchases/list', 'purchase.read'),
        throwsException,
      );
      c.online = false;
      await expectLater(
        c.stockWorkflowData('transfers/list', 'transfer.read'),
        throwsException,
      );
    },
  );
}
