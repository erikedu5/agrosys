import 'dart:async';
import 'dart:convert';

import 'package:drift/drift.dart' show Value;
import 'package:uuid/uuid.dart';

import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';

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
  static const configuredApiUrl = String.fromEnvironment(
    'POS_API_URL',
    defaultValue: 'http://localhost:8000/api/v1/pos',
  );
  String apiUrl = configuredApiUrl;
  bool startupSessionFailure = false;
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
  bool hasPermission(String permission) =>
      canConsult && session!.permissions.contains(permission);
  bool get canReadInventory => hasPermission('inventory.read');
  bool get canReadCustomers => hasPermission('customer.read');
  bool get canReadHistory => hasPermission('sale.history');
  Future<Map<String, dynamic>> inventoryMovements(
    String productId, {
    int page = 1,
  }) async {
    if (!hasPermission('inventory.movements.read')) {
      throw ApiFailure(403, 'No tienes permiso para consultar movimientos.');
    }
    final checked = session!;
    try {
      final result = await _api!.request(
        'inventory/$productId/movements',
        token: checked.token,
        body: {...checked.requestContext, 'page': page},
      );
      if (session?.contextId != checked.contextId ||
          session?.token != checked.token ||
          !hasPermission('inventory.movements.read')) {
        throw ApiFailure(403, 'La sesión cambió. Abre de nuevo el inventario.');
      }
      return result;
    } on ApiFailure catch (e) {
      if (session?.contextId == checked.contextId &&
          session?.token == checked.token) {
        await _apiFailure(e);
        notifyListeners();
      }
      rethrow;
    }
  }

  bool _inventoryWriting = false;
  bool canWriteInventory(String permission) =>
      online && !busy && !syncBusy && hasPermission(permission);

  Future<Map<String, dynamic>> inventoryFormData({String? productId}) async {
    if (!online || !canReadInventory) {
      throw ApiFailure(null, 'Conéctate para gestionar inventario.');
    }
    final checked = session!;
    final options = await _api!.request(
      'inventory/options',
      token: checked.token,
      body: checked.requestContext,
    );
    final detail = productId == null
        ? null
        : await _api!.request(
            'inventory/$productId/detail',
            token: checked.token,
            body: checked.requestContext,
          );
    if (session?.contextId != checked.contextId ||
        session?.token != checked.token ||
        !canReadInventory) {
      throw ApiFailure(403, 'La sesión cambió. Abre de nuevo el inventario.');
    }
    return {...options, ...?detail};
  }

  Future<Map<String, dynamic>> inventoryDestructivePreview(
    String productId,
    String action,
  ) async {
    final permission = action == 'delete'
        ? 'product.delete'
        : 'inventory.reset';
    if (!canWriteInventory(permission)) {
      throw ApiFailure(
        403,
        'Conéctate con un usuario autorizado para continuar.',
      );
    }
    final checked = session!;
    try {
      final result = await _api!.request(
        'inventory/$productId/preview',
        token: checked.token,
        body: {...checked.requestContext, 'action': action},
      );
      if (session?.contextId != checked.contextId ||
          session?.token != checked.token ||
          !hasPermission(permission)) {
        throw ApiFailure(403, 'La sesión cambió. Abre de nuevo el inventario.');
      }
      return result;
    } on ApiFailure catch (e) {
      if (session?.contextId == checked.contextId &&
          session?.token == checked.token) {
        await _apiFailure(e);
        notifyListeners();
      }
      rethrow;
    }
  }

  Future<Map<String, dynamic>> stockWorkflowData(
    String path,
    String permission, {
    Map<String, dynamic> body = const {},
  }) async {
    if (!online || !hasPermission(permission)) {
      throw ApiFailure(
        403,
        'Conéctate con un usuario autorizado para consultar este módulo.',
      );
    }
    final checked = session!;
    try {
      final result = await _api!.request(
        path,
        token: checked.token,
        body: {...body, ...checked.requestContext},
      );
      if (session?.contextId != checked.contextId ||
          session?.token != checked.token ||
          !hasPermission(permission)) {
        throw ApiFailure(403, 'La sesión cambió. Abre de nuevo el módulo.');
      }
      return result;
    } on ApiFailure catch (e) {
      if (session?.contextId == checked.contextId &&
          session?.token == checked.token) {
        await _apiFailure(e);
        notifyListeners();
      }
      rethrow;
    }
  }

  Future<String> prepareInventoryRequest(
    String path,
    String permission,
    Map<String, dynamic> payload,
  ) async {
    if (!canWriteInventory(permission)) {
      throw ApiFailure(
        403,
        'No puedes realizar esta operación en este momento.',
      );
    }
    final id = const Uuid().v4();
    await repository!.db
        .into(repository!.db.inventoryRequests)
        .insert(
          InventoryRequestsCompanion.insert(
            operationId: id,
            path: path,
            permission: permission,
            payload: jsonEncode({...payload, 'operation_id': id}),
          ),
        );
    return id;
  }

  Future<Map<String, dynamic>> retryInventoryRequest(String id) async {
    final repo = repository;
    if (repo == null) throw ApiFailure(403, 'La sesión no está disponible.');
    final row = await (repo.db.select(
      repo.db.inventoryRequests,
    )..where((t) => t.operationId.equals(id))).getSingle();
    if (repo != repository) {
      throw ApiFailure(403, 'La sesión cambió. Abre de nuevo el inventario.');
    }
    if (!canWriteInventory(row.permission)) {
      throw ApiFailure(
        403,
        'Se requiere conexión y autorización para guardar inventario.',
      );
    }
    if (row.status == 'rejected') {
      throw ApiFailure(
        409,
        'La solicitud fue rechazada. Recarga el producto y corrige los datos.',
      );
    }
    if (row.result != null) return object(jsonDecode(row.result!));
    final checked = session!;
    busy = true;
    _inventoryWriting = true;
    notifyListeners();
    try {
      await _stopSync();
      final result = await _api!.request(
        row.path,
        token: checked.token,
        body: {...object(jsonDecode(row.payload)), ...checked.requestContext},
      );
      if (session?.contextId != checked.contextId ||
          session?.token != checked.token ||
          repository != repo) {
        throw ApiFailure(
          403,
          'La sesión cambió. Revisa la solicitud en su sucursal original.',
        );
      }
      final resourceField = row.path.startsWith('purchases/')
          ? 'purchaseId'
          : row.path.startsWith('transfers/')
          ? 'transferId'
          : 'productId';
      if (result['operationId'] != id ||
          result['status'] != 'confirmed' ||
          result[resourceField] is! String ||
          (result[resourceField] as String).isEmpty) {
        throw ApiFailure(
          500,
          'No llegó una confirmación válida. Reintenta la misma solicitud.',
        );
      }
      await (repo.db.update(
        repo.db.inventoryRequests,
      )..where((t) => t.operationId.equals(id))).write(
        InventoryRequestsCompanion(
          status: const Value('confirmed'),
          result: Value(jsonEncode(result)),
        ),
      );
      String? syncWarning;
      try {
        final info = await repo.info();
        await repo.startDownload(
          incremental: info.cursor != null,
          cursor: info.cursor,
        );
        await _sync!.synchronize();
        await _refreshInfo();
      } catch (_) {
        syncWarning = 'Guardado en el servidor. Sincroniza el catálogo para ver las existencias y precios actualizados.';
      }
      return {...result, 'syncWarning': ?syncWarning};
    } on ApiFailure catch (e) {
      if ([404, 409, 422].contains(e.status)) {
        await (repo.db.update(repo.db.inventoryRequests)
              ..where((t) => t.operationId.equals(id)))
            .write(const InventoryRequestsCompanion(status: Value('rejected')));
      }
      if (session?.contextId == checked.contextId &&
          session?.token == checked.token) {
        await _apiFailure(e);
      }
      rethrow;
    } finally {
      busy = false;
      _inventoryWriting = false;
      generation++;
      notifyListeners();
    }
  }

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
    var stage = 'keychain';
    try {
      session = await vault.load();
      if (session != null) {
        stage = 'server';
        apiUrl = normalizeApiUrl(session!.apiUrl);
        _api = apiFactory(apiUrl);
        stage = 'session';
        if (session!.lease != null) {
          await vault.verifyLease(session!, session!.lease!);
        }
        stage = 'database';
        await _openRepository();
      }
    } catch (e) {
      final securityStatus = e is PlatformException && e.details is int
          ? e.details
          : 'n/a';
      debugPrint(
        'POS startup failure: stage=$stage type=${e.runtimeType} securityStatus=$securityStatus',
      );
      storageFailure = true;
      startupSessionFailure = stage == 'server' || stage == 'session';
      if (stage == 'keychain' && e is PlatformException) {
        final code = e.details is int ? ' (${e.details})' : '';
        error =
            'No se pudo acceder al llavero de macOS/iOS$code. Desbloquea el llavero y permite el acceso a AgroSys cuando el sistema lo solicite. Después cierra y abre la app. Los datos se conservaron.';
      } else if (stage == 'server' && e is FormatException) {
        error =
            '${e.message} La sesión guardada usa un servidor incompatible con esta versión. Puedes cerrar esa sesión y entrar de nuevo; las ventas locales se conservarán.';
      } else if (stage == 'session') {
        error = 'No se pudo validar la sesión o su autorización guardada. Los datos se conservaron. Solicita soporte antes de modificar el almacenamiento.';
      } else {
        error = 'No se pudo abrir el almacenamiento local. Los datos se conservaron. Reintenta o solicita soporte.';
      }
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
        _inventoryWriting ||
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
      final heartbeat = await api.request(
        'device/heartbeat',
        token: checked.token,
        body: {
          ...checked.requestContext,
          'lease_id': checked.lease == null
              ? null
              : object(checked.lease!['claims'])['leaseId'],
        },
      );
      if (session?.contextId != checked.contextId ||
          session?.token != checked.token ||
          _disposed) {
        return;
      }
      if (heartbeat['offlineLease'] != null) {
        final lease = object(heartbeat['offlineLease']);
        await vault.verifyLease(checked, lease);
        if (session?.contextId != checked.contextId ||
            session?.token != checked.token ||
            _disposed) {
          return;
        }
        session = session!.copyWith(lease: lease);
        await vault.save(session!);
        generation++;
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
      storageFailure = false;
      startupSessionFailure = false;
      apiUrl = configuredApiUrl;
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
