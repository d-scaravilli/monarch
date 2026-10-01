<?php

namespace App\Models\Resina;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A figure to paint. Catalog characters belong to a project and have no
 * user; "Le mie figure" are the same model with user_id set and no
 * project, so both share the tabbed guide view.
 */
class Character extends Model
{
    use HasFactory;

    public const SOURCES = ['catalogo', 'manuale', 'foto'];

    protected $table = 'resin_characters';

    protected $fillable = [
        'project_id',
        'user_id',
        'character_group_id',
        'slug',
        'name',
        'alias_it',
        'subtitle',
        'search_query',
        'versions_note',
        'no_face',
        'no_eyes',
        'bases',
        'tips',
        'source',
        'reference_image_path',
        'reference_thumb_path',
        'note',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'no_face' => 'boolean',
            'no_eyes' => 'boolean',
            'bases' => 'array',
            'tips' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CharacterGroup::class, 'character_group_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(CharacterVersion::class)->orderBy('position');
    }

    /**
     * Every zone, base and version-specific alike.
     */
    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class)->orderBy('position');
    }

    /**
     * Only the zones shared by every version; a chosen version's own
     * zones replace these by name (see charZones() in the JS).
     */
    public function baseZones(): HasMany
    {
        return $this->zones()->whereNull('character_version_id');
    }

    /**
     * @param  Builder<Character>  $query
     */
    public function scopeCatalog(Builder $query): void
    {
        $query->whereNull('user_id');
    }

    /**
     * @param  Builder<Character>  $query
     */
    public function scopeOwnedBy(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    public function isPersonal(): bool
    {
        return $this->user_id !== null;
    }
}
