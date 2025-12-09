<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('event_logs', function (Blueprint $table) {
            // 1) event_id must be nullable to allow SET NULL
            $table->unsignedInteger('event_id')->nullable()->change();

            // 2) drop the current FK (works if the FK name is default)
            $table->dropForeign(['event_id']);

            // 3) recreate FK with SET NULL instead of CASCADE
            $table->foreign('event_id')
                ->references('event_id')
                ->on('events')
                ->nullOnDelete(); // == ON DELETE SET NULL
        });
    }

    public function down(): void
    {
        Schema::table('event_logs', function (Blueprint $table) {
            $table->dropForeign(['event_id']);

            // revert back to NOT NULL and (optionally) cascade
            $table->unsignedInteger('event_id')->nullable(false)->change();

            $table->foreign('event_id')
                ->references('event_id')
                ->on('events')
                ->cascadeOnDelete();
        });
    }
};

