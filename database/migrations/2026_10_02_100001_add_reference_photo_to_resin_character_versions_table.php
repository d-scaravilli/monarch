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
        Schema::table('resin_character_versions', function (Blueprint $table) {
            $table->string('reference_image_path')->nullable()->after('note');
            $table->string('reference_thumb_path')->nullable()->after('reference_image_path');
            $table->string('reference_source')->nullable()->after('reference_thumb_path');
            $table->timestamp('reference_updated_at')->nullable()->after('reference_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resin_character_versions', function (Blueprint $table) {
            $table->dropColumn(['reference_image_path', 'reference_thumb_path', 'reference_source', 'reference_updated_at']);
        });
    }
};
