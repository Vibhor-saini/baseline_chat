<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix the unique constraint on message_reactions so that
 * each user can only have ONE reaction per message (regardless of emoji).
 *
 * Previously: UNIQUE(message_id, user_id, emoji)  — allowed multiple emoji per user
 * Correct:    UNIQUE(message_id, user_id)          — one reaction per user per message
 *
 * MySQL quirk: the old composite unique index was serving as the backing index for
 * the message_id FK. We must add a plain index on message_id first, then drop the
 * old unique, then add the new two-column unique.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Deduplicate rows: keep only the highest-id row per (message_id, user_id) pair
        DB::statement("
            DELETE mr1 FROM message_reactions mr1
            INNER JOIN message_reactions mr2
                ON mr1.message_id = mr2.message_id
               AND mr1.user_id    = mr2.user_id
               AND mr1.id         < mr2.id
        ");

        Schema::table('message_reactions', function (Blueprint $table) {
            // 2. Add a plain index on message_id so MySQL has a backing index for the FK
            $table->index('message_id', 'message_reactions_message_id_index');
        });

        Schema::table('message_reactions', function (Blueprint $table) {
            // 3. Now drop the old three-column unique (FK no longer needs it)
            $table->dropUnique('message_reactions_message_id_user_id_emoji_unique');
        });

        Schema::table('message_reactions', function (Blueprint $table) {
            // 4. Add the correct two-column unique: one reaction per user per message
            $table->unique(['message_id', 'user_id'], 'message_reactions_message_id_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('message_reactions', function (Blueprint $table) {
            $table->dropUnique('message_reactions_message_id_user_id_unique');
        });

        Schema::table('message_reactions', function (Blueprint $table) {
            $table->unique(['message_id', 'user_id', 'emoji'], 'message_reactions_message_id_user_id_emoji_unique');
        });

        Schema::table('message_reactions', function (Blueprint $table) {
            $table->dropIndex('message_reactions_message_id_index');
        });
    }
};
