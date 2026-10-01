<?php

namespace App\Models\Resina;

use Illuminate\Database\Eloquent\Model;

/**
 * A ready-made YouTube search.
 */
class Tutorial extends Model
{
    protected $table = 'resin_tutorials';

    protected $fillable = [
        'position',
        'title',
        'query',
        'description',
    ];
}
