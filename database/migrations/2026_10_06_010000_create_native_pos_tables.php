<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', fn (Blueprint $table) => $table->uuid('pos_device_id')->nullable());
        Schema::table('offline_devices', function (Blueprint $table) {
            $table->string('client_kind')->default('web');
            $table->timestamp('revoked_at')->nullable();
        });
        Schema::table('offline_operations', function (Blueprint $table) {
            $table->char('request_hash', 64)->nullable();
            $table->uuid('pos_lease_id')->nullable();
        });
        Schema::create('pos_login_challenges', function (Blueprint $table) {
            $table->char('id', 64)->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->uuid('device_id');
            $table->string('device_name', 100);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at')->index();
        });
        Schema::create('pos_offline_leases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('device_id')->index();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('branch_id')->constrained('sucursales');
            $table->json('claims');
            $table->timestamp('issued_at');
            $table->timestamp('expires_at')->index();
        });
        Schema::create('pos_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('device_id')->index();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('branch_id')->constrained('sucursales');
            $table->json('payload');
            $table->timestamp('created_at');
            $table->timestamp('expires_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_snapshots');
        Schema::dropIfExists('pos_offline_leases');
        Schema::dropIfExists('pos_login_challenges');
        Schema::table('offline_operations', fn (Blueprint $table) => $table->dropColumn(['request_hash', 'pos_lease_id']));
        Schema::table('offline_devices', fn (Blueprint $table) => $table->dropColumn(['client_kind', 'revoked_at']));
        Schema::table('personal_access_tokens', fn (Blueprint $table) => $table->dropColumn('pos_device_id'));
    }
};
