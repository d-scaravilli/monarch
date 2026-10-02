<?php

namespace App\Models\Resina;

use Illuminate\Database\Eloquent\Model;

/**
 * A text of the painting mode that isn't tied to one technique (the
 * metallic and Shade TMM variants, the session checklists), as lines.
 */
class GuideText extends Model
{
    /**
     * Key → what the admin editor calls it.
     */
    public const LABELS = [
        'metallico_preparazione' => 'Metallici: preparazione (sostituisce quella della tecnica)',
        'metallico_dopo' => 'Metallici: a fine pezzo',
        'shade_tmm' => 'Shade TMM: nota',
        'light_metallico_drybrush' => 'Luce metallica: alternativa a drybrush ({pennello} = il drybrush piccolo del kit)',
        'sessione_prima' => 'Prima di iniziare',
        'sessione_fine' => 'Fine sessione',
    ];

    protected $table = 'resin_guide_texts';

    protected $fillable = [
        'key',
        'lines',
    ];

    protected function casts(): array
    {
        return [
            'lines' => 'array',
        ];
    }
}
