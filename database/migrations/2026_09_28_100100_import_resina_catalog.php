<?php

use App\Services\Resina\CatalogImporter;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Loads the "3D - Resina" catalog on deploy (no SSH on the server, so
     * no manual db:seed). The importer is idempotent: running it again,
     * here or through `php artisan resina:importa`, never duplicates rows.
     *
     * It runs with today's schema: if a later migration adds a column the
     * importer starts writing, this migration breaks on a fresh database
     * (tests included), so move that write to a new data migration.
     */
    public function up(): void
    {
        app(CatalogImporter::class)->import();
    }

    /**
     * Nothing to undo: rolling back the resin_* table migrations drops
     * the imported rows with them.
     */
    public function down(): void
    {
        //
    }
};
