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
        Schema::create('popular', function (Blueprint $table) {
            $table->id();

            $table->float('dev1')->nullable();
            $table->float('dev2')->nullable();
            $table->float('dev3')->nullable();
            $table->float('dev4')->nullable();
            $table->float('dev5')->nullable();
            $table->float('dev6')->nullable();
            $table->float('dev7')->nullable();
            $table->float('dev8')->nullable();
            $table->float('dev9')->nullable();
            $table->float('dev10')->nullable();
            $table->float('dev11')->nullable();
            $table->float('dev12')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('popular');
    }
};
