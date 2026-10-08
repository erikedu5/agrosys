import 'dart:async';

import 'package:flutter/foundation.dart';

import 'models.dart';
import 'pos_api.dart';
import 'session_vault.dart';
import '../data/database.dart';
import '../data/catalog_repository.dart';
import '../data/catalog_sync.dart';
import '../features/sales/sale_domain.dart';
import '../features/sync/sync_worker.dart' as sync;
import '../features/sales/sales_repository.dart';

enum SignInStep { credentials, secondFactor, branch }

class AppController extends ChangeNotifier {
  final SessionVault vault;
  final bool enableAutomaticSync;
  final Future<PosDatabase> Function(String) openDatabase;
  final PosApi Function(String) apiFactory;
  AppController({
    required this.vault,
    this.enableAutomaticSync = true,
    Future<PosDatabase> Function(String)? openDatabase,
    PosApi Function(String)? apiFactory,
  }) : openDatabase = openDatabase ?? PosDatabase.open,
       apiFactory = apiFactory ?? ((url) => PosApi(url));
  Session? session;
  CatalogRepository? repository;
  PosApi? _api;
  CatalogSync? _sync;
  SignInStep signInStep = SignInStep.credentials;
  List<Branch> branches = [];
  Map<String, dynamic>? _credentials;
  String? _challenge;
  String apiUrl = const String.fromEnvironment(
    'POS_API_URL',
    defaultValue: 'http://localhost:8000/api/v1/pos',
  );
  String? error;
  bool busy = false, online = false, starting = true, storageFailure = false;
  int downloadedPages = 0, generation = 0;
  SyncInfo? syncInfo;
  Timer? _expiryTimer, _networkTimer, _saleSyncTimer;
  Future<void>? _syncRun;
  int _syncEpoch = 0;
  bool syncBusy = false, _foreground = true;
  void setForeground(bool value) {
    _foreground = value;
    if (value) unawaited(checkAccess());
  }

  void _scheduleSync() {
    if (!enableAutomaticSync ||
        !_foreground ||
        _disposed ||
        session == null ||
        session!.blocked) {
      return;
    }
    _saleSyncTimer?.cancel();
    _saleSyncTimer = Timer(const Duration(seconds: 1), () {
      unawaited(_runSync(manual: false));
    });
  }

  Future<void> _stopSync() async {
    _syncEpoch++;
    _saleSyncTimer?.cancel();
    await _syncRun;
  }

  bool _disposed = false;
  @override
  void notifyListeners() {
    if (!_disposed) super.notifyListeners();
  }

  bool get canConsult =>
      session?.hasOfflineAccess == true &&
      repository != null &&
      syncInfo?.revision != null &&
      !storageFailure;
  SalesRepository? get sales => repository == null || session == null
      ? null
      : SalesRepository(repository!, session!);
  bool get canSell =>
      canConsult && session!.permissions.contains('sale.create');
  Future<StoredReceipt?> completeSale(SaleRequest request) async {
    StoredReceipt? result;
    await _action(() async {
      if (!canSell) {
        throw const SaleInputException('No hay autorización para vender.');
      }
      await vault.verifyLease(session!, session!.lease!);
      result = await sales!.complete(request);
      await _refreshInfo();
    });
    if (result != null) _scheduleSync();
    return result;
  }

  bool get needsActivation => session != null && !canConsult;

  Future<void> initialize() async {
    try {
      session = await vault.load();
      if (session != null) {
        apiUrl = normalizeApiUrl(session!.apiUrl);
        _api = apiFactory(apiUrl);
        if (session!.lease != null) {
          await vault.verifyLease(session!, session!.lease!);
        }
        await _openRepository();
      }
    } catch (e) {
      storageFailure = true;
      error = 'No se pudo abrir el almacenamiento seguro o local. Los datos se conservaron. Reintenta o solicita soporte.';
    } finally {
      starting = false;
      _expiryTimer = Timer.periodic(const Duration(minutes: 1), (_) {
        notifyListeners();
      });
      if (enableAutomaticSync) {
        _networkTimer = Timer.periodic(const Duration(seconds: 30), (_) {
          if (_foreground && !_disposed) unawaited(checkAccess());
        });
      }
      notifyListeners();
    }
    if (session != null && !storageFailure) unawaited(checkAccess());
  }

