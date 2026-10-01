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
        Schema::create('resin_step_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('resin_characters')->cascadeOnDelete();
            $table->string('zone_key', 100);
            $table->unsignedSmallInteger('step_position');
            $table->timestamp('done_at');

            $table->unique(['user_id', 'character_id', 'zone_key', 'step_position'], 'resin_step_progress_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resin_step_progress');
    }
};
