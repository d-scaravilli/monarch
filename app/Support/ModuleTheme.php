<?php

namespace App\Support;

class ModuleTheme
{
    /**
     * Every literal Tailwind class below must stay visible to the JIT
     * content scanner (see tailwind.config.js, this file is included in
     * `content`) — colors are looked up by key at runtime, never built
     * as a dynamic string, or Tailwind would purge them.
     *
     * @var array<string, array{badge: string, text: string, soft: string, ring: string}>
     */
    private const PALETTE = [
        'orange' => ['badge' => 'bg-orange-500', 'text' => 'text-orange-600', 'soft' => 'bg-orange-50', 'ring' => 'ring-orange-100'],
        'blue' => ['badge' => 'bg-blue-500', 'text' => 'text-blue-600', 'soft' => 'bg-blue-50', 'ring' => 'ring-blue-100'],
        'green' => ['badge' => 'bg-green-500', 'text' => 'text-green-600', 'soft' => 'bg-green-50', 'ring' => 'ring-green-100'],
        'purple' => ['badge' => 'bg-purple-500', 'text' => 'text-purple-600', 'soft' => 'bg-purple-50', 'ring' => 'ring-purple-100'],
        'pink' => ['badge' => 'bg-pink-500', 'text' => 'text-pink-600', 'soft' => 'bg-pink-50', 'ring' => 'ring-pink-100'],
        'teal' => ['badge' => 'bg-teal-500', 'text' => 'text-teal-600', 'soft' => 'bg-teal-50', 'ring' => 'ring-teal-100'],
        'red' => ['badge' => 'bg-red-500', 'text' => 'text-red-600', 'soft' => 'bg-red-50', 'ring' => 'ring-red-100'],
        'gray' => ['badge' => 'bg-gray-900', 'text' => 'text-gray-900', 'soft' => 'bg-gray-100', 'ring' => 'ring-gray-100'],
    ];

    /**
     * The same palette as hex values, for contexts CSS classes can't
     * reach (chart series colors, canvas fills).
     */
    private const HEX = [
        'orange' => '#f97316',
        'blue' => '#3b82f6',
        'green' => '#22c55e',
        'purple' => '#a855f7',
        'pink' => '#ec4899',
        'teal' => '#14b8a6',
        'red' => '#ef4444',
        'gray' => '#111827',
    ];

    /**
     * @return array{badge: string, text: string, soft: string, ring: string}
     */
    public static function classes(?string $color): array
    {
        return self::PALETTE[$color] ?? self::PALETTE['gray'];
    }

    public static function hex(?string $color): string
    {
        return self::HEX[$color] ?? self::HEX['gray'];
    }

    /**
     * @return array<int, string>
     */
    public static function colorKeys(): array
    {
        return array_keys(self::PALETTE);
    }

    /**
     * A curated set of heroicon names offered when picking a module's icon.
     *
     * @return array<int, string>
     */
    public static function iconChoices(): array
    {
        return [
            'fire', 'academic-cap', 'shield-check', 'calendar-days', 'users',
            'home', 'cube', 'trophy', 'sparkles', 'bolt', 'book-open',
            'wrench-screwdriver', 'banknotes', 'heart', 'beaker', 'puzzle-piece',
        ];
    }
}
