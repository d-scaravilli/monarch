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
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->boolean('owns_sword')->default(false);
            $table->boolean('shirt_given')->default(false);
            $table->boolean('has_borrowed_equipment')->default(false);
            $table->text('borrowed_equipment_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropColumn(['owns_sword', 'shirt_given', 'has_borrowed_equipment', 'borrowed_equipment_notes']);
        });
    }
};
