<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Laravel\Cashier\Exceptions\IncompletePayment;

class SuscripcionController extends Controller
{
    /**
     * Muestra el estado de la suscripción
     */
    public function index()
    {
        $user = Auth::user();
        $empresa = $user->empresa;

        if (!$empresa) {
            return redirect()->route('dashboard')->withErrors([
                'error' => 'No se encontró información de la empresa.'
            ]);
        }

        $subscriptionData = [
            'empresa' => $empresa,
            'isOnTrial' => $empresa->isOnTrial(),
            'hasActiveSubscription' => $empresa->hasActiveSubscription(),
            'daysRemaining' => $empresa->daysRemainingInTrial(),
            'status' => $empresa->getSubscriptionStatus(),
            'trialEndsAt' => $empresa->trial_ends_at?->format('d/m/Y'),
        ];

        // Si tiene suscripción activa, agregar datos adicionales
        if ($empresa->subscribed('default')) {
            $subscription = $empresa->subscription('default');
            $subscriptionData['subscription'] = [
                'stripe_status' => $subscription->stripe_status,
                'ends_at' => $subscription->ends_at?->format('d/m/Y'),
                'on_grace_period' => $subscription->onGracePeriod(),
            ];
        }

        return Inertia::render('Suscripcion/Index', $subscriptionData);
    }

    /**
     * Muestra el formulario para suscribirse
     */
    /**
     * Muestra el formulario para suscribirse
     */
    public function create()
    {
        $user = Auth::user();
        $empresa = $user->empresa;

        if (!$empresa) {
            return redirect()->route('dashboard');
        }

        $plans = config('subscription.plans');

        return Inertia::render('Suscripcion/Subscribe', [
            'empresa' => $empresa,
            'intent' => $empresa->createSetupIntent(),
            'stripeKey' => config('cashier.key'),
            'plans' => $plans,
        ]);
    }

    /**
     * Procesa la suscripción
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'payment_method' => 'required|string',
            'plan' => 'required|string', // Slug o Price ID
        ]);

        $user = Auth::user();
        $empresa = $user->empresa;

        if (!$empresa) {
            return back()->withErrors(['error' => 'No se encontró la empresa.']);
        }

        // Buscar el plan seleccionado en la configuración
        $selectedPlan = collect(config('subscription.plans'))->firstWhere('stripe_price_id', $validated['plan']);

        if (!$selectedPlan) {
            return back()->withErrors(['plan' => 'El plan seleccionado no es válido.']);
        }

        try {
            $paymentMethod = $validated['payment_method'];

            // Verificar si la empresa tiene trial pendiente
            $trialDays = 0;
            if ($empresa->trial_ends_at && $empresa->trial_ends_at->isFuture()) {
                // Calcular días restantes de trial
                $trialDays = now()->diffInDays($empresa->trial_ends_at);
            }

            // Comparar Stripe IDs
            try {
                $stripePaymentMethod = $empresa->stripe()->paymentMethods->retrieve($paymentMethod);
                Log::info('Debug Payment Method', [
                    'payment_method_id' => $paymentMethod,
                    'pm_customer' => $stripePaymentMethod->customer,
                    'empresa_stripe_id' => $empresa->stripe_id,
                ]);

                if ($stripePaymentMethod->customer && $stripePaymentMethod->customer !== $empresa->stripe_id) {
                    // Si el PM pertenece a otro cliente, intentar adjuntarlo (fallará si ya está adjunto a otro)
                    // Pero si es null, se puede adjuntar.
                    Log::warning('Payment Method Customer Mismatch', [
                        'pm_customer' => $stripePaymentMethod->customer,
                        'empresa_stripe_id' => $empresa->stripe_id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error retrieving Payment Method: ' . $e->getMessage());
            }

            // Crear suscripción con Stripe
            $subscriptionBuilder = $empresa->newSubscription('default', $validated['plan']);

            // Si tiene trial, aplicarlo
            if ($trialDays > 0) {
                $subscriptionBuilder->trialDays($trialDays);
            }

            // Intentamos crear la suscripción pasando el PM como primer argumento.
            // Cashier intentará adjuntarlo si no es el mismo customer.
            // Si ya está adjunto a ESTE customer, no hará nada.
            // Si está adjunto a OTRO customer, fallará.
            try {
                $subscription = $subscriptionBuilder->create($paymentMethod);
            } catch (\Exception $e) {
                // Si falla, intentamos usarlo como default payment method sin adjuntar implícitamente
                // (Asumiendo que ya estaba adjunto y Cashier se confundió, o algo similar)
                Log::warning('Fallo create($pm), intentando create(null, options)', ['error' => $e->getMessage()]);
                $subscription = $subscriptionBuilder->create(null, [], [
                    'default_payment_method' => $paymentMethod,
                ]);
            }

            // Actualizar la empresa
            $empresa->ventas_bloqueadas = false;
            $empresa->motivo_bloqueo = null;
            $empresa->save();

            Log::info('Suscripción creada exitosamente', [
                'empresa_id' => $empresa->id,
                'subscription_id' => $subscription->id,
                'plan' => $validated['plan'],
                'trial_days' => $trialDays,
            ]);

            // Redirigir al dashboard con mensaje de éxito
            $message = $trialDays > 0
                ? '¡Bienvenido! Tu período de prueba de ' . $trialDays . ' días ha comenzado. No se realizará ningún cargo hasta que finalice.'
                : '¡Suscripción activada exitosamente!';

            return redirect()->route('dashboard')->with('success', $message);

        } catch (IncompletePayment $exception) {
            return redirect()->route(
                'cashier.payment',
                [$exception->payment->id, 'redirect' => route('dashboard')]
            );
        } catch (\Exception $e) {
            Log::error('Error al crear suscripción', [
                'empresa_id' => $empresa->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'error' => 'No se pudo procesar el pago. Por favor, verifica tu información e inténtalo de nuevo.'
            ]);
        }
    }

    /**
     * Redirige al portal de cliente de Stripe
     */
    public function billingPortal(Request $request)
    {
        $user = Auth::user();
        $empresa = $user->empresa;

        if (!$empresa) {
            return redirect()->route('dashboard');
        }

        return $empresa->redirectToBillingPortal(route('suscripcion.index'));
    }

