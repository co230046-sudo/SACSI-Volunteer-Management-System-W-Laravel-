<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table) {
                $table->increments('event_id');
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('venue')->nullable();
                $table->unsignedInteger('location_id')->nullable();
                $table->timestamp('start_datetime')->nullable();
                $table->timestamp('end_datetime')->nullable();
                $table->enum('status', ['planned','ongoing','completed','cancelled'])->default('planned');
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('location_id')->references('location_id')->on('locations')->onDelete('set null');
                $table->foreign('created_by')->references('admin_id')->on('admin_accounts')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
