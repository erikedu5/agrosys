<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Billable;
use Carbon\Carbon;

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
        'mostrar_campos_precio',
        'ventas_bloqueadas',
        'enviar_facturas_automaticas',
        'motivo_bloqueo',
        'facturapi_api_key',
        'facturapi_sandbox',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
        'subscription_ends_at',
    ];

    protected $casts = [
        'mostrar_campos_precio' => 'boolean',
        'ventas_bloqueadas' => 'boolean',
        'enviar_facturas_automaticas' => 'boolean',
        'facturapi_sandbox' => 'boolean',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    /**
     * Verifica si la empresa está en período de prueba activo
     */
    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && Carbon::now()->isBefore($this->trial_ends_at);
    }

    /**
     * Verifica si la empresa tiene una suscripción activa o está en trial
     */
    public function hasActiveSubscription(): bool
    {
        // Si está en trial activo
        if ($this->isOnTrial()) {
            return true;
        }

        // Si tiene suscripción activa en Stripe
        return $this->subscribed('default');
    }

    /**
     * Determina si la empresa debe ser bloqueada
     */
    public function shouldBlock(): bool
    {
        return !$this->hasActiveSubscription();
    }

    /**
     * Calcula días restantes en el trial
     */
    public function daysRemainingInTrial(): int
    {
        if (!$this->trial_ends_at) {
            return 0;
        }

        $days = Carbon::now()->diffInDays($this->trial_ends_at, false);
        return max(0, (int) ceil($days));
    }

    /**
     * Obtiene el estado de la suscripción en formato legible
     */
    public function getSubscriptionStatus(): string
    {
        if ($this->isOnTrial()) {
            $days = $this->daysRemainingInTrial();
            return "Trial - {$days} días restantes";
        }

        if ($this->subscribed('default')) {
            return 'Activa';
        }

        if ($this->subscription('default')?->cancelled()) {
            return 'Cancelada';
        }

        return 'Vencida';
    }
}
