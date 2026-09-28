<?php

use App\Services\Resina\CatalogImporter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('resina:importa', function (CatalogImporter $importer) {
    $counts = $importer->import();

    $this->table(['Dato', 'Righe'], collect($counts)->map(fn (int $count, string $kind) => [$kind, $count])->values()->all());
    $this->info('Catalogo di "3D - Resina" importato. Rieseguire il comando non crea duplicati.');
})->purpose('Importa (o reimporta) il catalogo del modulo 3D - Resina dai file in database/data/resina');
