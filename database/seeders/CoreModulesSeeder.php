<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class CoreModulesSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Module::firstOrCreate(
            ['slug' => 'amministrazione'],
            [
                'name' => 'Amministrazione',
                'description' => 'Utenti, ruoli e moduli dell\'applicazione.',
                'icon' => 'shield-check',
                'color' => 'blue',
                'is_active' => true,
            ],
        );
    }
}