  Future<void> _openRepository() async {
    await _stopSync();
    final old = repository;
    repository = null;
    if (old != null) await old.db.close();
    final db = await openDatabase(session!.contextId);
    try {
      final repo = CatalogRepository(db, session!);
      syncInfo = await repo.info();
      if (await repo.isLocked()) session = session!.copyWith(blocked: true);
      if (!await repo.observeClock(DateTime.now())) {
        session = session!.copyWith(blocked: true);
        await vault.save(session!);
        error = 'El reloj del dispositivo retrocedió. Corrígelo y renueva la autorización online.';
      }
      repository = repo;
      _sync = CatalogSync(
        _api!,
        repo,
        onProgress: (pages) {
          downloadedPages = pages;
          notifyListeners();
        },
      );
      generation++;
    } catch (_) {
      await db.close();
      rethrow;
    }
  }

  Future<void> signIn(String url, String email, String password) async {
    await _action(() async {
      await _stopSync();
      apiUrl = normalizeApiUrl(url);
      _api?.close();
      _api = apiFactory(apiUrl);
      final device = await vault.deviceFor(
        apiUrl,
        email.trim().toLowerCase(),
        '_login',
      );
      final response = await _api!.login(email.trim(), password, device);
      if (response['requiresTwoFactor'] == true) {
        _challenge = response['challenge'] as String;
        signInStep = SignInStep.secondFactor;
      } else {
        _acceptCredentials(response);
      }
    });
  }

  void _acceptCredentials(Map<String, dynamic> response) {
    _credentials = response;
    branches = (response['branches'] as List)
        .map((raw) => Branch.fromJson(object(raw)))
        .toList();
    signInStep = SignInStep.branch;
    _challenge = null;
    online = true;
  }

  Future<void> verifySecondFactor(String code, bool recovery) =>
      _action(() async {
        _acceptCredentials(
          await _api!.verify(_challenge!, code.trim(), recovery),
        );
      });
  Future<void> selectBranch(Branch branch) => _action(() async {
    final user = object(_credentials!['user']);
    final device = await vault.deviceFor(
      apiUrl,
      user['id'] as String,
      branch.id,
    );
    final response = await _api!.exchange(_credentials!['token'] as String, {
      'device_id': device,
      'branch_id': branch.id,
    });
    session = Session(
      apiUrl: apiUrl,
      userId: user['id'] as String,
      userName: user['name'] as String,
      deviceId: device,
      token: response['token'] as String,
      branch: branch,
      tokenExpiresAt: DateTime.parse(response['expiresAt'] as String),
    );
    await vault.save(session!);
    _credentials = null;
    signInStep = SignInStep.credentials;
    await _openRepository();
    await _activateAndDownload();
  });
  Future<void> _activateAndDownload() async {
    await _stopSync();
    final result = await _api!.activate(session!);
    final lease = object(result['offlineLease']);
    await vault.verifyLease(session!, lease);
    session = session!.copyWith(lease: lease, blocked: false);
    await vault.save(session!);
    await repository?.markRevalidated();
    await _openRepository();
    await _sync!.synchronize();
    await _refreshInfo();
    online = true;
    _scheduleSync();
  }

  Future<void> activate() => _action(_activateAndDownload);
  Future<void> renew() => _action(() async {
    await _stopSync();
    final result = await _api!.renew(session!);
    final lease = object(result['offlineLease']);
    await vault.verifyLease(session!, lease);
    session = session!.copyWith(lease: lease, blocked: false);
    await vault.save(session!);
    await repository?.markRevalidated();
    await _openRepository();
    await _sync!.synchronize();
    await _refreshInfo();
    online = true;
    _scheduleSync();
  });
  Future<void> synchronize({bool full = false}) =>
      _runSync(full: full, manual: true);
  Future<void> _runSync({bool full = false, required bool manual}) {
    if (_syncRun != null) return _syncRun!;
    if (_disposed ||
        storageFailure ||
        session == null ||
        repository == null ||
        session!.blocked ||
        signInStep != SignInStep.credentials ||
        _credentials != null ||
        normalizeApiUrl(session!.apiUrl) != apiUrl) {
      return Future.value();
    }
    final checked = session!;
    final repo = repository!;
    final epoch = _syncEpoch;
    bool active() =>
        !_disposed &&
        epoch == _syncEpoch &&
        session?.token == checked.token &&
        session?.contextId == checked.contextId &&
        session?.blocked != true;
    syncBusy = true;
    if (manual) error = null;
    notifyListeners();
    final worker = sync.SyncWorker(
      _api!,
      repo,
      checked,
      active: active,
      onChanged: () {
        if (active()) {
          generation++;
          notifyListeners();
        }
      },
    );
    final run = () async {
      try {
        await worker.run(full: full, manual: manual);
        if (active()) {
          await _refreshInfo();
          online = true;
        }
      } on ApiFailure catch (e) {
        if (active()) await _apiFailure(e);
      } catch (_) {
        if (active()) error = 'No llegó una confirmación válida. Los pendientes se conservaron para reintentar.';
      } finally {
        try {
          if (active()) await _refreshInfo();
        } catch (_) {
          if (active()) {
            storageFailure = true;
            error = 'No se pudo leer el estado local. Los pendientes se conservaron; solicita soporte.';
          }
        }
        syncBusy = false;
        _syncRun = null;
        notifyListeners();
      }
    }();
    _syncRun = run;
    return run;
  }

