<?php

namespace App\Listeners;

use App\Models\Empresa;
use App\Support\SubscriptionPlans;
use Illuminate\Support\Arr;
use Laravel\Cashier\Events\WebhookHandled;

class SyncEmpresaPlanFromStripe
{
    public function handle(WebhookHandled $event): void
    {
        $payload = $event->payload;
        $type = (string) ($payload['type'] ?? '');

        if (!in_array($type, [
            'customer.subscription.created',
            'customer.subscription.updated',
        ], true)) {
            return;
        }

        $customerId = Arr::get($payload, 'data.object.customer');
        if (!$customerId) {
            return;
        }

        /** @var Empresa|null $empresa */
        $empresa = Empresa::query()->where('stripe_id', $customerId)->first();
        if (!$empresa) {
            return;
        }

        $stripePriceId = Arr::get($payload, 'data.object.items.data.0.price.id');
        $mapped = SubscriptionPlans::fromStripePriceId($stripePriceId);
        if (!$mapped) {
            return;
        }

        $plan = SubscriptionPlans::find($mapped['plan']);

        $empresa->forceFill([
            'plan_code' => $mapped['plan'],
            'plan_cycle' => $mapped['cycle'],
            'numero_sucursales' => (int) ($plan['limits']['max_sucursales'] ?? $empresa->numero_sucursales),
            'numero_dispositivos_por_sucursal' => (int) ($plan['limits']['devices_per_sucursal'] ?? $empresa->numero_dispositivos_por_sucursal),
        ])->save();
    }
}

