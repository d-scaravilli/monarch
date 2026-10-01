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
        Schema::create('resin_project_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('resin_projects')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('title');
            $table->string('url', 1000);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resin_project_links');
    }
};
