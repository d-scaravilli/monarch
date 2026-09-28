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
        Schema::create('resin_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained('resin_characters')->cascadeOnDelete();
            $table->foreignId('character_version_id')->nullable()->constrained('resin_character_versions')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('name');
            $table->string('tab')->nullable();
            $table->foreignId('recipe_id')->nullable()->constrained('resin_recipes')->nullOnDelete();
            $table->string('target_hex', 7)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['character_id', 'character_version_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resin_zones');
    }
};
