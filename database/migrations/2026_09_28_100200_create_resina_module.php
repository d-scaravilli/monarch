<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Registers the "3D - Resina" module. Palestra and Amministrazione
     * come from seeders, but the server only runs `migrate --force`, so
     * this one has to arrive through a migration. Only inserts when
     * missing: a module renamed or recolored from "Gestisci" is kept.
     */
    public function up(): void
    {
        if (DB::table('modules')->where('slug', 'resina')->exists()) {
            return;
        }

        DB::table('modules')->insert([
            'slug' => 'resina',
            'name' => '3D - Resina',
            'description' => 'Dipingere a pennello le stampe 3D in resina.',
            'icon' => 'paint-brush',
            'color' => 'purple',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Access grants go with it (module_user cascades on delete).
     */
    public function down(): void
    {
        DB::table('modules')->where('slug', 'resina')->delete();
    }
};
