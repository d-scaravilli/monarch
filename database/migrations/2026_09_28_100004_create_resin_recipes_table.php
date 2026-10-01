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
        Schema::create('resin_recipes', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('recipe_category_id')->nullable()->constrained('resin_recipe_categories')->nullOnDelete();
            $table->string('title');
            $table->string('who')->nullable();
            $table->text('tip')->nullable();
            $table->boolean('is_inline')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resin_recipes');
    }
};
