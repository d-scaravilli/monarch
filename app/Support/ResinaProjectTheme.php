<?php

namespace App\Support;

/**
 * Header band colors for each "3D - Resina" project theme, the only
 * place where a project keeps its own look (the rest of the module uses
 * Monarch's style). Hex values go into inline styles, never into
 * Tailwind classes, so there's nothing for the JIT scanner to miss.
 */
class ResinaProjectTheme
{
    /**
     * @var array<string, array{band: string, accent: string, stars: bool}>
     */
    private const THEMES = [
        't-ss' => ['band' => '#1F2548', 'accent' => '#F6E4B0', 'stars' => true],
        't-pr' => ['band' => '#2A1414', 'accent' => '#FFD2CC', 'stars' => false],
        't-mv' => ['band' => '#1B1D2B', 'accent' => '#FFD0D5', 'stars' => false],
    ];

    private const FALLBACK = ['band' => '#1F2937', 'accent' => '#F3F4F6', 'stars' => false];

    /**
     * @return array{band: string, accent: string, stars: bool}
     */
    public static function for(?string $theme): array
    {
        return self::THEMES[$theme] ?? self::FALLBACK;
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::THEMES);
    }
}
