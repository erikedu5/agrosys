<?php

namespace App\Services\Pos;

use App\Models\Clientes;
use App\Models\Producto;
use App\Models\Sucursales;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SaleAmounts
{
    public function validate(array $payload, Clientes $customer, Sucursales $branch, bool $lock = true): array
    {
        Validator::make($payload, [
            'saleType' => 'required|in:Contado,Credito', 'total' => 'required',
            'items' => 'required|array|min:1|max:200', 'items.*.productId' => 'required|integer|distinct|min:1',
            'items.*.quantity' => 'required', 'items.*.unitPrice' => 'required', 'items.*.total' => 'required',
            'payments' => 'required|array|size:1', 'payments.0.method' => 'required|in:cash', 'payments.0.amount' => 'required',
        ])->validate();
        $discount = Decimal::units($customer->porcentaje_descuento, 'discount');
        if ($discount > 10000) {
            $this->fail('discount', 'Descuento inválido.');
        }
        $query = Producto::withoutGlobalScope('empresa')->where('id_empresa', $branch->id_empresa)->whereIn('id', array_column($payload['items'], 'productId'))->orderBy('id');
        if ($lock) {
            $query->lockForUpdate();
        }
        $products = $query->get()->keyBy('id');
        $total = 0;
        $items = [];
        foreach ($payload['items'] as $item) {
            $product = $products->get($item['productId']);
            if (! $product) {
                $this->fail('items', 'Producto eliminado o de otra empresa.');
            }
            $quantity = Decimal::units($item['quantity'], 'quantity');
            if ($quantity <= 0 || $quantity > 99999999) {
                $this->fail('quantity', 'Cantidad fuera de rango.');
            }
            $price = Decimal::roundRatio(Decimal::units($product->precio_ieps, 'price') * (10000 - $discount), 10000);
            if (Decimal::units($item['unitPrice'], 'unitPrice') !== $price) {
                $this->fail('unitPrice', "El precio de {$product->nombre} cambió desde la última sincronización.");
            }
            $lineTotal = Decimal::roundRatio($price * $quantity, 100);
            if ($lineTotal > 9999999999 || Decimal::units($item['total'], 'items.total') !== $lineTotal) {
                $this->fail('items.total', 'Importe de partida incorrecto.');
            }
            $total += $lineTotal;
            $items[] = ['product' => $product, 'quantity' => $quantity, 'price' => $price, 'total' => $lineTotal];
        }
        if ($total > 9999999999 || Decimal::units($payload['total'], 'total') !== $total) {
            $this->fail('total', 'El total no coincide con las partidas.');
        }
        $payment = Decimal::units($payload['payments'][0]['amount'], 'payment');
        if ($payment > $total || ($payload['saleType'] === 'Contado' && $payment !== $total)) {
            $this->fail('payment', 'Pago o abono inicial fuera de rango.');
        }

        return ['items' => $items, 'total' => $total, 'payment' => $payment, 'paid' => $payment === $total];
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
