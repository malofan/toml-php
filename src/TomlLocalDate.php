<?php

namespace Devium\Toml;

final class TomlLocalDate extends TomlDateTimeUtils
{
    public $year;

    public $month;

    public $day;

    public function __construct($year, $month, $day)
    {
        $this->year = $year;
        $this->month = $month;
        $this->day = $day;
    }

    /**
     * @throws TomlError
     */
    public static function fromString($value): self
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            throw new TomlError("invalid local date format \"$value\"");
        }
        [$year, $month, $day] = array_map('intval', explode('-', $value));
        if (! self::isYear($year) || ! self::isMonth($month) || ! self::isDay($day)) {
            throw new TomlError("invalid local date format \"$value\"");
        }

        return new self($year, $month, $day);
    }

    public function __toString(): string
    {
        return "$this->year-$this->month-$this->day";
    }
}
