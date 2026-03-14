<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->decimal('monthly_price_mxn', 10, 2);
            $table->decimal('yearly_price_mxn', 10, 2)->nullable();
            $table->decimal('annual_discount_percent', 5, 2)->nullable();
            $table->unsignedInteger('max_sucursales');
            $table->unsignedInteger('devices_per_sucursal');
            $table->boolean('has_bitacora')->default(false);
            $table->boolean('has_soporte_12h_6d')->default(false);
            $table->string('stripe_monthly_price_id', 120)->nullable();
            $table->string('stripe_yearly_price_id', 120)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('subscription_plans')->insert([
            [
                'code' => 'campo',
                'name' => 'AgroSys Campo',
                'monthly_price_mxn' => 459.00,
                'yearly_price_mxn' => 5508.00,
                'annual_discount_percent' => 0.00,
                'max_sucursales' => 1,
                'devices_per_sucursal' => 1,
                'has_bitacora' => false,
                'has_soporte_12h_6d' => false,
                'stripe_monthly_price_id' => env('STRIPE_PRICE_CAMPO_MONTHLY'),
                'stripe_yearly_price_id' => env('STRIPE_PRICE_CAMPO_YEARLY'),
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'esencial',
                'name' => 'AgroSys Esencial',
                'monthly_price_mxn' => 999.00,
                'yearly_price_mxn' => 10789.20,
                'annual_discount_percent' => 10.00,
                'max_sucursales' => 3,
                'devices_per_sucursal' => 2,
                'has_bitacora' => true,
                'has_soporte_12h_6d' => false,
                'stripe_monthly_price_id' => env('STRIPE_PRICE_ESENCIAL_MONTHLY'),
                'stripe_yearly_price_id' => env('STRIPE_PRICE_ESENCIAL_YEARLY'),
                'is_active' => true,
                'sort_order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'expert',
                'name' => 'AgroSys Expert',
                'monthly_price_mxn' => 1299.00,
                'yearly_price_mxn' => 13249.80,
                'annual_discount_percent' => 15.00,
                'max_sucursales' => 5,
                'devices_per_sucursal' => 3,
                'has_bitacora' => true,
                'has_soporte_12h_6d' => true,
                'stripe_monthly_price_id' => env('STRIPE_PRICE_EXPERT_MONTHLY'),
                'stripe_yearly_price_id' => env('STRIPE_PRICE_EXPERT_YEARLY'),
                'is_active' => true,
                'sort_order' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
