<?php

namespace Devium\Toml;

abstract class TomlDateTime
{
    public static function isYear($value): bool
    {
        return $value >= 0 && $value <= 9999;
    }

    public static function isMonth($value): bool
    {
        return $value > 0 && $value <= 12;
    }

    public static function isDay($value): bool
    {
        return $value > 0 && $value <= 31;
    }

    public static function isHour($value): bool
    {
        return $value >= 0 && $value < 24;
    }

    public static function isMinute($value): bool
    {
        return $value >= 0 && $value < 60;
    }

    public static function isSecond($value): bool
    {
        return $value >= 0 && $value < 60;
    }
}
