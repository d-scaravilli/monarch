<?php

namespace App\Support;

use App\Models\Resina\Character;
use App\Models\Resina\CharacterVersion;

/**
 * The Google Images search for a version's reference photo: the
 * character's search words, the version label (not for "Unica") and the
 * project's armor name when it has one ("cloth" for Saint Seiya,
 * nothing for Marvel). Mirrors versionReferenceLink() in guide.js.
 */
class ResinaReferenceSearch
{
    public static function query(Character $character, CharacterVersion $version): string
    {
        return trim(implode(' ', array_filter([
            $character->search_query ?: $character->name,
            $version->slug === CharacterVersion::SINGLE_SLUG ? null : str_replace('Anime ', 'anime ', $version->label),
            $character->project?->armor_label ? mb_strtolower($character->project->armor_label) : null,
        ])));
    }

    public static function url(Character $character, CharacterVersion $version): string
    {
        return 'https://www.google.com/search?tbm=isch&q='.rawurlencode(self::query($character, $version));
    }
}
