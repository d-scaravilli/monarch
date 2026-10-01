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
        Schema::create('resin_path_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('position')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('link_route')->nullable();
            $table->string('link_anchor')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resin_path_steps');
    }
};
