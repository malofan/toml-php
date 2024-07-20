<?php

namespace Devium\Toml;

require_once './vendor/autoload.php';

use Carbon\Carbon;
use DateTimeZone;

/**
 * @internal
 */
final class TomlDate
{

    public const ISO_FORMAT = 'Y-m-d\TH:i:s.000\Z';

    public const TOML_DATE_RE = "/^(?'date'\d{4}-\d{2}-\d{2})?[T ]?(?:(?'hours'\d{2}):\d{2}:\d{2}(?:\.\d+)?)?(?'offset'Z|[-+]\d{2}:\d{2})?$/i";

    protected bool $hasDate = false;
    protected bool $hasTime = false;
    protected ?string $offset = null;

    protected Carbon $carbon;

    public function __construct(?string $date, $timezone = null)
    {
        $hasTime = true;
        $hasDate = true;
        $offset = 'Z';

        if (is_string($date)) {
            preg_match(self::TOML_DATE_RE, $date, $matches);

            if ($matches) {
                if (!($matches['date'] ?? false)) {
                    $hasDate = false;
                    $date = "0000-01-01T$date";
                }

                $hasTime = (bool)($matches['hours'] ?? false);

                // Do not allow rollover hours
                if (($matches['hours'] ?? false) && $matches['hours'] && $matches['hours'] > 23) {
                    $date = "";
                } else {
                    $offset = $matches['offset'] ?? null;
                    $date = strtoupper($date);
                    if (!$offset && $hasTime) $date .= "Z";
                }
            } else {
                $date = "";
            }
        }

        if ($date) {
            $this->carbon = Carbon::parse($date, $timezone);
        } else {
            $hasDate = false;
            $hasTime = false;
        }

        $this->hasTime = $hasTime;
        $this->hasDate = $hasDate;
        $this->offset = $offset;
    }

    public function isDateTime(): bool
    {
        return $this->hasDate && $this->hasTime;
    }

    public function isLocal(): bool
    {
        return !$this->hasDate || !$this->hasTime || !$this->offset;
    }

    public function isDate(): bool
    {
        return $this->hasDate && !$this->hasTime;
    }

    public function isTime(): bool
    {
        return $this->hasTime && !$this->hasDate;
    }

    public function isValid(): bool
    {
        return $this->hasDate || $this->hasTime;
    }

    public static function wrapAsOffsetDateTime($d, $offset = 'Z'): TomlDate
    {
        $date = new self($d);
        $date->offset = $offset;
        return $date;
    }

    public static function wrapAsLocalDateTime($d): TomlDate
    {
        $date = new self($d);
        $date->offset = null;
        return $date;
    }

    public static function wrapAsLocalDate($d): TomlDate
    {
        $date = new self($d);
        $date->hasTime = false;
        $date->offset = null;
        return $date;
    }

    public static function wrapAsLocalTime($d): TomlDate
    {
        $date = new self($d);
        $date->hasDate = false;
        $date->offset = null;
        return $date;
    }

    public function toISOString(): string
    {
        $iso = $this->carbon->format(self::ISO_FORMAT);

        if ($this->isDate()) {
            return substr($iso, 0, 10);
        }

        if ($this->isTime()) {
            return substr($iso, 11, 8);
        }

        if (!$this->offset) {
            return substr($iso, 0, -1);
        }

        if ($this->offset === 'Z') {
            return $iso;
        }

        $offset = ((int)substr($this->offset, 1, 3)) * 60 + ((int)substr($this->offset, 4, 6));
        $offset = $this->offset[0] === '-' ? $offset : -$offset;

        $offsetDate = new Carbon('now', new DateTimeZone('UTC'));
        $offsetDate = $offsetDate->setTimestamp((int)($this->carbon->getTimestamp() - $offset * 60));
		return substr($offsetDate->format('Y-m-d\TH:i:s.000\Z'), 0, -1) . $this->offset;
    }
}

//var_dump((new TomlDate('1979-05-27T07:32:00-08:00'))->toISOString() === '1979-05-27T07:32:00.000-08:00');
//var_dump((new TomlDate('1979-05-27T07:32:00'))->toISOString() === '1979-05-27T07:32:00.000');
//var_dump((new TomlDate('1979-05-27'))->toISOString() === '1979-05-27');
//var_dump((new TomlDate('07:32:00.000'))->toISOString() === '07:32:00');
//var_dump((new Carbon('1979-05-27T07:32:00-08:00'))->utc()->format(TomlDate::ISO_FORMAT) === '1979-05-27T15:32:00.000Z');
