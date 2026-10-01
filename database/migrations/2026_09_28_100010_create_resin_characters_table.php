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
        Schema::create('resin_characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('resin_projects')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('character_group_id')->nullable()->constrained('resin_character_groups')->nullOnDelete();
            $table->string('slug')->nullable();
            $table->string('name');
            $table->string('alias_it')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('search_query')->nullable();
            $table->text('versions_note')->nullable();
            $table->boolean('no_face')->default(false);
            $table->boolean('no_eyes')->default(false);
            $table->json('bases')->nullable();
            $table->json('tips')->nullable();
            $table->string('source')->default('catalogo');
            $table->string('reference_image_path')->nullable();
            $table->string('reference_thumb_path')->nullable();
            $table->text('note')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resin_characters');
    }
};