  Future<void> _refreshInfo() async {
    syncInfo = await repository!.info();
    generation++;
  }

  Future<void> checkAccess() async {
    if (busy ||
        syncBusy ||
        session == null ||
        repository == null ||
        storageFailure ||
        signInStep != SignInStep.credentials ||
        _credentials != null ||
        normalizeApiUrl(session!.apiUrl) != apiUrl) {
      return;
    }
    final checked = session!;
    final api = _api!;
    final repo = repository!;
    try {
      final validClock = await repo.observeClock(DateTime.now());
      if (session?.contextId != checked.contextId ||
          session?.token != checked.token ||
          _disposed) {
        return;
      }
      if (!validClock) {
        session = session!.copyWith(blocked: true);
        await vault.save(session!);
        error = 'El reloj retrocedió. Se requiere revalidación online.';
      }
      if (session?.contextId != checked.contextId ||
          session?.token != checked.token ||
          _disposed) {
        return;
      }
      await api.request(
        'device/heartbeat',
        token: checked.token,
        body: checked.requestContext,
      );
      if (session?.contextId != checked.contextId ||
          session?.token != checked.token ||
          _disposed) {
        return;
      }
      online = true;
      _scheduleSync();
    } on ApiFailure catch (e) {
      if (session?.contextId == checked.contextId &&
          session?.token == checked.token &&
          !_disposed) {
        await _apiFailure(e);
      }
    } catch (_) {
      error = 'No fue posible verificar la autorización.';
    }
    notifyListeners();
  }

  Future<void> _apiFailure(ApiFailure failure) async {
    online = !failure.isConnectionFailure;
    error = failure.message;
    if (failure.blocksAccess && session != null) {
      session = session!.copyWith(blocked: true);
      try {
        await repository?.setLocked(true);
        await vault.save(session!);
      } catch (_) {
        storageFailure = true;
        error = 'No se pudo guardar el bloqueo de acceso. Los datos se conservaron; solicita soporte.';
      }
    }
  }

  Future<void> _action(Future<void> Function() callback) async {
    if (busy || starting) return;
    busy = true;
    error = null;
    notifyListeners();
    try {
      await callback();
    } on ApiFailure catch (e) {
      await _apiFailure(e);
    } on SaleInputException catch (e) {
      error = e.message;
      if (repository != null && await repository!.isLocked()) {
        session = session!.copyWith(blocked: true);
        await vault.save(session!);
      }
    } on FormatException catch (e) {
      error = e.message;
    } catch (_) {
      error = 'No fue posible completar la operación. Los datos locales se conservaron; reintenta o solicita soporte.';
    } finally {
      busy = false;
      notifyListeners();
    }
  }

  Future<void> signOut() async {
    if (busy) return;
    final old = session;
    final discoveryToken = _credentials?['token'] as String?;
    final api = _api;
    final repo = repository;
    session = null;
    busy = true;
    notifyListeners();
    await _stopSync();
    syncInfo = null;
    _credentials = null;
    _challenge = null;
    signInStep = SignInStep.credentials;
    repository = null;
    _sync = null;
    generation++;
    error = null;
    busy = true;
    notifyListeners();
    try {
      await repo?.setLocked(true);
      await vault.lock();
    } catch (_) {
      storageFailure = true;
      error = 'Se bloqueó el acceso, pero no se pudo guardar el cierre de sesión. Conserva los datos y solicita soporte.';
    } finally {
      await repo?.db.close();
      busy = false;
      notifyListeners();
    }
    // Remote failure cannot reopen local access or discard stored data.
    try {
      if (old != null) {
        await api!.logout(old);
      } else if (discoveryToken != null) {
        await api!.request('auth/logout', token: discoveryToken);
      }
    } catch (_) {}
  }

  @override
  void dispose() {
    _disposed = true;
    _expiryTimer?.cancel();
    _networkTimer?.cancel();
    _saleSyncTimer?.cancel();
    _syncEpoch++;
    _api?.close();
    final repo = repository;
    if (repo != null) {
      unawaited((_syncRun ?? Future<void>.value()).whenComplete(repo.db.close));
    }
    super.dispose();
  }
}
