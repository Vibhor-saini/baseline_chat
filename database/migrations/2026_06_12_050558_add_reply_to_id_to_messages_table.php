<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('messages', 'reply_to_id')) {
            return; // already exists — nothing to do
        }

        Schema::table('messages', function (Blueprint $table) {
            // Reply chain — nullable FK to the message being replied to.
            // No ->after() hint — column position is irrelevant and ->after()
            // throws on DBs where the referenced column doesn't exist yet.
            $table->foreignId('reply_to_id')
                ->nullable()
                ->constrained('messages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'reply_to_id')) {
                $table->dropForeign(['reply_to_id']);
                $table->dropColumn('reply_to_id');
            }
        });
    }
};
