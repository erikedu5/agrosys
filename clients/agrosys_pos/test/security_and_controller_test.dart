import 'dart:io';

import 'package:agrosys_pos/app.dart';
import 'package:agrosys_pos/core/app_controller.dart';
import 'package:agrosys_pos/core/pos_api.dart';
import 'package:agrosys_pos/core/session_vault.dart';
import 'package:agrosys_pos/data/database.dart';
import 'package:drift/native.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/fixtures.dart';

class _UnavailableKeychain extends MemorySecretStore {
  _UnavailableKeychain() {
    values['active_session'] = 'private-token';
  }
  @override
  Future<String?> read(String key) async => throw PlatformException(
    code: 'Unexpected security result code',
    message: 'private-token',
    details: -25293,
  );
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  test('incompatible restored server can sign out without deleting context data', () async {
    final store = MemorySecretStore();
    final vault = SessionVault(store);
    await vault.save(fixtureSession());
    store.values['device_saved'] = 'original-device';
    var opened = false;
    final c = AppController(
      enableAutomaticSync: false,
      vault: vault,
      apiFactory: (_) => throw const FormatException('La versión de producción requiere HTTPS.'),
      openDatabase: (_) async {
        opened = true;
        return PosDatabase(NativeDatabase.memory(), fixtureSession().contextId);
      },
    );
    await c.initialize();
    expect(c.storageFailure, isTrue);
    expect(c.startupSessionFailure, isTrue);
    expect(c.error, contains('HTTPS'));
    expect(opened, isFalse);
    expect(await vault.load(), isNotNull);
    await c.signOut();
    expect(c.storageFailure, isFalse);
    expect(c.startupSessionFailure, isFalse);
    expect(c.session, isNull);
    expect(c.apiUrl, AppController.configuredApiUrl);
    expect(await vault.load(), isNull);
    expect(store.values['device_saved'], 'original-device');
    expect(opened, isFalse);
    c.dispose();
  });
  test(
    'Keychain failure reports only status and preserves credentials',
    () async {
      final store = _UnavailableKeychain();
      final c = AppController(
        enableAutomaticSync: false,
        vault: SessionVault(store),
      );
      await c.initialize();
      expect(c.storageFailure, isTrue);
      expect(c.canConsult, isFalse);
      expect(c.error, contains('llavero'));
      expect(c.error, contains('-25293'));
      expect(c.error, isNot(contains('private-token')));
      expect(store.values['active_session'], 'private-token');
      c.dispose();
    },
  );
  test('AC-01/02 tokens are secured; passwords never persisted; device identity survives logout', () async {
    final store = MemorySecretStore();
    final vault = SessionVault(store);
    final device = await vault.deviceFor('https://server', '1', '1');
    final session = fixtureSession(lease: await signedLease(fixtureSession()));
    await vault.verifyLease(session, session.lease!);
    await vault.save(session);
    expect((await vault.load())!.contextId, session.contextId);
    await vault.lock();
    expect(await vault.load(), isNull);
    expect(await vault.deviceFor('https://server', '1', '1'), device);
    expect(await vault.deviceFor('https://server', '2', '1'), isNot(device));
    expect(await vault.deviceFor('https://other', '1', '1'), isNot(device));
    expect(store.values.values.any((v) => v.contains('password')), isFalse);
  });
  test(
    'AC-10/14 signature, claim ownership and server key rotation are verified',
    () async {
      final vault = SessionVault(MemorySecretStore());
      final session = fixtureSession();
      final lease = await signedLease(session);
      await vault.verifyLease(session, lease);
      await expectLater(
        vault.verifyLease(fixtureSession(user: 'other'), lease),
        throwsFormatException,
      );
      await expectLater(
        vault.verifyLease(session, {...lease, 'payload': 'altered'}),
        throwsFormatException,
      );
      await expectLater(
        vault.verifyLease(session, await signedLease(session)),
        throwsFormatException,
      );
    },
  );
  test('production requires HTTPS and forbids credential-bearing URLs', () {
    expect(
      () => normalizeApiUrl('http://server', allowDevelopmentHttp: false),
      throwsFormatException,
    );
    expect(
      () => normalizeApiUrl('https://user:password@server'),
      throwsFormatException,
    );
    expect(
      normalizeApiUrl('https://server/api/v1/pos/'),
      'https://server/api/v1/pos',
    );
  });
  test(
    'AC-01/02 login, activation, bootstrap and offline reopen preserve context',
    () async {
      final directory = await Directory.systemTemp.createTemp(
        'agrosys-controller-',
      );
      final store = MemorySecretStore();
      final api = FixtureApi();
      PosDatabase open(String id) =>
          PosDatabase(NativeDatabase(File('${directory.path}/$id.sqlite')), id);
      final controller = AppController(
        enableAutomaticSync: false,
        vault: SessionVault(store),
        openDatabase: (id) async => open(id),
        apiFactory: (_) => api,
      );
      await controller.initialize();
      expect(controller.canConsult, isFalse);
      await controller.signIn(
        'https://pos.example/api/v1/pos',
        'test@example.com',
        'private-password',
      );
      expect(controller.signInStep, SignInStep.branch);
      await controller.selectBranch(controller.branches.single);
      expect(controller.error, isNull);
      expect(controller.canConsult, isTrue);
      final context = controller.session!.contextId;
      expect(
        store.values.values.any((v) => v.contains('private-password')),
        isFalse,
      );
      await controller.repository!.db.close();
      controller.dispose();
      api.disconnected = true;
      final restored = AppController(
        enableAutomaticSync: false,
        vault: SessionVault(store),
        openDatabase: (id) async => open(id),
        apiFactory: (_) => api,
      );
      await restored.initialize();
      await restored.checkAccess();
      expect(restored.session!.contextId, context);
      expect(restored.canConsult, isTrue);
      expect((await restored.repository!.products('maiz')).single.id, '10');
      await restored.signOut();
      expect(restored.canConsult, isFalse);
      expect(await SessionVault(store).load(), isNull);
      expect(await File('${directory.path}/$context.sqlite').exists(), isTrue);
      restored.dispose();
      await directory.delete(recursive: true);
    },
  );
  test(
    'AC-10 server revocation persists a local lock across restart',
    () async {
      final directory = await Directory.systemTemp.createTemp(
        'agrosys-revoke-',
      );
      final store = MemorySecretStore();
      final api = FixtureApi();
      Future<PosDatabase> open(String id) async =>
          PosDatabase(NativeDatabase(File('${directory.path}/$id.sqlite')), id);
      final c = AppController(
        enableAutomaticSync: false,
        vault: SessionVault(store),
        openDatabase: open,
        apiFactory: (_) => api,
      );
      await c.initialize();
      await c.signIn(
        'https://pos.example/api/v1/pos',
        'test@example.com',
        'password',
      );
      await c.selectBranch(c.branches.single);
      api.deniedStatus = 403;
      await c.checkAccess();
      expect(c.session!.blocked, isTrue);
      expect(c.canConsult, isFalse);
      await c.repository!.db.close();
      c.dispose();
      api.deniedStatus = null;
      api.disconnected = true;
      final restarted = AppController(
        enableAutomaticSync: false,
        vault: SessionVault(store),
        openDatabase: open,
        apiFactory: (_) => api,
      );
      await restarted.initialize();
      await restarted.checkAccess();
      expect(restarted.canConsult, isFalse);
      expect((await restarted.repository!.products('')).single.id, '10');
      await restarted.repository!.db.close();
      restarted.dispose();
      await directory.delete(recursive: true);
    },
  );
  test('AC-01/02 second factor precedes branch activation and credential persistence', () async {
    final store = MemorySecretStore();
    final api = FixtureApi()..requiresTwoFactor = true;
    final c = AppController(
      enableAutomaticSync: false,
      vault: SessionVault(store),
      apiFactory: (_) => api,
    );
    await c.initialize();
    await c.signIn(
      'https://pos.example/api/v1/pos',
      'test@example.com',
      'password',
    );
    expect(c.signInStep, SignInStep.secondFactor);
    expect(await c.vault.load(), isNull);
    await c.verifySecondFactor('123456', false);
    expect(c.signInStep, SignInStep.branch);
    expect(api.calls, ['auth/login', 'auth/verify-2fa']);
    c.dispose();
  });
  testWidgets(
    'AC-01 responsive login explains first activation without overflowing',
    (tester) async {
      final c = AppController(
        enableAutomaticSync: false,
        vault: SessionVault(MemorySecretStore()),
        apiFactory: (_) => FixtureApi(),
      );
      await c.initialize();
      await tester.binding.setSurfaceSize(const Size(360, 740));
      await tester.pumpWidget(
        ProviderScope(
          overrides: [appControllerProvider.overrideWithValue(c)],
          child: const AgrosysPosApp(),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text('Accede a tu sucursal'), findsOneWidget);
      expect(tester.takeException(), isNull);
      await tester.binding.setSurfaceSize(const Size(1200, 800));
      await tester.pumpAndSettle();
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox());
      c.dispose();
      await tester.binding.setSurfaceSize(null);
    },
  );
}
