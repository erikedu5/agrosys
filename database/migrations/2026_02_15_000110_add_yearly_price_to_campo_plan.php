<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        DB::table('subscription_plans')
            ->where('code', 'campo')
            ->update([
                'yearly_price_mxn' => 5508.00,
                'annual_discount_percent' => 0.00,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        DB::table('subscription_plans')
            ->where('code', 'campo')
            ->update([
                'yearly_price_mxn' => null,
                'annual_discount_percent' => null,
                'updated_at' => now(),
            ]);
    }
};
