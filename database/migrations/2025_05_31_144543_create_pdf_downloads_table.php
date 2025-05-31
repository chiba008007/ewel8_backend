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
        Schema::create('pdfDownloads', function (Blueprint $table) {
            $table->id();
            $table->integer("partner_id")->nullable()->default(0);
            $table->integer("customer_id")->nullable()->default(0);
            $table->integer("test_id")->nullable()->default(0);
            $table->integer("admin_id")->nullable()->default(0);
            $table->integer("code")->nullable()->default(1)->comment("1:ファイル化 2:ZIP化");
            $table->integer("type")->nullable()->default(0)->comment("0:実施前 1:実施中 2:実施済");
            $table->integer("status")->nullable()->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdfDownloads');
    }
};
