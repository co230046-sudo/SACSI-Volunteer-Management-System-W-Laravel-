<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('event_organizers')) {
            Schema::create('event_organizers', function (Blueprint $table) {
                $table->increments('organizer_id');
                $table->unsignedInteger('event_id');
                $table->foreign('event_id')->references('event_id')->on('events')->onDelete('cascade');
                $table->string('organizer_name');
                $table->string('organizer_email')->nullable();
                $table->string('organizer_contact')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_organizers');
    }
};
