<?php

namespace App\Support;

/**
 * Ordered rows coming out of $request->validate() (zones, steps,
 * groups…). validated() rebuilds nested arrays rule by rule, so when
 * the first wildcard key ("*.id") exists only on some rows, those rows
 * come first. The original indexes survive, though: sort by them to get
 * the order on screen back.
 */
class ResinaRows
{
    /**
     * @template T
     *
     * @param  array<int, T>|null  $rows
     * @return array<int, T>
     */
    public static function ordered(?array $rows): array
    {
        $rows ??= [];
        ksort($rows);

        return array_values($rows);
    }
}
