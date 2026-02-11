<?php

return [
    'plans' => [
        [
            'name' => 'AgroSys Campo',
            'slug' => 'campo',
            'stripe_price_id' => env('STRIPE_PRICE_ID_CAMPO'),
            'price' => 459.00,
            'description' => 'Ideal para pequeños productores',
            'features' => [
                '1 dispositivo',
                '1 sucursal',
            ],
        ],
        [
            'name' => 'AgroSys Esencial',
            'slug' => 'esencial',
            'stripe_price_id' => env('STRIPE_PRICE_ID_ESENCIAL'),
            'price' => 999.00,
            'description' => 'Para negocios en crecimiento',
            'features' => [
                'Hasta 2 dispositivos por sucursal',
                'Sistema bitácora agrícola',
                'Máximo 3 sucursales',
                'Descuento 10% pago anual',
            ],
            'highlight' => true,
        ],
        [
            'name' => 'AgroSys Expert',
            'slug' => 'expert',
            'stripe_price_id' => env('STRIPE_PRICE_ID_EXPERT'),
            'price' => 1299.00,
            'description' => 'Para operaciones grandes',
            'features' => [
                'Hasta 3 dispositivos por sucursal',
                'Sistema bitácora agrícola (clientes ilimitados)',
                'Máximo 5 sucursales',
                'Soporte 12/6',
                'Descuento 15% pago anual',
            ],
        ],
    ],
];
