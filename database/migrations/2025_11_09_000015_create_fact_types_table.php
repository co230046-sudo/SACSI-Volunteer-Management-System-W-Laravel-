<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fact_types')) {
            Schema::create('fact_types', function (Blueprint $table) {
                $table->increments('fact_type_id');
                $table->string('type_name')->unique();
                $table->text('description')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fact_types');
    }
};
