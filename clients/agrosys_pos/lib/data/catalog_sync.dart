import '../core/pos_api.dart';
import 'catalog_repository.dart';

class CatalogSync {
  final PosApi api;
  final CatalogRepository repository;
  final void Function(int pages)? onProgress;
  Future<void>? _running;
  final Future<void> Function()? beforePage;
  CatalogSync(this.api, this.repository, {this.onProgress, this.beforePage});
  Future<void> synchronize({bool full = false}) {
    if (_running != null) return _running!;
    final future = _download(full);
    _running = future.whenComplete(() {
      _running = null;
    });
    return _running!;
  }

  Future<void> _download(bool full) async {
    var state = await repository.download();
    if (full || state == null) {
      final info = await repository.info();
      await repository.startDownload(
        incremental: !full && info.cursor != null,
        cursor: full ? null : info.cursor,
      );
    }
    var expiredRestarts = 0;
    while (true) {
      await beforePage?.call();
      state = await repository.download();
      if (state!.complete) {
        await repository.commitDownload();
        return;
      }
      try {
        final page = await api.page(
          repository.session,
          incremental: state.incremental,
          pageToken: state.nextPageToken,
          cursor: state.baseCursor,
          knownIds: await repository.knownOperationIds(),
        );
        await beforePage?.call();
        await repository.stage(page);
        onProgress?.call(state.pageCount + 1);
      } on ApiFailure catch (e) {
        if (e.status != 410 || expiredRestarts++ >= 1) rethrow;
        await repository.startDownload(incremental: false);
      }
    }
  }
}
