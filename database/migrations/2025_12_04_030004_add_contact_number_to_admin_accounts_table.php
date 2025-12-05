<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_accounts', function (Blueprint $table) {
            // Add the missing column
            $table->string('contact_number')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('admin_accounts', function (Blueprint $table) {
            // Rollback column
            $table->dropColumn('contact_number');
        });
    }
};

