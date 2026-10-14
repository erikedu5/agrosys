<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->timestamp('manual_subscription_starts_at')->nullable()->after('trial_ends_at');
            $table->timestamp('manual_subscription_ends_at')->nullable()->after('manual_subscription_starts_at');
            $table->boolean('manual_subscription_blocked')->default(false)->after('manual_subscription_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'manual_subscription_starts_at',
                'manual_subscription_ends_at',
                'manual_subscription_blocked',
            ]);
        });
    }
};
