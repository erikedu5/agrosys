<?php

namespace App\Services;

use App\Models\Compras;
use Carbon\Carbon;

class PurchaseIdempotency
{
    public static function fingerprint(array $data, int $branchId): string
    {
        // Only include values that are persisted; ignore labels and form metadata.
        $payload = [
            'sucursal' => $branchId,
            'proveedor' => $data['proveedor'],
            'fecha_compra' => Carbon::parse($data['fecha_compra'])->format('Y-m-d'),
            'total_compra' => (float) $data['total_compra'],
            'total_credito' => (float) $data['total_credito'],
            'status' => $data['status'],
            'productos' => array_map(fn ($product) => [
                'id' => (int) $product['id'],
                'cantidad' => (float) $product['cantidad'],
                'precio' => (float) $product['precio_compra'],
            ], array_values($data['productos'])),
            'abonos' => array_map(fn ($payment) => (float) $payment['cantidad_abonada'], array_values($data['abonos'] ?? [])),
        ];

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public static function find(int $companyId, string $key, string $hash): ?Compras
    {
        $purchase = Compras::where('id_empresa', $companyId)->where('idempotency_key', $key)->first();
        if ($purchase && !hash_equals($purchase->request_hash, $hash)) {
            abort(409, 'Esta compra ya fue registrada con datos diferentes. Revisa el listado antes de registrar otra compra.');
        }

        return $purchase;
    }
}
