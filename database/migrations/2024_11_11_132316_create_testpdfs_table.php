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
        Schema::create('testpdfs', function (Blueprint $table) {
            $table->id();
            $table->integer('test_id')->length(11);
            $table->integer('pdf_id')->length(11);
            $table->integer('status')->length(11)->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testpdfs');
    }
};
