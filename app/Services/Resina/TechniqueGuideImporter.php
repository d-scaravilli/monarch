<?php

namespace App\Services\Resina;

use App\Models\Resina\GuideText;
use App\Models\Resina\StepTitle;
use App\Models\Resina\TechniqueGuide;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Loads database/data/resina/technique_guides.json (format
 * resina-tecniche/1). Idempotent: rows are matched on technique code,
 * title pattern and text key, and updated in place.
 */
class TechniqueGuideImporter
{
    /**
     * The drybrush named in the light-metallic alternative becomes a
     * placeholder: the page puts the user's own small drybrush there.
     */
    public const BRUSH_PLACEHOLDER = '{pennello}';

    public function __construct(private ?string $path = null)
    {
        $this->path ??= database_path('data/resina/technique_guides.json');
    }

    /**
     * @return array<string, int>
     */
    public function import(): array
    {
        $data = json_decode((string) @file_get_contents($this->path), true);

        if (($data['formato'] ?? null) !== 'resina-tecniche/1') {
            throw new RuntimeException("File delle tecniche mancante o non valido: {$this->path}");
        }

        DB::transaction(function () use ($data) {
            $position = 0;
            foreach ($data['tecniche'] as $code => $guide) {
                TechniqueGuide::updateOrCreate(['code' => $code], [
                    'name' => $guide['nome'],
                    'preparation' => $guide['preparazione'] ?? [],
                    'steps' => $guide['come'] ?? [],
                    'wait_minutes' => $guide['attesa_minuti'] ?? null,
                    'result' => $guide['risultato'] ?? null,
                    'mistakes' => $guide['errori'] ?? [],
                    'position' => ++$position,
                ]);
            }

            foreach ($data['titoli_semplici'] as $index => $title) {
                StepTitle::updateOrCreate(['pattern' => $title['ruolo']], ['title' => $title['titolo'], 'position' => $index + 1]);
            }

            $variants = $data['varianti'];
            $texts = [
                'metallico_preparazione' => $variants['metallico']['preparazione_extra'],
                'metallico_dopo' => [$variants['metallico']['dopo']],
                'shade_tmm' => [$variants['shade_tmm']['nota']],
                'light_metallico_drybrush' => [str_replace('il Drybrush 3', self::BRUSH_PLACEHOLDER, $variants['light_metallico_drybrush']['nota'])],
                'sessione_prima' => $data['sessione']['prima_di_iniziare'],
                'sessione_fine' => $data['sessione']['fine_sessione'],
            ];
            foreach ($texts as $key => $lines) {
                GuideText::updateOrCreate(['key' => $key], ['lines' => array_values($lines)]);
            }
        });

        return [
            'tecniche' => count($data['tecniche']),
            'titoli semplici' => count($data['titoli_semplici']),
            'testi' => 6,
        ];
    }
}
