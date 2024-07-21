<?php

require_once './errors.php';

function isYear($value)
{
    return $value >= 0 && $value <= 9999;
}

function isMonth($value)
{
    return $value > 0 && $value <= 12;
}

function isDay($value)
{
    return $value > 0 && $value <= 31;
}

class LocalDate
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

    public static function fromString($value)
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            throw new TOMLError("invalid local date format \"$value\"");
        }
        [$year, $month, $day] = array_map('intval', explode('-', $value));
        if (! isYear($year) || ! isMonth($month) || ! isDay($day)) {
            throw new TOMLError("invalid local date format \"$value\"");
        }

        return new LocalDate($year, $month, $day);
    }
}

function isHour($value)
{
    return $value >= 0 && $value < 24;
}

function isMinute($value)
{
    return $value >= 0 && $value < 60;
}

function isSecond($value)
{
    return $value >= 0 && $value < 60;
}

class LocalTime
{
    public $hour;

    public $minute;

    public $second;

    public $millisecond;

    public function __construct($hour, $minute, $second, $millisecond)
    {
        $this->hour = $hour;
        $this->minute = $minute;
        $this->second = $second;
        $this->millisecond = intval(substr((string) $millisecond, 0, 3));
    }

    public static function fromString($value)
    {
        if (! preg_match('/^\d{2}:\d{2}:\d{2}(\.\d+)?$/', $value)) {
            throw new TOMLError("invalid local time format \"$value\"");
        }
        $components = explode(':', $value);
        [$hour, $minute] = array_map('intval', array_slice($components, 0, 2));
        [$second, $millisecond] = array_map('intval', explode('.', $components[2]));
        if (! isHour($hour) || ! isMinute($minute) || ! isSecond($second)) {
            throw new TOMLError("invalid local time format \"$value\"");
        }

        return new LocalTime($hour, $minute, $second, is_nan($millisecond) ? 0 : $millisecond);
    }
}

class LocalDateTime
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

    public static function fromString($value)
    {
        $components = preg_split('/[tT ]/', $value);
        if (count($components) !== 2) {
            throw new TOMLError("invalid local date-time format \"$value\"");
        }
        $date = LocalDate::fromString($components[0]);
        $time = LocalTime::fromString($components[1]);

        return new LocalDateTime($date->year, $date->month, $date->day, $time->hour, $time->minute, $time->second, $time->millisecond);
    }
}
