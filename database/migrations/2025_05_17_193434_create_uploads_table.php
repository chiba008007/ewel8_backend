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
        Schema::create('csvuploads', function (Blueprint $table) {
            $table->id();
            $table->integer('test_id')->length(11);
            $table->integer('customer_id')->length(11);
            $table->string('filename', 512)->nullable();
            $table->string('filepath', 512)->nullable();
            $table->integer('type')->length(1)->default(1)->comment("1:成功 2:失敗");
            $table->integer('total')->nullable();
            $table->integer('notrows')->nullable();
            $table->string('memo', 1280)->nullable();
            $table->integer('status')->length(1)->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
