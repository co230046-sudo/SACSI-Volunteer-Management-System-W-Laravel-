<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('event_attendances')) {
            Schema::create('event_attendances', function (Blueprint $table) {
                $table->increments('attendance_id');

                $table->unsignedInteger('event_id');

                // allow walk-ins/unmatched rows
                $table->unsignedInteger('volunteer_id')->nullable();

                $table->enum('status', ['present', 'late'])->default('present');

                // import/manual (NO ->after() in create)
                $table->string('source', 20)->default('import');

                $table->boolean('walk_in')->default(false);

                // raw identity
                $table->string('full_name')->nullable();
                $table->string('school_id', 50)->nullable();
                $table->string('school_email')->nullable();

                // tracking
                $table->string('event_code', 50)->nullable();
                $table->timestamp('attendance_time')->nullable();
                $table->string('import_batch', 60)->nullable();

                $table->foreign('event_id')
                    ->references('event_id')->on('events')
                    ->onDelete('cascade');

                $table->foreign('volunteer_id')
                    ->references('volunteer_id')->on('volunteer_profiles')
                    ->onDelete('set null');

                // Optional (add later if needed)
                // $table->unique(['event_id','school_id'], 'uniq_event_schoolid');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attendances');
    }
};
