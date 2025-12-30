<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pdfDownloads', function (Blueprint $table) {
            //
            $table->text('admin_cronfile_path')
              ->nullable()
              ->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdfDownloads', function (Blueprint $table) {
            //
            $table->dropColumn('admin_cronfile_path');
        });
    }
};
