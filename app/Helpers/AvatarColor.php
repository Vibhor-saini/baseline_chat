<?php

namespace App\Helpers;

class AvatarColor
{
    // Vibrant gradient pairs [from, to]
    private static array $gradients = [
        ['#f97316', '#ef4444'], // orange → red
        ['#8b5cf6', '#6366f1'], // violet → indigo
        ['#06b6d4', '#3b82f6'], // cyan → blue
        ['#10b981', '#059669'], // emerald → green
        ['#f59e0b', '#f97316'], // amber → orange
        ['#ec4899', '#a855f7'], // pink → purple
        ['#14b8a6', '#06b6d4'], // teal → cyan
        ['#6366f1', '#8b5cf6'], // indigo → violet
        ['#ef4444', '#f43f5e'], // red → rose
        ['#84cc16', '#22c55e'], // lime → green
        ['#f43f5e', '#ec4899'], // rose → pink
        ['#0ea5e9', '#6366f1'], // sky → indigo
    ];

    public static function gradient(int $userId): string
    {
        $index = $userId % count(self::$gradients);
        [$from, $to] = self::$gradients[$index];
        return "linear-gradient(135deg, {$from}, {$to})";
    }
}
