import 'package:agrosys_pos/data/database.dart';
import 'package:agrosys_pos/data/catalog_repository.dart';
import 'package:drift/native.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/fixtures.dart';

void main() {
  test(
    'AC-03 search p95 with 10000 local products stays under 300ms',
    () async {
      final db = PosDatabase(
        NativeDatabase.memory(),
        fixtureSession().contextId,
      );
      try {
        await db.select(db.syncMetadata).get();
        await db.batch((batch) {
          batch.insertAll(db.localProducts, [
            for (var i = 1; i <= 10000; i++)
              LocalProductsCompanion.insert(
                serverId: '$i',
                name: 'Producto maíz $i',
                searchName: 'producto maiz $i',
                barcode: '750${i.toString().padLeft(10, '0')}',
                size: '1 kg',
                priceCents: 5000,
                active: true,
                revision: 'benchmark',
              ),
          ]);
          batch.insertAll(db.stockSnapshots, [
            for (var i = 1; i <= 10000; i++)
              StockSnapshotsCompanion.insert(
                productId: '$i',
                quantityUnits: 1000,
                revision: 'benchmark',
              ),
          ]);
        });
        final repo = CatalogRepository(db, fixtureSession());
        final samples = <int>[];
        for (var i = 0; i < 30; i++) {
          final timer = Stopwatch()..start();
          final result = await repo.products(
            i.isEven ? 'maiz' : '7500000009999',
          );
          timer.stop();
          expect(result, isNotEmpty);
          samples.add(timer.elapsedMicroseconds);
        }
        samples.sort();
        final p95 = samples[(samples.length * .95).ceil() - 1] / 1000;
        debugPrint(
          '10000-product local search p95: ${p95.toStringAsFixed(2)}ms',
        );
        expect(p95, lessThan(300));
      } finally {
        await db.close();
      }
    },
  );
}
