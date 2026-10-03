<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_attachments', function (Blueprint $table) {
            $table->string('scan_status', 16)->default('pending')->after('mime_type');
        });

        // Files uploaded before scanning existed are treated as already trusted.
        DB::table('task_attachments')->update(['scan_status' => 'clean']);
    }

    public function down(): void
    {
        Schema::table('task_attachments', function (Blueprint $table) {
            $table->dropColumn('scan_status');
        });
    }
};
