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
        Schema::create('resin_recipe_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('resin_recipes')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('role');
            $table->text('usage')->nullable();
            $table->boolean('optional')->default(false);
            $table->string('technique')->nullable();
            $table->unsignedTinyInteger('coverage')->nullable();
            $table->timestamps();

            $table->index(['recipe_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resin_recipe_steps');
    }
};
