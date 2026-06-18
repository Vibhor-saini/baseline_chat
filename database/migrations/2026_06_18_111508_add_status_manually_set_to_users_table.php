<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // When true, online-presence updates must NOT override status.
            // Only manual status selection (from profile panel) sets this.
            if (!Schema::hasColumn('users', 'status_manually_set')) {
                $table->boolean('status_manually_set')
                      ->default(false)
                      ->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'status_manually_set')) {
                $table->dropColumn('status_manually_set');
            }
        });
    }
};
