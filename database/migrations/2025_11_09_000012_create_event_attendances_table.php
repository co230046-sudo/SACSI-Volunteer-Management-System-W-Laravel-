<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('event_attendances')) {
            Schema::create('event_attendances', function (Blueprint $table) {
                $table->increments('attendance_id');
                $table->unsignedInteger('event_id');
                $table->unsignedInteger('volunteer_id');
                $table->enum('status', ['present','absent','late'])->default('present');
                $table->timestamp('attendance_time')->nullable();

                $table->foreign('event_id')->references('event_id')->on('events')->onDelete('cascade');
                $table->foreign('volunteer_id')->references('volunteer_id')->on('volunteer_profiles')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attendances');
    }
};
