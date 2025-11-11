<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('event_logs')) {
            Schema::create('event_logs', function (Blueprint $table) {
                $table->increments('log_id');
                $table->unsignedInteger('event_id');
                $table->unsignedInteger('admin_id')->nullable();
                $table->string('action');
                $table->text('details')->nullable();
                $table->timestamp('timestamp')->useCurrent();

                $table->foreign('event_id')->references('event_id')->on('events')->onDelete('cascade');
                $table->foreign('admin_id')->references('admin_id')->on('admin_accounts')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_logs');
    }
};
