<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transferencias', function (Blueprint $table) {
            $table->unsignedBigInteger('id_usuario_envia')->after('id_sucursal_destino');
            $table->unsignedBigInteger('id_usuario_recibe')->nullable()->after('id_usuario_envia');
            $table->timestamp('fecha_envio')->nullable()->after('id_usuario_recibe');
            $table->timestamp('fecha_recepcion')->nullable()->after('fecha_envio');

            // Foreign keys
            $table->foreign('id_usuario_envia')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('id_usuario_recibe')->references('id')->on('users')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transferencias', function (Blueprint $table) {
            $table->dropForeign(['id_usuario_envia']);
            $table->dropForeign(['id_usuario_recibe']);
            $table->dropColumn(['id_usuario_envia', 'id_usuario_recibe', 'fecha_envio', 'fecha_recepcion']);
        });
    }
};
