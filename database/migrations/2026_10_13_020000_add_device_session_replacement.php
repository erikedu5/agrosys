<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->unsignedBigInteger('device_session_version')->default(0));
        Schema::table('offline_devices', fn (Blueprint $table) => $table->timestamp('session_closed_at')->nullable());
    }

    public function down(): void
    {
        Schema::table('offline_devices', fn (Blueprint $table) => $table->dropColumn('session_closed_at'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('device_session_version'));
    }
};
