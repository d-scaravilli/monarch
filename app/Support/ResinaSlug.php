<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

/**
 * Slugs for "3D - Resina" rows, made once from the name and never
 * changed: URLs, imports and recipe references rely on them.
 */
class ResinaSlug
{
    /**
     * A slug from $name that $query (the rows it must be unique among)
     * doesn't have yet: "seiya", "seiya-2", …
     *
     * @param  Builder<Model>|Relation<Model, Model, mixed>  $query
     */
    public static function unique(Builder|Relation $query, string $name, string $fallback): string
    {
        $base = Str::slug($name) ?: $fallback;
        $slug = $base;

        for ($i = 2; (clone $query)->where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }
}
