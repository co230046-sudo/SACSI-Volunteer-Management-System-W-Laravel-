<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('event_feedbacks')) {
            Schema::create('event_feedbacks', function (Blueprint $table) {
                $table->increments('feedback_id');
                $table->unsignedInteger('event_id');
                $table->unsignedInteger('volunteer_id')->nullable();
                $table->tinyInteger('rating')->nullable();
                $table->text('feedback_text')->nullable();
                $table->timestamp('submitted_at')->nullable();

                $table->foreign('event_id')->references('event_id')->on('events')->onDelete('cascade');
                $table->foreign('volunteer_id')->references('volunteer_id')->on('volunteer_profiles')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_feedbacks');
    }
};
