<?php

namespace App\Support;

class CourseIcons
{
    /**
     * A curated set of icons offered when picking a course/event's icon.
     * Most are heroicon names, rendered via <x-course-icon> like everywhere
     * else in the app; 'sword' and 'dumbbell' have no heroicon equivalent,
     * so <x-course-icon> draws them from a hand-made SVG instead — see
     * that component for the two special cases.
     *
     * @return array<int, string>
     */
    public static function choices(): array
    {
        return [
            'sword', 'dumbbell', 'flag', 'fire', 'trophy', 'heart',
            'users', 'academic-cap', 'shield-check', 'musical-note',
            'face-smile', 'star', 'bolt', 'calendar-days', 'puzzle-piece', 'sparkles',
        ];
    }
}
