<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Services\SucursalService;
use App\Support\SubscriptionPlans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Laravel\Cashier\Exceptions\IncompletePayment;

class SubscriptionController extends Controller
{
    public function show(Request $request)
    {
        $user = Auth::user();
        $empresa = SucursalService::getEmpresaActiva();

        $subscription = $empresa?->subscription('default');

        $plans = collect(SubscriptionPlans::all())->map(function (array $p) {
            return [
                'code' => $p['code'],
                'name' => $p['name'],
                'prices' => $p['prices'],
                'limits' => $p['limits'],
                'features' => $p['features'],
                'has_yearly' => !empty($p['prices']['yearly_mxn']),
            ];
        })->values();

        return Inertia::render('Subscription/Manage', [
            'empresa' => $empresa ? [
                'id' => $empresa->id,
                'nombre' => $empresa->nombre,
                'plan_code' => $empresa->plan_code,
                'plan_cycle' => $empresa->plan_cycle,
                'trial_ends_at' => optional($empresa->trial_ends_at)->toIso8601String(),
                'trial_days_left' => $empresa->diasRestantesTrial(),
                'can_access' => $empresa->canAccessApp(),
                'has_stripe_id' => $empresa->hasStripeId(),
            ] : null,
            'subscription' => $subscription ? [
                'stripe_status' => $subscription->stripe_status,
                'stripe_price' => $subscription->stripe_price,
                'quantity' => $subscription->quantity,
                'trial_ends_at' => optional($subscription->trial_ends_at)->toIso8601String(),
                'ends_at' => optional($subscription->ends_at)->toIso8601String(),
                'on_grace_period' => $subscription->onGracePeriod(),
                'cancelled' => $subscription->canceled(),
                'active' => $subscription->active(),
            ] : null,
            'plans' => $plans,
            'trialDays' => SubscriptionPlans::trialDays(),
            'canManage' => in_array($user?->tipo, ['adminEmpresa', 'superAdmin'], true),
        ]);
    }

    public function checkout(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user?->tipo, ['adminEmpresa', 'superAdmin'], true)) {
            abort(403);
        }

        $empresa = SucursalService::getEmpresaActiva();
        if (!$empresa) {
            abort(404);
        }

        $planCodes = array_keys(SubscriptionPlans::all());

        $data = $request->validate([
            'plan' => ['required', Rule::in($planCodes)],
            'cycle' => ['required', Rule::in(['monthly', 'yearly'])],
        ]);

        $plan = SubscriptionPlans::find($data['plan']);

        if ($data['cycle'] === 'yearly' && empty($plan['prices']['yearly_mxn'])) {
            return back()->with('error', 'Este plan no tiene pago anual.');
        }

        $currentSubscription = $empresa->subscription('default');
        $hasActiveSubscription = $currentSubscription && $currentSubscription->active();

        if (
            $hasActiveSubscription &&
            $empresa->plan_code === $data['plan'] &&
            $empresa->plan_cycle === $data['cycle']
        ) {
            return back()->with('error', 'Ya tienes este plan activo. Selecciona un plan superior o inferior.');
        }

        $priceId = SubscriptionPlans::stripePriceId($data['plan'], $data['cycle']);
        $targetPrice = $priceId
            ? $priceId
            : [SubscriptionPlans::stripeInlineCheckoutItem($data['plan'], $data['cycle'])];

        // Si ya existe suscripcion activa: actualizar con prorrateo (credito/cargo proporcional).
        if ($hasActiveSubscription) {
            try {
                $currentSubscription
                    ->prorate()
                    ->swap($targetPrice);
            } catch (IncompletePayment $exception) {
                $paymentUrl = route('cashier.payment', [
                    'id' => $exception->payment->id,
                    'redirect' => route('subscription.show'),
                ]);

                if ($request->header('X-Inertia')) {
                    return Inertia::location($paymentUrl);
                }

                return redirect()->to($paymentUrl);
            }

            $empresa->forceFill([
                'plan_code' => $data['plan'],
                'plan_cycle' => $data['cycle'],
                'numero_sucursales' => (int) ($plan['limits']['max_sucursales'] ?? $empresa->numero_sucursales),
                'numero_dispositivos_por_sucursal' => (int) ($plan['limits']['devices_per_sucursal'] ?? $empresa->numero_dispositivos_por_sucursal),
            ])->save();

            return back()->with('success', 'Plan actualizado. Se aplico ajuste proporcional por el tiempo restante del ciclo actual.');
        }

        // Alta inicial: crear checkout de Stripe.
        $empresa->forceFill([
            'plan_code' => $data['plan'],
            'plan_cycle' => $data['cycle'],
            'numero_sucursales' => (int) ($plan['limits']['max_sucursales'] ?? $empresa->numero_sucursales),
            'numero_dispositivos_por_sucursal' => (int) ($plan['limits']['devices_per_sucursal'] ?? $empresa->numero_dispositivos_por_sucursal),
        ])->save();

        $builder = $empresa
            ->newSubscription('default', $targetPrice)
            ->withMetadata([
                'empresa_id' => (string) $empresa->id,
                'plan_code' => (string) $data['plan'],
                'plan_cycle' => (string) $data['cycle'],
                'pricing_source' => $priceId ? 'stripe_price_id' : 'inline_checkout_price_data',
            ]);

        if ($empresa->trial_ends_at && $empresa->trial_ends_at->isFuture()) {
            $builder->trialUntil($empresa->trial_ends_at);
        }

        $checkout = $builder->checkout([
            'success_url' => route('subscription.show').'?checkout=success',
            'cancel_url' => route('subscription.show').'?checkout=cancel',
        ]);

        // Si el POST viene desde Inertia (XHR), necesitamos forzar navegacion completa al URL de Stripe.
        if ($request->header('X-Inertia')) {
            return Inertia::location($checkout->url);
        }

        return $checkout;
    }

    public function portal(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user?->tipo, ['adminEmpresa', 'superAdmin'], true)) {
            abort(403);
        }

        $empresa = SucursalService::getEmpresaActiva();
        if (!$empresa || !$empresa->hasStripeId()) {
            return back()->with('error', 'No hay un cliente de Stripe asociado a esta empresa.');
        }

        if ($request->header('X-Inertia')) {
            return Inertia::location($empresa->billingPortalUrl(route('subscription.show')));
        }

        return $empresa->redirectToBillingPortal(route('subscription.show'));
    }
}
