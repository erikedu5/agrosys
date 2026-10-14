import 'dart:convert';
import 'dart:io';

import 'package:agrosys_pos/core/app_controller.dart';
import 'package:agrosys_pos/core/session_vault.dart';
import 'package:agrosys_pos/data/database.dart';
import 'package:agrosys_pos/features/sales/sale_domain.dart';
import 'package:drift/native.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:uuid/uuid.dart';

import 'support/cash_server.dart';
import 'support/fixtures.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  late Directory dir;
  late CashServer api;
  late AppController c;
  late MemorySecretStore secrets;
  AppController controller() => AppController(
    enableAutomaticSync: false,
    vault: SessionVault(secrets),
    apiFactory: (_) => api,
    openDatabase: (id) async =>
        PosDatabase(NativeDatabase(File('${dir.path}/pos.sqlite')), id),
  );
  setUp(() async {
    dir = await Directory.systemTemp.createTemp('cash-tests-');
    secrets = MemorySecretStore();
    api = CashServer();
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
    await dir.delete(recursive: true);
  });
  Future<String> openCash() async {
    final id = await c.prepareInventoryRequest('cash/open', 'cash.manage', {
      'opening': '200.00',
    });
    await c.retryInventoryRequest(id);
    return api.current!['id'] as String;
  }

  test(
    'sales are blocked before opening and after the cashier closes cash',
    () async {
      Future<void> rejectedSale() async {
        final quote = await c.sales!.quote('20', [const CartLine('10', 100)]);
        final saved = await c.completeSale(
          SaleRequest(
            saleId: const Uuid().v4(),
            customerId: '20',
            type: 'Contado',
            lines: [const CartLine('10', 100)],
            initialCents: 0,
            receivedCents: 10000,
            expectedPriceFingerprint: quote.priceFingerprint,
          ),
        );
        expect(saved, isNull);
        expect(c.error, contains('Abre la caja'));
        expect(
          await c.repository!.db.select(c.repository!.db.localSales).get(),
          isEmpty,
        );
      }

      expect(c.canCompleteSale, isFalse);
      await rejectedSale();
      final cashId = await openCash();
      expect(c.canCompleteSale, isTrue);
      final id = await c.prepareInventoryRequest('cash/close', 'cash.manage', {
        'session_id': cashId,
        'expected': '200.00',
        'counted': '200.00',
        'confirmed': true,
      });
      await c.retryInventoryRequest(id);
      expect(c.canCompleteSale, isFalse);
      await rejectedSale();
    },
  );
  test('lost open response survives restart and preserves cashier context without duplicate opening', () async {
    final id = await c.prepareInventoryRequest('cash/open', 'cash.manage', {
      'opening': '200.00',
    });
    api.loseReply = true;
    await expectLater(c.retryInventoryRequest(id), throwsException);
    c.dispose();
    await c.repository!.db.close();
    c = controller();
    await c.initialize();
    expect(
      (await c.repository!.db
              .select(c.repository!.db.inventoryRequests)
              .getSingle())
          .status,
      'pending',
    );
    api.disconnected = false;
    await c.checkAccess();
    await c.retryInventoryRequest(id);
    expect(api.mutations, 1);
    expect((await c.cachedCash())['current']['opening'], '200.00');
  });
  test('offline sale keeps session and prevents cut until confirmed', () async {
    final cashId = await openCash();
    c.online = false;
    api.disconnected = true;
    final quote = await c.sales!.quote('20', [const CartLine('10', 100)]);
    final sale = await c.completeSale(
      SaleRequest(
        saleId: const Uuid().v4(),
        customerId: '20',
        type: 'Contado',
        lines: [const CartLine('10', 100)],
        initialCents: 0,
        receivedCents: 10000,
        expectedPriceFingerprint: quote.priceFingerprint,
      ),
    );
    expect(sale, isNotNull);
    final queued = await c.repository!.db
        .select(c.repository!.db.outbox)
        .getSingle();
    expect(
      (jsonDecode(queued.payload)['payload'] as Map)['cashSessionId'],
      cashId,
    );
    c.online = true;
    api.disconnected = false;
    final id = await c.prepareInventoryRequest('cash/close', 'cash.manage', {
      'session_id': cashId,
      'expected': '200.00',
      'counted': '200.00',
      'confirmed': true,
    });
    await expectLater(c.retryInventoryRequest(id), throwsException);
    expect(api.history, isEmpty);
    expect(api.mutations, 1);
  });
  test('lost close acknowledgement is retryable and caches a printable immutable cut', () async {
    final cashId = await openCash();
    final id = await c.prepareInventoryRequest('cash/close', 'cash.manage', {
      'session_id': cashId,
      'expected': '200.00',
      'counted': '199.00',
      'notes': 'Faltante',
      'confirmed': true,
    });
    api.loseReply = true;
    await expectLater(c.retryInventoryRequest(id), throwsException);
    api.disconnected = false;
    c.online = true;
    await c.retryInventoryRequest(id);
    expect(api.mutations, 2);
    final cached = await c.cachedCash();
    expect(cached['current'], isNull);
    expect((cached['history'] as List).single['difference'], '-1.00');
    await c.retryInventoryRequest(id);
    expect(api.mutations, 2);
  });
  test('invalid cash acknowledgement retains pending identity', () async {
    api.invalidReply = true;
    final id = await c.prepareInventoryRequest('cash/open', 'cash.manage', {
      'opening': '200.00',
    });
    await expectLater(c.retryInventoryRequest(id), throwsException);
    expect(
      (await c.repository!.db
              .select(c.repository!.db.inventoryRequests)
              .getSingle())
          .status,
      'pending',
    );
    expect((await c.cachedCash())['current'], isNull);
  });
  test(
    'v6 migration preserves original queued sales and introduces cash state',
    () async {
      await openCash();
      final quote = await c.sales!.quote('20', [const CartLine('10', 100)]);
      await c.completeSale(
        SaleRequest(
          saleId: const Uuid().v4(),
          customerId: '20',
          type: 'Contado',
          lines: [const CartLine('10', 100)],
          initialCents: 0,
          receivedCents: 10000,
          expectedPriceFingerprint: quote.priceFingerprint,
        ),
      );
      final original =
          (await c.repository!.db.select(c.repository!.db.outbox).getSingle())
              .payload;
      await c.repository!.db.customStatement('DROP TABLE cash_local_state');
      await c.repository!.db.customStatement('PRAGMA user_version=6');
      c.dispose();
      await c.repository!.db.close();
      c = controller();
      await c.initialize();
      expect(
        (await c.repository!.db.select(c.repository!.db.outbox).getSingle())
            .payload,
        original,
      );
      expect((await c.cachedCash())['current'], isNull);
    },
  );
}
