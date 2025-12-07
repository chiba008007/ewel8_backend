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
        Schema::create('admin_page_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->index();   // 必須

            $table->string('route_name');
            $table->string('title')->nullable();
            $table->string('path');
            $table->json('params')->nullable();

            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();

            // created_at, updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_page_logs');
    }
};
