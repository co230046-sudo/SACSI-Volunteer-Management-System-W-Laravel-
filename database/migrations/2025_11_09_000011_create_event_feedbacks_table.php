<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('event_feedbacks')) {
            Schema::create('event_feedbacks', function (Blueprint $table) {
                $table->increments('feedback_id');

                $table->unsignedInteger('event_id');
                $table->string('event_code', 50)->nullable();

                $table->unsignedInteger('volunteer_id')->nullable();

                // raw identity (walk-ins/unmatched)
                $table->string('full_name')->nullable();
                $table->string('school_id', 50)->nullable();
                $table->string('school_email')->nullable();

                $table->tinyInteger('rating')->nullable();

                // keep your original if you still need it
                $table->text('feedback_text')->nullable();

                // split fields from your final GForm
                $table->text('improve_next_time')->nullable();
                $table->text('issues_encountered')->nullable();
                $table->text('other_comments')->nullable();

                $table->timestamp('submitted_at')->nullable();
                $table->string('import_batch', 60)->nullable();

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
