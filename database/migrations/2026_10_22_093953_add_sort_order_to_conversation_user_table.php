<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_user', function (Blueprint $table) {
            // NULL = not manually sorted (falls back to last_message_at ordering)
            // Lower value = higher in the list
            $table->unsignedInteger('sort_order')->nullable()->after('is_favorite');
        });
    }

    public function down(): void
    {
        Schema::table('conversation_user', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