    /**
     * Cancela la suscripción
     */
    public function cancel(Request $request)
    {
        $user = Auth::user();
        $empresa = $user->empresa;

        if (!$empresa || !$empresa->subscribed('default')) {
            return back()->withErrors(['error' => 'No hay suscripción activa para cancelar.']);
        }

        try {
            $empresa->subscription('default')->cancel();

            Log::info('Suscripción cancelada', [
                'empresa_id' => $empresa->id,
            ]);

            return redirect()->route('suscripcion.index')->with(
                'success',
                'Suscripción cancelada. Tendrás acceso hasta el final del período actual.'
            );
        } catch (\Exception $e) {
            Log::error('Error al cancelar suscripción', [
                'empresa_id' => $empresa->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'error' => 'No se pudo cancelar la suscripción. Por favor, inténtalo de nuevo.'
            ]);
        }
    }

    /**
     * Reactiva una suscripción cancelada
     */
    public function resume(Request $request)
    {
        $user = Auth::user();
        $empresa = $user->empresa;

        $subscription = $empresa->subscription('default');

        if (!$subscription || !$subscription->onGracePeriod()) {
            return back()->withErrors(['error' => 'No hay suscripción que reactivar.']);
        }

        try {
            $subscription->resume();

            Log::info('Suscripción reactivada', [
                'empresa_id' => $empresa->id,
            ]);

            return redirect()->route('suscripcion.index')->with(
                'success',
                '¡Suscripción reactivada exitosamente!'
            );
        } catch (\Exception $e) {
            Log::error('Error al reactivar suscripción', [
                'empresa_id' => $empresa->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'error' => 'No se pudo reactivar la suscripción.'
            ]);
        }
    }

    /**
     * Página mostrada cuando la suscripción ha vencido
     */
    public function expired()
    {
        $user = Auth::user();
        $empresa = $user->empresa;

        return Inertia::render('Suscripcion/Vencida', [
            'empresa' => $empresa,
            'status' => $empresa->getSubscriptionStatus(),
        ]);
    }
}
