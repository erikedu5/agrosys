<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('regimen_fiscal')->nullable()->after('facturapi_customer_id');
            $table->string('codigo_postal', 10)->nullable()->after('regimen_fiscal');
            $table->string('uso_cfdi', 5)->nullable()->after('codigo_postal');
            $table->string('email_facturacion')->nullable()->after('uso_cfdi');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn([
                'regimen_fiscal',
                'codigo_postal',
                'uso_cfdi',
                'email_facturacion',
            ]);
        });
    }
};
