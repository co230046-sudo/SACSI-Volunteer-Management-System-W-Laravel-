<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attendance_import_logs')) {
            Schema::create('attendance_import_logs', function (Blueprint $table) {
                $table->increments('import_id');
                $table->unsignedInteger('event_id');
                $table->unsignedInteger('admin_id')->nullable();
                $table->string('filename');
                $table->integer('total_records')->default(0);
                $table->integer('valid_count')->default(0);
                $table->integer('invalid_count')->default(0);
                $table->timestamp('import_date')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->foreign('event_id')->references('event_id')->on('events')->onDelete('cascade');
                $table->foreign('admin_id')->references('admin_id')->on('admin_accounts')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_import_logs');
    }
};
