<?php

namespace App\Support;

/**
 * The tab a zone belongs to: its own, or deduced from its name. The
 * same rules as zoneTab() in resources/js/resina/guide.js (and the
 * prototype): keep the two in step.
 */
class ResinaZoneTab
{
    public static function for(string $name, ?string $tab = null): string
    {
        if ($tab) {
            return $tab;
        }

        $n = mb_strtolower($name);

        return match (true) {
            (bool) preg_match('/pelle/u', $n) => 'pelle',
            (bool) preg_match('/capelli/u', $n) => 'capelli',
            (bool) preg_match('/occhi|neo|cicatrice|puntini|punto sulla fronte|labbra|sopracc/u', $n) => 'volto',
            (bool) preg_match('/tuta|mantello|vestito|pantaloni|parti bianche|abito/u', $n) => 'vestiti',
            (bool) preg_match('/cloth|maschera|scudo|catene|gemme|armatura|ali|armi|elmo|stella|surplice|scaglia|fibbia|oro/u', $n) => 'armatura',
            default => 'dettagli',
        };
    }
}
