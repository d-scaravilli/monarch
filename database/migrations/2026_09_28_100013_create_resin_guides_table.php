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
        Schema::create('resin_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('resin_projects')->cascadeOnDelete();
            $table->string('slug');
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('title');
            $table->text('intro')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resin_guides');
    }
};
