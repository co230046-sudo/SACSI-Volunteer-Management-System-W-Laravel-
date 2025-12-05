<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Add column if missing
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'event_code')) {
                $table->string('event_code', 16)->nullable()->after('event_id');
            }
        });

        // 2) Add unique index if missing (NO Doctrine)
        // Works on MySQL. This checks information_schema.statistics.
        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('database()'))
            ->where('table_name', 'events')
            ->where('index_name', 'events_event_code_unique')
            ->exists();

        if (!$indexExists) {
            Schema::table('events', function (Blueprint $table) {
                $table->unique('event_code', 'events_event_code_unique');
            });
        }
    }

    public function down(): void
    {
        // Drop unique index if exists (NO Doctrine)
        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('database()'))
            ->where('table_name', 'events')
            ->where('index_name', 'events_event_code_unique')
            ->exists();

        if ($indexExists) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropUnique('events_event_code_unique');
            });
        }

        // Drop column if exists
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'event_code')) {
                $table->dropColumn('event_code');
            }
        });
    }
};
