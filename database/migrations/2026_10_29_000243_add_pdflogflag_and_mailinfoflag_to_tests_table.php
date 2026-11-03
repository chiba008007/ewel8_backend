<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->boolean('pdflogflag')
                ->default(false)
                ->after('pdflimitcount');

            $table->boolean('mailinfoflag')
                ->default(false)
                ->after('pdflogflag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn(['pdflogflag', 'mailinfoflag']);
        });
    }
};
