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
        Schema::create('resin_armor_type_recipe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('armor_type_id')->constrained('resin_armor_types')->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained('resin_recipes')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);

            $table->unique(['armor_type_id', 'recipe_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resin_armor_type_recipe');
    }
};
