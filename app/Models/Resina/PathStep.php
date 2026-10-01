<?php

namespace App\Models\Resina;

use Illuminate\Database\Eloquent\Model;

/**
 * One step of the beginner path. The link points to a module page by
 * route name, plus an optional anchor (e.g. a technique on its page).
 */
class PathStep extends Model
{
    protected $table = 'resin_path_steps';

    protected $fillable = [
        'position',
        'title',
        'description',
        'link_route',
        'link_anchor',
    ];
}
