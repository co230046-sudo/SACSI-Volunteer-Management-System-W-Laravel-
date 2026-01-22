<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create pivot table
        if (!Schema::hasTable('event_event_organizer')) {
            Schema::create('event_event_organizer', function (Blueprint $table) {
                $table->unsignedInteger('event_id');
                $table->unsignedInteger('organizer_id');

                $table->foreign('event_id')->references('event_id')->on('events')->onDelete('cascade');
                $table->foreign('organizer_id')->references('organizer_id')->on('event_organizers')->onDelete('cascade');

                $table->primary(['event_id','organizer_id']);
                $table->timestamps();
            });
        }

        // 2. Remove old one-to-many column SAFELY
        if (Schema::hasColumn('event_organizers', 'event_id')) {
            Schema::table('event_organizers', function (Blueprint $table) {
                $table->dropForeign(['event_id']);
                $table->dropColumn('event_id');
            });
        }
    }


    public function down(): void
    {
        Schema::dropIfExists('event_event_organizer');
    }
};
