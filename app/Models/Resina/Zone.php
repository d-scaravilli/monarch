<?php

namespace App\Models\Resina;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One area of a character to paint. It uses either a recipe or a free
 * target color (the recipe is then computed from the user's paints).
 * A null tab is deduced from the name, like zoneTab() in the prototype.
 */
class Zone extends Model
{
    use HasFactory;

    public const TABS = ['pelle', 'volto', 'vestiti', 'armatura', 'capelli', 'dettagli', 'basetta'];

    protected $table = 'resin_zones';

    protected $fillable = [
        'character_id',
        'character_version_id',
        'position',
        'name',
        'tab',
        'recipe_id',
        'target_hex',
        'note',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(CharacterVersion::class, 'character_version_id');
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
