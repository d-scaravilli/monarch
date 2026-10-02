<?php

use App\Services\Resina\CatalogImporter;
use App\Services\Resina\TechniqueGuideImporter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('resina:importa', function (CatalogImporter $importer, TechniqueGuideImporter $guides) {
    $counts = [...$importer->import(), ...$guides->import()];

    $this->table(['Dato', 'Righe'], collect($counts)->map(fn (int $count, string $kind) => [$kind, $count])->values()->all());
    $this->info('Catalogo di "3D - Resina" importato. Rieseguire il comando non crea duplicati.');
})->purpose('Importa (o reimporta) il catalogo e le istruzioni delle tecniche del modulo 3D - Resina dai file in database/data/resina');
