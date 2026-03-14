<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Suscripciones (AgroSys)
    |--------------------------------------------------------------------------
    |
    | Catalogo de planes y limites para enforcement dentro del sistema.
    |
    | Importante:
    | - Los price IDs de Stripe se configuran via .env.
    | - Los montos aqui son solo para UI; el cobro real lo define Stripe.
    |
    */

    'trial_days' => 30,

    /*
    |----------------------------------------------------------------------
    | Fuente de planes
    |----------------------------------------------------------------------
    |
    | true  = usa tabla subscription_plans (recomendado) con fallback a
    |         este archivo si la tabla no existe o no tiene registros.
    | false = usa solo este archivo.
    |
    */
    'use_database' => env('SUBSCRIPTIONS_USE_DATABASE', true),

    // Dias antes del fin de trial para enviar recordatorios.
    'trial_reminder_days' => [7, 3, 1],

    'defaults' => [
        'plan' => 'campo',
        'cycle' => 'monthly', // monthly|yearly
    ],

    'plans' => [
        'campo' => [
            'code' => 'campo',
            'name' => 'AgroSys Campo',
            'prices' => [
                'monthly_mxn' => 459.00,
                // Sin descuento anual: 459 * 12 = 5508.00
                'yearly_mxn' => 5508.00,
            ],
            'limits' => [
                'max_sucursales' => 1,
                'devices_per_sucursal' => 1,
            ],
            'features' => [
                'bitacora' => false,
                'soporte_12h_6d' => false,
            ],
            'stripe' => [
                'monthly_price_id' => env('STRIPE_PRICE_CAMPO_MONTHLY'),
                'yearly_price_id' => env('STRIPE_PRICE_CAMPO_YEARLY'),
            ],
        ],

        'esencial' => [
            'code' => 'esencial',
            'name' => 'AgroSys Esencial',
            'prices' => [
                'monthly_mxn' => 999.00,
                // Ya con descuento del 10% sobre 12 meses:
                // 999 * 12 * 0.90 = 10789.20
                'yearly_mxn' => 10789.20,
            ],
            'limits' => [
                'max_sucursales' => 3,
                'devices_per_sucursal' => 2,
            ],
            'features' => [
                'bitacora' => true,
                'soporte_12h_6d' => false,
            ],
            'stripe' => [
                'monthly_price_id' => env('STRIPE_PRICE_ESENCIAL_MONTHLY'),
                'yearly_price_id' => env('STRIPE_PRICE_ESENCIAL_YEARLY'),
            ],
        ],

        'expert' => [
            'code' => 'expert',
            'name' => 'AgroSys Expert',
            'prices' => [
                'monthly_mxn' => 1299.00,
                // Ya con descuento del 15% sobre 12 meses:
                // 1299 * 12 * 0.85 = 13249.80
                'yearly_mxn' => 13249.80,
            ],
            'limits' => [
                'max_sucursales' => 5,
                'devices_per_sucursal' => 3,
            ],
            'features' => [
                'bitacora' => true,
                'soporte_12h_6d' => true,
            ],
            'stripe' => [
                'monthly_price_id' => env('STRIPE_PRICE_EXPERT_MONTHLY'),
                'yearly_price_id' => env('STRIPE_PRICE_EXPERT_YEARLY'),
            ],
        ],
    ],
];
