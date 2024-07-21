<?php

namespace Devium\Toml;

/**
 * @internal
 */
final class TomlLocalTime extends TomlDateTime
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

    /**
     * @throws TomlError
     */
    public static function fromString($value): self
    {
        if (! preg_match('/^\d{2}:\d{2}:\d{2}(\.\d+)?$/', $value)) {
            throw new TomlError("invalid local time format \"$value\"");
        }
        $components = explode(':', $value);
        [$hour, $minute] = array_map('intval', array_slice($components, 0, 2));
        [$second, $millisecond] = array_map('intval', explode('.', $components[2]));
        if (! self::isHour($hour) || ! self::isMinute($minute) || ! self::isSecond($second)) {
            throw new TomlError("invalid local time format \"$value\"");
        }

        return new self($hour, $minute, $second, is_nan($millisecond) ? 0 : $millisecond);
    }
}
