<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('volunteer_profiles', function (Blueprint $table) {
            $table->unsignedSmallInteger('batch_year')->nullable()->after('year_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('volunteer_profiles', function (Blueprint $table) {
            $table->dropColumn('batch_year');
        });
    }
};
