<?php

namespace App\Support;

use App\Models\SubscriptionPlan;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Throwable;

class SubscriptionPlans
{
    protected static ?array $resolvedPlans = null;

    public static function all(): array
    {
        if (self::$resolvedPlans !== null) {
            return self::$resolvedPlans;
        }

        $dbPlans = self::fromDatabase();
        if (!empty($dbPlans)) {
            self::$resolvedPlans = $dbPlans;
            return self::$resolvedPlans;
        }

        self::$resolvedPlans = self::fromConfig();
        return self::$resolvedPlans;
    }

    protected static function fromConfig(): array
    {
        return config('subscriptions.plans', []);
    }

    protected static function fromDatabase(): array
    {
        if (!config('subscriptions.use_database', true)) {
            return [];
        }

        try {
            if (!Schema::hasTable('subscription_plans')) {
                return [];
            }

            $plans = SubscriptionPlan::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        } catch (Throwable $e) {
            return [];
        }

        if ($plans->isEmpty()) {
            return [];
        }

        return $plans->mapWithKeys(function (SubscriptionPlan $plan): array {
            $configPlan = Arr::get(self::fromConfig(), $plan->code, []);

            return [
                $plan->code => [
                    'code' => $plan->code,
                    'name' => $plan->name,
                    'prices' => [
                        'monthly_mxn' => (float) $plan->monthly_price_mxn,
                        'yearly_mxn' => $plan->yearly_price_mxn !== null ? (float) $plan->yearly_price_mxn : null,
                    ],
                    'limits' => [
                        'max_sucursales' => (int) $plan->max_sucursales,
                        'devices_per_sucursal' => (int) $plan->devices_per_sucursal,
                    ],
                    'features' => [
                        'bitacora' => (bool) $plan->has_bitacora,
                        'soporte_12h_6d' => (bool) $plan->has_soporte_12h_6d,
                    ],
                    'stripe' => [
                        'monthly_price_id' => $plan->stripe_monthly_price_id
                            ?: ($configPlan['stripe']['monthly_price_id'] ?? null),
                        'yearly_price_id' => $plan->stripe_yearly_price_id
                            ?: ($configPlan['stripe']['yearly_price_id'] ?? null),
                    ],
                ],
            ];
        })->all();
    }

    public static function find(string $planCode): array
    {
        $plan = Arr::get(self::all(), $planCode);

        if (!$plan) {
            throw new InvalidArgumentException("Unknown plan code: {$planCode}");
        }

        return $plan;
    }

    public static function exists(string $planCode): bool
    {
        return Arr::has(self::all(), $planCode);
    }

    public static function trialDays(): int
    {
        return (int) config('subscriptions.trial_days', 30);
    }

    public static function reminderDays(): array
    {
        $days = config('subscriptions.trial_reminder_days', [7, 3, 1]);
        $days = array_values(array_unique(array_map('intval', (array) $days)));
        rsort($days);
        return $days;
    }

    public static function stripePriceId(string $planCode, string $cycle): ?string
    {
        $plan = self::find($planCode);
        $cycle = strtolower($cycle);

        if (!in_array($cycle, ['monthly', 'yearly'], true)) {
            throw new InvalidArgumentException("Unknown cycle: {$cycle}");
        }

        return $cycle === 'monthly'
            ? ($plan['stripe']['monthly_price_id'] ?? null)
            : ($plan['stripe']['yearly_price_id'] ?? null);
    }

    public static function stripeInlineCheckoutItem(string $planCode, string $cycle): array
    {
        $plan = self::find($planCode);
        $cycle = strtolower($cycle);

        if (!in_array($cycle, ['monthly', 'yearly'], true)) {
            throw new InvalidArgumentException("Unknown cycle: {$cycle}");
        }

        $amount = $cycle === 'monthly'
            ? ($plan['prices']['monthly_mxn'] ?? null)
            : ($plan['prices']['yearly_mxn'] ?? null);

        if ($amount === null) {
            throw new InvalidArgumentException("No amount configured for {$planCode} / {$cycle}");
        }

        $interval = $cycle === 'monthly' ? 'month' : 'year';

        return [
            'price_data' => [
                'currency' => strtolower((string) config('cashier.currency', 'mxn')),
                'unit_amount' => (int) round(((float) $amount) * 100),
                'recurring' => [
                    'interval' => $interval,
                ],
                'product_data' => [
                    'name' => $plan['name'],
                    'metadata' => [
                        'plan_code' => (string) $plan['code'],
                        'plan_cycle' => (string) $cycle,
                    ],
                ],
            ],
            'quantity' => 1,
        ];
    }

    public static function fromStripePriceId(?string $stripePriceId): ?array
    {
        if (!$stripePriceId) {
            return null;
        }

        foreach (self::all() as $plan) {
            $monthly = $plan['stripe']['monthly_price_id'] ?? null;
            if ($monthly && $monthly === $stripePriceId) {
                return ['plan' => $plan['code'], 'cycle' => 'monthly'];
            }

            $yearly = $plan['stripe']['yearly_price_id'] ?? null;
            if ($yearly && $yearly === $stripePriceId) {
                return ['plan' => $plan['code'], 'cycle' => 'yearly'];
            }
        }

        return null;
    }
}
