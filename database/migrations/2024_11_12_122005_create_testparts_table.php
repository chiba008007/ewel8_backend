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
        Schema::create('testparts', function (Blueprint $table) {
            $table->id();
            $table->integer('test_id')->length(11);
            $table->integer('timelimit')->length(11)->nullable();
            $table->string('code',11);
            $table->integer('threeflag')->length(1)->default(0)->nullable();
            $table->integer('weightflag')->length(1)->default(0)->nullable();
            $table->string('weight1',11)->default('0')->nullable();
            $table->string('weight2',11)->default('0')->nullable();
            $table->string('weight3',11)->default('0')->nullable();
            $table->string('weight4',11)->default('0')->nullable();
            $table->string('weight5',11)->default('0')->nullable();
            $table->string('weight6',11)->default('0')->nullable();
            $table->string('weight7',11)->default('0')->nullable();
            $table->string('weight8',11)->default('0')->nullable();
            $table->string('weight9',11)->default('0')->nullable();
            $table->string('weight10',11)->default('0')->nullable();
            $table->string('weight11',11)->default('0')->nullable();
            $table->string('weight12',11)->default('0')->nullable();
            $table->string('weight13',11)->default('0')->nullable();
            $table->string('weight14',11)->default('0')->nullable();
            $table->integer('status')->length(1)->default('0')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testparts');
    }
};
