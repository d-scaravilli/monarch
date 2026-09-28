<?php

namespace App\Models\Resina;

use Illuminate\Database\Eloquent\Model;

/**
 * A paint the user doesn't own yet but is worth buying ("Da comprare").
 */
class ShopSuggestion extends Model
{
    protected $table = 'resin_shop_suggestions';

    protected $fillable = [
        'code',
        'name',
        'hex',
        'why',
        'position',
    ];
}
