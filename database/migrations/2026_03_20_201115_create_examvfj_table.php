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
        Schema::create('examvfj', function (Blueprint $table) {
            $table->id(); // bigint UN AI PK

            $table->integer('testparts_id');
            $table->integer('exam_id');

            $table->timestamp('starttime')->nullable();
            $table->timestamp('endtime')->nullable();

            // q1〜q66
            for ($i = 1; $i <= 66; $i++) {
                $table->integer("q{$i}")->nullable();
            }

            // w1〜w12（小数：幅広）
            for ($i = 1; $i <= 12; $i++) {
                $table->decimal("w{$i}", 10, 4)->nullable();
            }

            // dev1〜dev12（小数：幅広）
            for ($i = 1; $i <= 12; $i++) {
                $table->decimal("dev{$i}", 10, 4)->nullable();
            }

            // avg, std（小数）
            $table->decimal('avg', 10, 4)->nullable();
            $table->decimal('std', 10, 4)->nullable();

            $table->integer('status')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examvfj');
    }
};
