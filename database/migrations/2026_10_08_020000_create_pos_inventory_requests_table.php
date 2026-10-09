<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', fn (Blueprint $table) => $table->decimal('ieps', 10, 2)->change());
        Schema::create('pos_inventory_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('device_id')->index();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('branch_id')->constrained('sucursales');
            $table->string('action', 30);
            $table->char('request_hash', 64);
            $table->json('result');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_inventory_requests');
        Schema::table('productos', fn (Blueprint $table) => $table->unsignedBigInteger('ieps')->change());
    }
};
