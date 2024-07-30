<?php

namespace Devium\Toml;

final class TomlLocalTime extends AbstractTomlDateTime
{
    public readonly int $millisecond;

    public function __construct(
        public readonly int $hour,
        public readonly int $minute,
        public readonly int $second,
        $millisecond
    ) {
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
        $p = array_map('intval', explode('.', $components[2]));
        $second = $p[0];
        $millisecond = $p[1] ?? NAN;

        if (! self::isHour($hour) || ! self::isMinute($minute) || ! self::isSecond($second)) {
            throw new TomlError("invalid local time format \"$value\"");
        }

        return new self($hour, $minute, $second, is_nan($millisecond) ? 0 : $millisecond);
    }

    public function __toString(): string
    {
        return "$this->hour-$this->minute-$this->second".($this->millisecond ? '.'.$this->millisecond : '');
    }
}
