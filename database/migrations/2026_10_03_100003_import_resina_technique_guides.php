<?php

use App\Services\Resina\TechniqueGuideImporter;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Loads database/data/resina/technique_guides.json on deploy, like
     * the catalog. Idempotent: running it again (or "Reimporta catalogo")
     * updates the same rows.
     */
    public function up(): void
    {
        app(TechniqueGuideImporter::class)->import();
    }

    /**
     * The tables' own migration drops the rows.
     */
    public function down(): void
    {
        //
    }
};
