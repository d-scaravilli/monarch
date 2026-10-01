<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * "Tecniche": static pages with the prototype's text and diagrams.
 */
class TechniqueController extends Controller
{
    /**
     * Anchor of each technique on the page, as step rows, sheets and
     * the beginner path link to them.
     */
    public const ANCHORS = [
        't-prep' => 'Preparare la stampa in resina',
        't-primer' => 'Il primer a bomboletta',
        't-tavolozza' => 'La tavolozza bagnata',
        't-diluire' => 'Diluire e caricare il pennello',
        't-mischiare' => 'Come si mischiano i colori',
        't-bsl' => 'Base, ombra e luce',
        't-tmm' => 'Il sistema True Metallic Metal (TMM)',
        't-wash' => 'Il wash (lavatura)',
        't-drybrush' => 'Drybrush (pennello asciutto)',
        't-velatura' => 'Velatura (glaze)',
        't-spigoli' => 'Luce sugli spigoli (edge highlight)',
        't-metallici' => 'Lavorare con i metallici',
        't-occhi' => 'Gli occhi',
        't-vernice' => 'Vernice finale',
        't-errori' => 'Errori tipici di chi inizia',
    ];

    public function index(): View
    {
        return view('resina.techniques.index');
    }
}
