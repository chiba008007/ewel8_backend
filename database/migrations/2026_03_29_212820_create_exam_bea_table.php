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
        Schema::create('exam_bea', function (Blueprint $table) {
            $table->id();
            $table->integer('testparts_id');
            $table->integer('exam_id');

            $table->timestamp('starttime')->nullable();
            $table->timestamp('endtime')->nullable();
            $table->timestamp('limittime')->nullable();

            // q1〜q106
            for ($i = 1; $i <= 106; $i++) {
                $table->integer("q{$i}")->nullable();
            }
            $table->decimal('sougo', 10, 4)->nullable();
            $table->decimal('yomitori', 10, 4)->nullable();
            $table->decimal('rikai', 10, 4)->nullable();
            $table->decimal('sentaku', 10, 4)->nullable();
            $table->decimal('kirikae', 10, 4)->nullable();
            $table->decimal('jyoho', 10, 4)->nullable();

            $table->integer('status')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_bea');
    }
};
