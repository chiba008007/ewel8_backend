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
        Schema::create('fileuploads', function (Blueprint $table) {
            $table->id();
            $table->integer("partner_id");
            $table->integer("admin_id");
            $table->string('filename',256)->nullable()->comment("ファイル名");
            $table->string('filepath',256)->nullable()->comment("表示ファイル名");
            $table->integer("status")->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fileuploads');
    }
};
