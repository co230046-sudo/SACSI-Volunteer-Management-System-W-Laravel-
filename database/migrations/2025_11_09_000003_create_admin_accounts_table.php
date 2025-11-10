<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_accounts')) {
            Schema::create('admin_accounts', function (Blueprint $table) {
                $table->increments('admin_id');
                $table->string('username', 100);
                $table->string('password');
                $table->string('profile_picture')->nullable();
                $table->string('email');
                $table->string('full_name')->nullable();
                $table->enum('role', ['admin','super_admin'])->default('admin');
                $table->enum('status', ['active','inactive','suspended'])->default('active');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_accounts');
    }
};
