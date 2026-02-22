<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Services\SucursalService;
use App\Support\SubscriptionPlans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        $plans = collect(SubscriptionPlans::all())->map(function (array $plan) {
            return [
                'code' => $plan['code'],
                'name' => $plan['name'],
                'prices' => $plan['prices'],
                'limits' => $plan['limits'],
                'features' => $plan['features'],
                'has_yearly' => !empty($plan['prices']['yearly_mxn']),
            ];
        })->values();

        $user = Auth::user();
        $empresa = null;

        if ($user) {
            $empresa = SucursalService::getEmpresaActiva();

            if (!$empresa && $user->id_empresa) {
                $empresa = Empresa::query()->find($user->id_empresa);
            }
        }

        $hasSubscriptionHistory = false;
        $activePlanCode = null;
        $activePlanName = null;
        $activePlanCycle = null;

        if ($empresa) {
            $hasSubscriptionHistory = $empresa
                ->subscriptions()
                ->where('type', 'default')
                ->exists();

            $activePlanCode = $empresa->plan_code;
            $activePlanCycle = $empresa->plan_cycle;

            if ($activePlanCode && SubscriptionPlans::exists($activePlanCode)) {
                $activePlanName = SubscriptionPlans::find($activePlanCode)['name'] ?? $activePlanCode;
            } else {
                $activePlanName = $activePlanCode;
            }
        }

        $showTrialBenefits = !$user || !$hasSubscriptionHistory;

        return Inertia::render('Plans', [
            'plans' => $plans,
            'trialDays' => SubscriptionPlans::trialDays(),
            'defaults' => config('subscriptions.defaults'),
            'viewer' => [
                'is_authenticated' => (bool) $user,
                'show_trial_benefits' => $showTrialBenefits,
                'has_subscription_history' => $hasSubscriptionHistory,
                'active_plan_code' => $activePlanCode,
                'active_plan_name' => $activePlanName,
                'active_plan_cycle' => $activePlanCycle,
                'has_active_access' => (bool) ($empresa?->canAccessApp() ?? false),
            ],
        ]);
    }
}
