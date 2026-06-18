<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('name');
            $table->string('avatar')->nullable()->after('email');
            $table->string('status_quote')->nullable(); 
            $table->enum('role', ['admin', 'user'])->default('user');
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_seen_at')->nullable();
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'avatar', 'status_quote', 'role', 'is_online', 'last_seen_at']);
        });
    }
};  