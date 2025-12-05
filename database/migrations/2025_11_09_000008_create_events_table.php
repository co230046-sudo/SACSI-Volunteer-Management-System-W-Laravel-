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

                $table->string('event_code')->nullable()->unique();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('venue')->nullable();

                // Location (barangay)
                $table->unsignedInteger('location_id')->nullable();

                // District (simple number, no FK!)
                $table->unsignedTinyInteger('district_id')->nullable();

                // Event Type
                $table->unsignedInteger('event_type_id')->nullable();

                // Datetime
                $table->timestamp('start_datetime')->nullable();
                $table->timestamp('end_datetime')->nullable();

                // Optional capacity (if you use it elsewhere)
                $table->unsignedInteger('max_volunteers')->nullable();

                $table->enum('status', ['planned','ongoing','completed','cancelled'])
                    ->default('planned');

                // Created by admin
                $table->unsignedInteger('created_by')->nullable();

                // ✅ Cancel fields
                $table->text('cancel_reason')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->unsignedInteger('cancelled_by')->nullable();

                $table->timestamps();

                // Foreign keys
                $table->foreign('location_id')
                    ->references('location_id')
                    ->on('locations')
                    ->onDelete('set null');

                $table->foreign('event_type_id')
                    ->references('event_type_id')
                    ->on('event_types')
                    ->onDelete('set null');

                $table->foreign('created_by')
                    ->references('admin_id')
                    ->on('admin_accounts')
                    ->onDelete('set null');

                // ✅ FK for cancelled_by (recommended)
                $table->foreign('cancelled_by')
                    ->references('admin_id')
                    ->on('admin_accounts')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
