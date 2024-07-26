<?php

namespace Devium\Toml;

/**
 * @internal
 */
final class TomlLocalDateTime extends TomlDateTimeUtils
{
    public $year;

    public $month;

    public $day;

    public $hour;

    public $minute;

    public $second;

    public $millisecond;

    public function __construct($year, $month, $day, $hour, $minute, $second, $millisecond)
    {
        $this->year = $year;
        $this->month = $month;
        $this->day = $day;
        $this->hour = $hour;
        $this->minute = $minute;
        $this->second = $second;
        $this->millisecond = $millisecond;
    }

    public static function fromString($value): self
    {
        $components = preg_split('/[tT ]/', $value);
        if (count($components) !== 2) {
            throw new TomlError("invalid local date-time format \"$value\"");
        }
        $date = TomlLocalDate::fromString($components[0]);
        $time = TomlLocalTime::fromString($components[1]);

        return new self($date->year, $date->month, $date->day, $time->hour, $time->minute, $time->second, $time->millisecond);
    }
}
