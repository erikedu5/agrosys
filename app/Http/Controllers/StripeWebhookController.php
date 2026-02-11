<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;

class StripeWebhookController extends CashierController
{
    /**
     * Handle invoice payment succeeded.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleInvoicePaymentSucceeded(array $payload)
    {
        Log::info('Stripe Webhook: Invoice Payment Succeeded', ['payload' => $payload]);

        if ($this->isTestingEvent($payload)) {
            return $this->successMethod();
        }

        // Desbloquear empresa si el pago fue exitoso
        $stripeId = $payload['data']['object']['customer'] ?? null;

        if ($stripeId) {
            $empresa = Empresa::where('stripe_id', $stripeId)->first();

            if ($empresa && $empresa->ventas_bloqueadas) {
                $empresa->ventas_bloqueadas = false;
                $empresa->motivo_bloqueo = null;
                $empresa->save();

                Log::info('Empresa desbloqueada tras pago exitoso', [
                    'empresa_id' => $empresa->id,
                    'stripe_id' => $stripeId,
                ]);
            }
        }

        return parent::handleInvoicePaymentSucceeded($payload);
    }

    /**
     * Handle invoice payment failed.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleInvoicePaymentFailed(array $payload)
    {
        Log::warning('Stripe Webhook: Invoice Payment Failed', ['payload' => $payload]);

        if ($this->isTestingEvent($payload)) {
            return $this->successMethod();
        }

        // Bloquear empresa si el pago falló
        $stripeId = $payload['data']['object']['customer'] ?? null;

        if ($stripeId) {
            $empresa = Empresa::where('stripe_id', $stripeId)->first();

            if ($empresa && !$empresa->ventas_bloqueadas) {
                $empresa->ventas_bloqueadas = true;
                $empresa->motivo_bloqueo = 'El pago de tu suscripción no pudo procesarse. Por favor, actualiza tu método de pago.';
                $empresa->save();

                Log::warning('Empresa bloqueada por fallo en pago', [
                    'empresa_id' => $empresa->id,
                    'stripe_id' => $stripeId,
                ]);
            }
        }

        return parent::handleInvoicePaymentFailed($payload);
    }

    /**
     * Handle customer subscription deleted.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleCustomerSubscriptionDeleted(array $payload)
    {
        Log::info('Stripe Webhook: Subscription Deleted', ['payload' => $payload]);

        if ($this->isTestingEvent($payload)) {
            return $this->successMethod();
        }

        // Bloquear empresa si la suscripción fue cancelada
        $stripeId = $payload['data']['object']['customer'] ?? null;

        if ($stripeId) {
            $empresa = Empresa::where('stripe_id', $stripeId)->first();

            if ($empresa) {
                $empresa->ventas_bloqueadas = true;
                $empresa->motivo_bloqueo = 'Tu suscripción ha sido cancelada. Reactívala para continuar usando el sistema.';
                $empresa->subscription_ends_at = now();
                $empresa->save();

                Log::info('Empresa bloqueada tras cancelación de suscripción', [
                    'empresa_id' => $empresa->id,
                    'stripe_id' => $stripeId,
                ]);
            }
        }

        return parent::handleCustomerSubscriptionDeleted($payload);
    }

    /**
     * Handle customer subscription updated.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleCustomerSubscriptionUpdated(array $payload)
    {
        Log::info('Stripe Webhook: Subscription Updated', ['payload' => $payload]);

        if ($this->isTestingEvent($payload)) {
            return $this->successMethod();
        }

        $stripeId = $payload['data']['object']['customer'] ?? null;
        $status = $payload['data']['object']['status'] ?? null;

        if ($stripeId) {
            $empresa = Empresa::where('stripe_id', $stripeId)->first();

            if ($empresa) {
                // Si la suscripción está activa, desbloquear
                if (in_array($status, ['active', 'trialing'])) {
                    $empresa->ventas_bloqueadas = false;
                    $empresa->motivo_bloqueo = null;
                    $empresa->save();

                    Log::info('Empresa desbloqueada tras actualización de suscripción', [
                        'empresa_id' => $empresa->id,
                        'status' => $status,
                    ]);
                }
                // Si la suscripción está inactiva, bloquear
                elseif (in_array($status, ['past_due', 'canceled', 'unpaid'])) {
                    $empresa->ventas_bloqueadas = true;
                    $empresa->motivo_bloqueo = 'Hay un problema con tu suscripción. Por favor, revisa tu método de pago.';
                    $empresa->save();

                    Log::warning('Empresa bloqueada tras actualización de suscripción', [
                        'empresa_id' => $empresa->id,
                        'status' => $status,
                    ]);
                }
            }
        }

        return parent::handleCustomerSubscriptionUpdated($payload);
    }

    /**
     * Determine if the event is a Stripe testing event.
     *
     * @param  array  $payload
     * @return bool
     */
    protected function isTestingEvent(array $payload): bool
    {
        return isset($payload['livemode']) && $payload['livemode'] === false;
    }
}
