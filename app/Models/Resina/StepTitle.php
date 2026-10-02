<?php

namespace App\Models\Resina;

use Illuminate\Database\Eloquent\Model;

/**
 * A plain title for steps whose role matches pattern (a regular
 * expression, tried in position order). The admin edits the title
 * only: the pattern comes from the data file.
 */
class StepTitle extends Model
{
    protected $table = 'resin_step_titles';

    protected $fillable = [
        'position',
        'pattern',
        'title',
    ];
}
