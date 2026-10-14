<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('facturapi_api_key')->nullable()->after('motivo_bloqueo');
            $table->boolean('facturapi_sandbox')->default(false)->after('facturapi_api_key');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->string('facturapi_customer_id')->nullable()->after('rfc');
        });

        Schema::table('facturas', function (Blueprint $table) {
            $table->string('facturapi_invoice_id')->nullable()->after('facturaCompleta');
            $table->string('facturapi_uuid')->nullable()->after('facturapi_invoice_id');
            $table->string('facturapi_pdf_url')->nullable()->after('facturapi_uuid');
            $table->string('facturapi_xml_url')->nullable()->after('facturapi_pdf_url');
            $table->string('factura_status')->nullable()->after('facturapi_xml_url');
            $table->text('factura_error')->nullable()->after('factura_status');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['facturapi_api_key', 'facturapi_sandbox']);
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('facturapi_customer_id');
        });

        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn([
                'facturapi_invoice_id',
                'facturapi_uuid',
                'facturapi_pdf_url',
                'facturapi_xml_url',
                'factura_status',
                'factura_error',
            ]);
        });
    }
};
