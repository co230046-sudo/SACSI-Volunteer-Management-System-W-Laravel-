<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'max_volunteers')) {
                $table->unsignedInteger('max_volunteers')->nullable()->after('end_datetime');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'max_volunteers')) {
                $table->dropColumn('max_volunteers');
            }
        });
    }
};
