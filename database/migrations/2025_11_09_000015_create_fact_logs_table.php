<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fact_logs')) {
            Schema::create('fact_logs', function (Blueprint $table) {
                $table->increments('fact_log_id');
                $table->unsignedInteger('admin_id')->nullable();
                $table->string('entity_type');
                $table->unsignedInteger('entity_id')->nullable();
                $table->string('action');
                $table->text('details')->nullable();
                $table->timestamp('timestamp')->useCurrent();

                $table->foreign('admin_id')->references('admin_id')->on('admin_accounts')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fact_logs');
    }
};
