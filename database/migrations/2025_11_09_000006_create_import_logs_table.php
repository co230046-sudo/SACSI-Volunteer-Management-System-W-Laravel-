<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('import_logs')) {
            Schema::create('import_logs', function (Blueprint $table) {
                $table->increments('import_id');
                $table->string('file_name');
                $table->unsignedInteger('admin_id')->nullable();
                $table->integer('total_records')->default(0);
                $table->integer('valid_count')->default(0);
                $table->integer('invalid_count')->default(0);
                $table->integer('duplicate_count')->default(0);
                $table->enum('status', ['Pending','Completed','Cancelled','Reset','Failed'])->default('Pending');
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->foreign('admin_id')->references('admin_id')->on('admin_accounts')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
