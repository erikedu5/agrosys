<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Billable;
use App\Support\SubscriptionPlans;

class Empresa extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Billable;

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'email',
        'rfc',
        'aviso',
        'numero_sucursales',
        'numero_dispositivos_por_sucursal',
        'plan_code',
        'plan_cycle',
        'manual_subscription_starts_at',
        'manual_subscription_ends_at',
        'manual_subscription_blocked',
        'billing_requires_invoice',
        'billing_email',
        'billing_razon_social',
        'billing_rfc',
        'billing_regimen_fiscal',
        'billing_uso_cfdi',
        'billing_codigo_postal',
        'terms_version',
        'terms_accepted_at',
        'privacy_version',
        'privacy_accepted_at',
        'mostrar_campos_precio',
        'ventas_bloqueadas',
        'enviar_facturas_automaticas',
        'motivo_bloqueo',
        'facturapi_api_key',
        'facturapi_sandbox',
    ];

    protected $casts = [
        'mostrar_campos_precio' => 'boolean',
        'ventas_bloqueadas' => 'boolean',
        'enviar_facturas_automaticas' => 'boolean',
        'facturapi_sandbox' => 'boolean',
        'billing_requires_invoice' => 'boolean',
        'trial_ends_at' => 'datetime',
        'manual_subscription_starts_at' => 'datetime',
        'manual_subscription_ends_at' => 'datetime',
        'manual_subscription_blocked' => 'boolean',
        'terms_accepted_at' => 'datetime',
        'privacy_accepted_at' => 'datetime',
        'trial_reminder_7d_sent_at' => 'datetime',
        'trial_reminder_3d_sent_at' => 'datetime',
        'trial_reminder_1d_sent_at' => 'datetime',
        'trial_ended_sent_at' => 'datetime',
    ];

    public function plan(): ?array
    {
        if (!$this->plan_code) {
            return null;
        }

        return SubscriptionPlans::find($this->plan_code);
    }

    public function maxSucursalesPermitidas(): ?int
    {
        $plan = $this->plan();
        return $plan['limits']['max_sucursales'] ?? null;
    }

    public function dispositivosPorSucursalPermitidos(): ?int
    {
        $plan = $this->plan();
        return $plan['limits']['devices_per_sucursal'] ?? $this->numero_dispositivos_por_sucursal;
    }

    public function diasRestantesTrial(): ?int
    {
        if (!$this->trial_ends_at) {
            return null;
        }

        $days = now()->diffInDays($this->trial_ends_at, false);
        return max(0, (int) $days);
    }

    public function canAccessApp(): bool
    {
        if ($this->manual_subscription_blocked) {
            return false;
        }

        if ($this->hasActiveManualSubscription()) {
            return true;
        }

        if ($this->subscribed('default')) {
            return true;
        }

        // Empresas legacy / internas (sin plan_code) no se bloquean por defecto
        // si no tienen una vigencia manual definida.
        if (!$this->plan_code && !$this->hasManualSubscriptionWindow()) {
            return true;
        }

        return $this->onTrial();
    }

    public function hasManualSubscriptionWindow(): bool
    {
        return (bool) ($this->manual_subscription_starts_at || $this->manual_subscription_ends_at);
    }

    public function hasActiveManualSubscription(): bool
    {
        if (!$this->hasManualSubscriptionWindow()) {
            return false;
        }

        $now = now();

        if ($this->manual_subscription_starts_at && $now->lt($this->manual_subscription_starts_at)) {
            return false;
        }

        if ($this->manual_subscription_ends_at && $now->gt($this->manual_subscription_ends_at)) {
            return false;
        }

        return true;
    }

    public function hasActiveStripeSubscription(): bool
    {
        $subscription = $this->subscription('default');
        return (bool) ($subscription && $subscription->active());
    }

    public function activeSubscriptionSource(): string
    {
        if ($this->manual_subscription_blocked) {
            return 'blocked';
        }

        $manual = $this->hasActiveManualSubscription();
        $stripe = $this->hasActiveStripeSubscription();

        if ($manual && $stripe) {
            return 'hybrid';
        }

        if ($manual) {
            return 'manual';
        }

        if ($stripe) {
            return 'stripe';
        }

        if ($this->onTrial()) {
            return 'trial';
        }

        if (!$this->plan_code) {
            return 'legacy';
        }

        return 'none';
    }

    // Cashier: map model fields to Stripe customer fields.
    public function stripeName()
    {
        return $this->nombre ?? null;
    }

    public function stripePhone(): ?string
    {
        return $this->telefono ?? null;
    }

    public function stripeEmail(): ?string
    {
        return $this->billing_email ?: ($this->email ?? null);
    }
}
