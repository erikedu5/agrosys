<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('acceso_hasta')->nullable()->after('id_empresa');
        });

        // Alcance del auditor externo: id_sucursal null = todas las sucursales de la empresa.
        Schema::create('auditor_alcances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_empresa');
            $table->unsignedBigInteger('id_sucursal')->nullable();
            $table->timestamps();

            $table->foreign('id_usuario')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('id_empresa')->references('id')->on('empresas');
            $table->foreign('id_sucursal')->references('id')->on('sucursales');
            $table->unique(['id_usuario', 'id_empresa', 'id_sucursal'], 'auditor_alcances_unique');
        });

        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 30);
            $table->unsignedBigInteger('id_empresa');
            $table->unsignedBigInteger('id_sucursal');
            $table->string('status', 20)->default('borrador');
            $table->string('alcance_tipo', 20)->default('total');
            $table->json('alcance_filtros')->nullable();
            $table->boolean('conteo_ciego')->default(true);
            $table->unsignedBigInteger('corte_inicio_id')->nullable();
            $table->timestamp('iniciada_at')->nullable();
            $table->timestamp('conteo_cerrado_at')->nullable();
            $table->timestamp('finalizada_at')->nullable();
            $table->timestamp('cancelada_at')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->unsignedBigInteger('id_usuario_crea');
            $table->unsignedBigInteger('id_usuario_finaliza')->nullable();
            $table->unsignedBigInteger('id_usuario_cancela')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->foreign('id_empresa')->references('id')->on('empresas');
            $table->foreign('id_sucursal')->references('id')->on('sucursales');
            $table->foreign('id_usuario_crea')->references('id')->on('users');
            $table->unique(['id_empresa', 'folio']);
            $table->index(['id_sucursal', 'status']);
            $table->index(['id_empresa', 'created_at']);
        });

        Schema::create('auditoria_auditores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_auditoria');
            $table->unsignedBigInteger('id_usuario');
            $table->timestamps();

            $table->foreign('id_auditoria')->references('id')->on('auditorias')->cascadeOnDelete();
            $table->foreign('id_usuario')->references('id')->on('users');
            $table->unique(['id_auditoria', 'id_usuario']);
        });

        // Snapshot inmutable por producto: conserva el reporte aunque el catálogo cambie.
        Schema::create('auditoria_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_auditoria');
            $table->unsignedBigInteger('id_producto')->nullable();
            $table->string('descripcion_libre')->nullable();
            $table->string('nombre');
            $table->string('tamano')->nullable();
            $table->string('barcode')->nullable();
            $table->unsignedBigInteger('id_marca')->nullable();
            $table->string('marca_nombre')->nullable();
            $table->string('clasificacion_nombre')->nullable();
            $table->decimal('costo_unitario', 12, 2)->default(0);
            $table->decimal('cantidad_sistema_inicio', 12, 2)->default(0);
            $table->unsignedTinyInteger('ronda')->default(1);
            $table->decimal('cantidad_contada', 12, 2)->nullable();
            $table->decimal('contado_r1', 12, 2)->nullable();
            $table->timestamp('contado_at')->nullable();
            $table->unsignedBigInteger('corte_conteo_id')->nullable();
            $table->decimal('movimientos_durante', 12, 2)->default(0);
            $table->json('desglose_movimientos')->nullable();
            $table->decimal('ventas_offline_tardias', 12, 2)->default(0);
            $table->decimal('esperado', 12, 2)->nullable();
            $table->decimal('diferencia', 12, 2)->nullable();
            $table->decimal('transito_salida', 12, 2)->default(0);
            $table->decimal('transito_entrada', 12, 2)->default(0);
            $table->string('estado', 20)->default('pendiente');
            $table->boolean('no_esperado')->default(false);
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->foreign('id_auditoria')->references('id')->on('auditorias')->cascadeOnDelete();
            $table->foreign('id_producto')->references('id')->on('productos');
            $table->unique(['id_auditoria', 'id_producto']);
            $table->index(['id_auditoria', 'estado']);
            $table->index(['id_producto']);
        });

        // Capturas append-only: corregir = anular + capturar de nuevo.
        Schema::create('auditoria_conteos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_auditoria_item');
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedTinyInteger('ronda')->default(1);
            $table->decimal('cantidad', 12, 2);
            $table->string('ubicacion', 120)->nullable();
            $table->unsignedBigInteger('corte_id')->default(0);
            $table->timestamp('anulado_at')->nullable();
            $table->unsignedBigInteger('anulado_por')->nullable();
            $table->timestamps();

            $table->foreign('id_auditoria_item')->references('id')->on('auditoria_items')->cascadeOnDelete();
            $table->foreign('id_usuario')->references('id')->on('users');
            $table->index(['id_auditoria_item', 'ronda']);
        });

        Schema::table('alta_inventarios', function (Blueprint $table) {
            $table->index(['id_sucursal', 'id_producto', 'id'], 'alta_inventarios_sucursal_producto_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('alta_inventarios', function (Blueprint $table) {
            $table->dropIndex('alta_inventarios_sucursal_producto_id_index');
        });
        Schema::dropIfExists('auditoria_conteos');
        Schema::dropIfExists('auditoria_items');
        Schema::dropIfExists('auditoria_auditores');
        Schema::dropIfExists('auditorias');
        Schema::dropIfExists('auditor_alcances');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('acceso_hasta');
        });
    }
};
