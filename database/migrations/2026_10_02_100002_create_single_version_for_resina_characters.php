<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Every catalog character gets at least one version, so every one
     * can have a reference photo: those without any get "Unica". Their
     * zones stay where they are (zones without a version are shared by
     * all versions), so saved progress keeps pointing to the same rows.
     */
    public function up(): void
    {
        $characterIds = DB::table('resin_characters')
            ->whereNull('user_id')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('resin_character_versions')->whereColumn('resin_character_versions.character_id', 'resin_characters.id'))
            ->pluck('id');

        foreach ($characterIds as $characterId) {
            DB::table('resin_character_versions')->insert([
                'character_id' => $characterId,
                'slug' => 'unica',
                'position' => 1,
                'label' => 'Unica',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Only the "Unica" versions without a photo go: once one has a
     * photo, it holds data worth keeping.
     */
    public function down(): void
    {
        DB::table('resin_character_versions')->where('slug', 'unica')->whereNull('reference_image_path')->delete();
    }
};
