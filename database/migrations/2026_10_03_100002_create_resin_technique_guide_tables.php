<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Instructions shown in the painting mode, one card per step:
     * per-technique texts, the plain step titles (matched on the role)
     * and the extra texts (metallic and Shade TMM variants, session).
     */
    public function up(): void
    {
        Schema::create('resin_technique_guides', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->json('preparation');
            $table->json('steps');
            $table->unsignedSmallInteger('wait_minutes')->nullable();
            $table->text('result')->nullable();
            $table->json('mistakes');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('resin_step_titles', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('position');
            $table->string('pattern')->unique();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('resin_guide_texts', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('lines');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resin_guide_texts');
        Schema::dropIfExists('resin_step_titles');
        Schema::dropIfExists('resin_technique_guides');
    }
};
