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
        Schema::table('testparts', function (Blueprint $table) {
            $table->string('examPersonName')->nullable()->default('社員（または契約社員等）')->after('weight14');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('testparts', function (Blueprint $table) {
            $table->dropColumn('examPersonName');
        });
    }
};
