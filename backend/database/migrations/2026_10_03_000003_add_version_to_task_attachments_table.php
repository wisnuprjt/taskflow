<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_attachments', function (Blueprint $table) {
            $table->unsignedSmallInteger('version')->default(1)->after('file_name');
            // Re-uploading the same file name to a task creates the next version.
            $table->unique(['task_id', 'file_name', 'version']);
        });
    }

    public function down(): void
    {
        Schema::table('task_attachments', function (Blueprint $table) {
            $table->dropUnique(['task_id', 'file_name', 'version']);
            $table->dropColumn('version');
        });
    }
};
