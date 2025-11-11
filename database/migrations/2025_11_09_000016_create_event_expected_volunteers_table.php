<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('event_expected_volunteers')) {
            Schema::create('event_expected_volunteers', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('event_id');
                $table->unsignedInteger('volunteer_id');
                $table->enum('status', ['expected','cancelled'])->default('expected');
                $table->timestamps();

                $table->foreign('event_id')
                      ->references('event_id')->on('events')
                      ->onDelete('cascade');

                $table->foreign('volunteer_id')
                      ->references('volunteer_id')->on('volunteer_profiles')
                      ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_expected_volunteers');
    }
};
