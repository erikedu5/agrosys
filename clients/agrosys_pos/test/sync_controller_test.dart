import 'dart:async';
import 'dart:io';

import 'package:agrosys_pos/core/app_controller.dart';
import 'package:agrosys_pos/core/session_vault.dart';
import 'package:agrosys_pos/data/database.dart';
import 'package:agrosys_pos/features/sales/sale_domain.dart';
import 'package:drift/native.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:uuid/uuid.dart';

import 'support/fixtures.dart';
import 'support/sync_server.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  late Directory dir;
  late SyncFixtureApi api;
  late MemorySecretStore store;
  late AppController controller;
  AppController make() => AppController(
    vault: SessionVault(store),
    openDatabase: (id) async =>
        PosDatabase(NativeDatabase(File('${dir.path}/$id.sqlite')), id),
    apiFactory: (_) => api,
  );
  Future<void> waitFor(bool Function() condition) async {
    for (var i = 0; i < 500; i++) {
      if (condition()) return;
      await Future<void>.delayed(const Duration(milliseconds: 10));
    }
    fail('Automatic sync did not complete within 5 seconds');
  }

  Future<void> sale() async {
    final q = await controller.sales!.quote('20', [const CartLine('10', 100)]);
    expect(
      await controller.completeSale(
        SaleRequest(
          saleId: const Uuid().v4(),
          customerId: '20',
          type: 'Contado',
          lines: [const CartLine('10', 100)],
          initialCents: 0,
          receivedCents: 5000,
          expectedPriceFingerprint: q.priceFingerprint,
        ),
      ),
      isNotNull,
    );
  }

  setUp(() async {
    dir = await Directory.systemTemp.createTemp('agrosys-auto-');
    api = SyncFixtureApi();
    store = MemorySecretStore();
    controller = make();
    await controller.initialize();
    await controller.signIn(
      'https://pos.example/api/v1/pos',
      'qa@example.com',
      'password',
    );
    await controller.selectBranch(controller.branches.single);
    expect(controller.error, isNull);
  });
  tearDown(() async {
    await controller.signOut();
    controller.dispose();
    api.central.close();
    await dir.delete(recursive: true);
  });
  test('automatic sale trigger uploads while another offline checkout remains available', () async {
    api.central.gate = Completer();
    await sale();
    await waitFor(() => api.central.calls == 1);
    expect(controller.syncBusy, true);
    expect(controller.busy, false);
    await sale();
    expect((await controller.sales!.history()).length, 2);
    api.central.gate!.complete();
    await waitFor(() => !controller.syncBusy);
    expect(api.central.results.length, 2);
    expect(
      (await controller.sales!.history()).every((s) => s.status == 'confirmed'),
      true,
    );
  });
  test(
    'startup and foreground network recovery send persisted identities',
    () async {
      controller.setForeground(false);
      await sale();
      final before = await controller.repository!.db
          .select(controller.repository!.db.outbox)
          .getSingle();
      final old = controller;
      controller = make();
      old.dispose();
      await controller.initialize();
      await waitFor(
        () => api.central.results.length == 1 && !controller.syncBusy,
      );
      expect(
        (await controller.repository!.db
                .select(controller.repository!.db.outbox)
                .getSingle())
            .payload,
        before.payload,
      );
      controller.setForeground(false);
      await sale();
      api.disconnected = true;
      controller.setForeground(true);
      await controller.checkAccess();
      expect(controller.online, false);
      expect(controller.canSell, true);
      api.disconnected = false;
      await controller.checkAccess();
      await waitFor(
        () => api.central.results.length == 2 && !controller.syncBusy,
      );
    },
  );
  test(
    'changing login server cannot send an old context queue to the new API',
    () async {
      controller.setForeground(false);
      await sale();
      await controller.signIn(
        'https://other.example/api/v1/pos',
        'other@example.com',
        'password',
      );
      await controller.checkAccess();
      await controller.synchronize();
      expect(api.central.calls, 0);
      expect(
        (await controller.repository!.db
                .select(controller.repository!.db.outbox)
                .getSingle())
            .status,
        'pending',
      );
    },
  );
  test('logout during upload preserves pending data and never writes a stale confirmation', () async {
    api.central.gate = Completer();
    await sale();
    await waitFor(() => api.central.calls == 1);
    final db = controller.repository!.db;
    final file = File('${dir.path}/${controller.session!.contextId}.sqlite');
    final out = controller.signOut();
    api.central.gate!.complete();
    await out;
    expect(controller.session, isNull);
    expect(await file.exists(), true);
    expect(controller.repository, isNull);
    // Same device and context can reauthenticate and query the interrupted operation.
    await controller.signIn(
      'https://pos.example/api/v1/pos',
      'qa@example.com',
      'password',
    );
    await controller.selectBranch(controller.branches.single);
    await waitFor(() => !controller.syncBusy && controller.generation > 0);
    await controller.synchronize();
    expect(api.central.results.length, 1);
    expect((await controller.sales!.history()).single.status, 'confirmed');
    expect(identical(db, controller.repository!.db), false);
  });
}
