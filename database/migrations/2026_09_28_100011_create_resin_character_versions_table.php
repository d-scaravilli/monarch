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
        Schema::create('resin_character_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained('resin_characters')->cascadeOnDelete();
            $table->string('slug');
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('label');
            $table->string('subtitle')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['character_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resin_character_versions');
    }
};
