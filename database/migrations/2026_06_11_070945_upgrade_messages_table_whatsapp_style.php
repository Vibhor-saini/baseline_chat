<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // ── type column ───────────────────────────────────────────────
            // Use ->after('body') only when 'body' exists (local upgraded DB).
            // On a fresh Railway DB the messages table comes from the initial
            // migration which has no 'body' column, so we skip the position hint.
            if (!Schema::hasColumn('messages', 'type')) {
                if (Schema::hasColumn('messages', 'body')) {
                    $table->string('type')->default('text')->after('body');
                } else {
                    $table->string('type')->default('text');
                }
            }

            // ── delivered_at ──────────────────────────────────────────────
            if (!Schema::hasColumn('messages', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable();
            }

            // ── read_at ───────────────────────────────────────────────────
            if (!Schema::hasColumn('messages', 'read_at')) {
                $table->timestamp('read_at')->nullable();
            }

            // ── deleted_at (soft deletes) ─────────────────────────────────
            if (!Schema::hasColumn('messages', 'deleted_at')) {
                $table->softDeletes();
            }

            // ── forwarded_from_id ─────────────────────────────────────────
            if (!Schema::hasColumn('messages', 'forwarded_from_id')) {
                $table->foreignId('forwarded_from_id')
                    ->nullable()
                    ->constrained('messages')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'forwarded_from_id')) {
                $table->dropForeign(['forwarded_from_id']);
                $table->dropColumn('forwarded_from_id');
            }
            foreach (['type', 'delivered_at', 'read_at', 'deleted_at'] as $col) {
                if (Schema::hasColumn('messages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
