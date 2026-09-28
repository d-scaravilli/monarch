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
        Schema::create('resin_user_paints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('paint_id')->nullable()->constrained('resin_paints')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->string('hex', 7)->nullable();
            $table->string('type')->default('normal');
            $table->text('usage')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'paint_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resin_user_paints');
    }
};
