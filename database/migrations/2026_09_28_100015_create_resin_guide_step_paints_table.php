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
        Schema::create('resin_guide_step_paints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_step_id')->constrained('resin_guide_steps')->cascadeOnDelete();
            $table->foreignId('paint_id')->constrained('resin_paints')->restrictOnDelete();
            $table->unsignedTinyInteger('drops');

            $table->unique(['guide_step_id', 'paint_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resin_guide_step_paints');
    }
};
