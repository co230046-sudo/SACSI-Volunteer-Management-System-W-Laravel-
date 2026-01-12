<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_types', function (Blueprint $table) {
            $table->increments('event_type_id');
            $table->string('type_key', 50)->unique(); // e.g. "cleanup", "seminar"
            $table->string('label', 100);             // e.g. "Cleanup Drive", "Seminar"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_types');
    }   
};
