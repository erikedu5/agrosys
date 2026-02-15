<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Support\SubscriptionPlans;
use Illuminate\Http\Request;
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

        return Inertia::render('Plans', [
            'plans' => $plans,
            'trialDays' => SubscriptionPlans::trialDays(),
            'defaults' => config('subscriptions.defaults'),
        ]);
    }
}

