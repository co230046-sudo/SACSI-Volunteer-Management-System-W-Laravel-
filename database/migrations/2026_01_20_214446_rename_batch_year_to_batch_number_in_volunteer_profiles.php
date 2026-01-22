<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('volunteer_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('volunteer_profiles', 'batch_year')) {
                $table->renameColumn('batch_year', 'batch_number');
            }
        });
    }

    public function down()
    {
        Schema::table('volunteer_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('volunteer_profiles', 'batch_number')) {
                $table->renameColumn('batch_number', 'batch_year');
            }
        });
    }
};
