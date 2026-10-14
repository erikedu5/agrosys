<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'monthly_price_mxn',
        'yearly_price_mxn',
        'annual_discount_percent',
        'max_sucursales',
        'devices_per_sucursal',
        'has_bitacora',
        'has_soporte_12h_6d',
        'stripe_monthly_price_id',
        'stripe_yearly_price_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'monthly_price_mxn' => 'float',
        'yearly_price_mxn' => 'float',
        'annual_discount_percent' => 'float',
        'max_sucursales' => 'integer',
        'devices_per_sucursal' => 'integer',
        'has_bitacora' => 'boolean',
        'has_soporte_12h_6d' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
