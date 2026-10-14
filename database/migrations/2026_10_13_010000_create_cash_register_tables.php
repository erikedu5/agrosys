<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('active_key')->nullable()->unique();
            $table->bigInteger('opening_cents');
            $table->bigInteger('expected_cents')->nullable();
            $table->bigInteger('counted_cents')->nullable();
            $table->bigInteger('difference_cents')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->index(['branch_id', 'user_id', 'opened_at']);
        });
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('session_id')->constrained('cash_sessions');
            $table->string('kind', 30);
            $table->bigInteger('amount_cents');
            $table->string('source')->unique();
            $table->string('description', 255);
            $table->timestamp('created_at');
        });
        Schema::create('cash_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('request_hash', 64);
            $table->text('result');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_requests');
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('cash_sessions');
    }
};
