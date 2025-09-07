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
        Schema::create('trigger_history', function (Blueprint $table) {
            $table->id();
            $table->integer("partner_id")->nullable()->default(0);
            $table->integer("customer_id")->nullable()->default(0);
            $table->string("type", 11)->nullable()->default(null)->comment("customer/partner");
            $table->string("testtype", 11)->nullable()->default(null)->comment("BAJ/PA...");
            $table->string("status", 11)->nullable()->default(null)->comment("add/delete");
            $table->integer("num")->nullable()->default(0);
            $table->string("testname", 128)->nullable()->default(null)->comment("テスト名");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trigger_history');
    }
};
