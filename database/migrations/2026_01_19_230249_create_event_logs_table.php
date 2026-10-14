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
        Schema::create('event_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique()->index();
            $table->string('event_type', 100)->index(); // PROCESAR_VENTA, CREAR_TRANSFERENCIA, etc.
            $table->enum('status', ['started', 'success', 'error', 'warning'])->default('started')->index();

            // Contexto del evento
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('sucursal_id')->nullable()->index();
            $table->json('initial_context')->nullable(); // Contexto inicial del evento

            // Pasos del evento
            $table->json('steps')->nullable(); // Array de pasos ejecutados
            $table->integer('steps_count')->default(0);

            // Resultado
            $table->json('result')->nullable(); // Resultado final si es exitoso

            // Error (si aplica)
            $table->text('error_message')->nullable();
            $table->string('error_file')->nullable();
            $table->integer('error_line')->nullable();
            $table->text('error_trace')->nullable();

            // Metadatos
            $table->float('duration')->nullable(); // Duración en segundos
            $table->timestamp('started_at')->index();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // Índices compuestos para búsquedas comunes
            $table->index(['event_type', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['sucursal_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_logs');
    }
};
