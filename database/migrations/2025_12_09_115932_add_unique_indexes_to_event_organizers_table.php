<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::table('event_organizers', function (Blueprint $table) {
            $table->unique(['email']);
            $table->unique(['name']);
        });

    }

    public function down(): void
    {
        Schema::table('event_organizers', function (Blueprint $table) {
            $table->dropUnique('event_organizers_event_email_unique');
            $table->dropUnique('event_organizers_event_name_unique');
        });
    }
};
