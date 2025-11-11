<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('volunteer_profiles')) {
            Schema::create('volunteer_profiles', function (Blueprint $table) {
                $table->increments('volunteer_id');
                $table->unsignedInteger('import_id')->nullable();
                $table->unsignedInteger('location_id')->nullable();
                $table->unsignedInteger('course_id')->nullable();
                $table->string('full_name');
                $table->string('id_number')->nullable();
                $table->string('school_id')->nullable();
                $table->string('year_level')->nullable();
                $table->string('email')->nullable();
                $table->string('contact_number')->nullable();
                $table->string('emergency_contact')->nullable();
                $table->string('fb_messenger')->nullable();
                $table->text('certificates')->nullable();
                $table->text('class_schedule')->nullable();
                $table->enum('status', ['active','inactive'])->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('import_id')->references('import_id')->on('import_logs')->onDelete('set null');
                $table->foreign('location_id')->references('location_id')->on('locations')->onDelete('set null');
                $table->foreign('course_id')->references('course_id')->on('courses')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('volunteer_profiles')) {
            Schema::table('volunteer_profiles', function (Blueprint $table) {
                $table->dropForeign(['import_id']);
                $table->dropForeign(['location_id']);
                $table->dropForeign(['course_id']);
            });

            Schema::dropIfExists('volunteer_profiles');
        }
    }
};
