<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_authenticate_logs')) {
            Schema::create('admin_authenticate_logs', function (Blueprint $table) {
                $table->increments('log_id');
                $table->unsignedInteger('admin_id')->nullable();
                $table->string('ip_address')->nullable();
                $table->enum('status', ['Success', 'Failed']);
                $table->text('reason')->nullable();
                $table->timestamp('login_time')->nullable();
                $table->timestamps();

                $table->foreign('admin_id')->references('admin_id')->on('admin_accounts')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_authenticate_logs');
    }
};
