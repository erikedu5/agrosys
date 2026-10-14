import 'package:agrosys_pos/core/app_controller.dart';
import 'package:agrosys_pos/core/models.dart';
import 'package:agrosys_pos/core/pos_api.dart';
import 'package:agrosys_pos/core/session_vault.dart';
import 'package:agrosys_pos/data/database.dart';
import 'package:drift/native.dart';
import 'package:cryptography/cryptography.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/fixtures.dart';

class BranchApi extends FixtureApi {
  bool rejectSwitch = false;
  @override
  Future<Map<String, dynamic>> request(
    String path, {
    Map<String, dynamic>? body,
    String? token,
    String method = 'POST',
  }) async {
    if (path == 'auth/context' && rejectSwitch) {
      throw ApiFailure(403, 'El dispositivo requiere autorización manual.');
    }
    if (path == 'auth/context' && !disconnected) {
      final response = await super.request(path, body: body, token: token, method: method);
      final activation = await request('devices/activate', body: body, token: token);
      return {...response, ...activation};
    }
    if (path == 'branches' && !disconnected) {
      expect(method, 'GET');
      return {
        'branches': [
          {'id': '1', 'companyId': '1', 'name': 'Centro'},
          {'id': '2', 'companyId': '1', 'name': 'Norte'},
        ],
      };
    }
    if (path == 'devices/activate') {
      return {
        'offlineLease': await signedLease(
          Session(
            apiUrl: fixtureSession().apiUrl,
            userId: '1',
            userName: 'Operador',
            deviceId: body!['device_id'] as String,
            token: 'test-token',
            branch: Branch('2', '1', 'Norte'),
            tokenExpiresAt: DateTime.now().add(const Duration(days: 30)),
          ),
          pair: key,
        ),
      };
    }
    if (path == 'bootstrap') {
      return {
        ...snapshot(products: [], customers: []),
        'branch': {'id': '2', 'companyId': '1', 'name': 'Norte'},
      };
    }
    return super.request(path, body: body, token: token, method: method);
  }
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  test(
    'switch uses authorized branch and separate persisted context',
    () async {
      final vault = SessionVault(MemorySecretStore());
      final pair = await Ed25519().newKeyPair();
      final original = fixtureSession(
        lease: await signedLease(fixtureSession(), pair: pair),
      );
      await vault.save(original);
      final api = BranchApi()..key = pair;
      final opened = <String>[];
      final c = AppController(
        vault: vault,
        enableAutomaticSync: false,
        apiFactory: (_) => api,
        openDatabase: (context) async {
          opened.add(context);
          return PosDatabase(NativeDatabase.memory(), context);
        },
      );
      await c.initialize();
      await c.loadBranches();
      api.disconnected = true;
      await c.changeBranch(c.branches.last);
      expect(c.session!.contextId, original.contextId);
      api.disconnected = false;
      api.rejectSwitch = true;
      await c.changeBranch(c.branches.last);
      expect(c.error, contains('autorización manual'));
      expect(c.session!.contextId, original.contextId);
      expect(c.session!.blocked, isFalse);
      expect((await vault.load())!.contextId, original.contextId);
      api.rejectSwitch = false;
      await c.changeBranch(c.branches.last);
      expect(c.error, isNull);
      expect(c.session!.branch.id, '2');
      expect(c.session!.contextId, isNot(original.contextId));
      expect((await vault.load())!.branch.id, '2');
      expect(c.canConsult, isTrue);
      expect(opened.first, original.contextId);
      expect(opened.last, c.session!.contextId);
      final exchanges = api.calls
          .where((path) => path == 'auth/context')
          .length;
      await c.changeBranch(c.session!.branch);
      expect(
        api.calls.where((path) => path == 'auth/context').length,
        exchanges,
      );
      await c.changeBranch(Branch('3', '2', 'Otra empresa'));
      expect(c.error, contains('autorizada'));
      expect(c.session!.branch.id, '2');
      c.dispose();
    },
  );
}
