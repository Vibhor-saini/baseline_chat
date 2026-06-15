<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Message type: text | image | file
            $table->string('type')->default('text')->after('body');

            // Delivery / read timestamps (null = not yet)
            $table->timestamp('delivered_at')->nullable()->after('type');
            $table->timestamp('read_at')->nullable()->after('delivered_at');

            // Soft delete — shows "This message was deleted"
            $table->softDeletes()->after('read_at');

            // Forward chain — nullable FK to the original message
            $table->foreignId('forwarded_from_id')
                ->nullable()
                ->after('deleted_at')
                ->constrained('messages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['forwarded_from_id']);
            $table->dropColumn([
                'type', 'delivered_at', 'read_at', 'deleted_at', 'forwarded_from_id',
            ]);
        });
    }
};
