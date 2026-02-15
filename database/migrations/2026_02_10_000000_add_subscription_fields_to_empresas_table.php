<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            // Plan + limites (AgroSys)
            $table->string('plan_code', 32)->nullable()->after('numero_sucursales');
            $table->string('plan_cycle', 16)->nullable()->after('plan_code'); // monthly|yearly
            $table->unsignedInteger('numero_dispositivos_por_sucursal')->nullable()->after('plan_cycle');

            // Cashier / Stripe customer + default payment method
            $table->string('stripe_id')->nullable()->index()->after('numero_dispositivos_por_sucursal');
            $table->string('pm_type')->nullable()->after('stripe_id');
            $table->string('pm_last_four', 4)->nullable()->after('pm_type');
            $table->timestamp('trial_ends_at')->nullable()->after('pm_last_four');

            // Facturacion (opcional, solo captura de datos)
            $table->boolean('billing_requires_invoice')->default(false)->after('trial_ends_at');
            $table->string('billing_email')->nullable()->after('billing_requires_invoice');
            $table->string('billing_razon_social')->nullable()->after('billing_email');
            $table->string('billing_rfc', 32)->nullable()->after('billing_razon_social');
            $table->string('billing_regimen_fiscal', 8)->nullable()->after('billing_rfc');
            $table->string('billing_uso_cfdi', 8)->nullable()->after('billing_regimen_fiscal');
            $table->string('billing_codigo_postal', 12)->nullable()->after('billing_uso_cfdi');

            // Consentimiento legal (versionado)
            $table->string('terms_version', 32)->nullable()->after('billing_codigo_postal');
            $table->timestamp('terms_accepted_at')->nullable()->after('terms_version');
            $table->string('privacy_version', 32)->nullable()->after('terms_accepted_at');
            $table->timestamp('privacy_accepted_at')->nullable()->after('privacy_version');

            // Recordatorios (evitar envios duplicados)
            $table->timestamp('trial_reminder_7d_sent_at')->nullable()->after('privacy_accepted_at');
            $table->timestamp('trial_reminder_3d_sent_at')->nullable()->after('trial_reminder_7d_sent_at');
            $table->timestamp('trial_reminder_1d_sent_at')->nullable()->after('trial_reminder_3d_sent_at');
            $table->timestamp('trial_ended_sent_at')->nullable()->after('trial_reminder_1d_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'plan_code',
                'plan_cycle',
                'numero_dispositivos_por_sucursal',
                'stripe_id',
                'pm_type',
                'pm_last_four',
                'trial_ends_at',
                'billing_requires_invoice',
                'billing_email',
                'billing_razon_social',
                'billing_rfc',
                'billing_regimen_fiscal',
                'billing_uso_cfdi',
                'billing_codigo_postal',
                'terms_version',
                'terms_accepted_at',
                'privacy_version',
                'privacy_accepted_at',
                'trial_reminder_7d_sent_at',
                'trial_reminder_3d_sent_at',
                'trial_reminder_1d_sent_at',
                'trial_ended_sent_at',
            ]);
        });
    }
};

