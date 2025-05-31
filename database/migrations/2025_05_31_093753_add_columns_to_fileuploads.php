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
        Schema::table('fileuploads', function (Blueprint $table) {
            //
            $table->integer('customer_id')->nullable()->after('partner_id');
            $table->integer('test_id')->nullable()->after('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fileuploads', function (Blueprint $table) {
            //

        });
    }
};
