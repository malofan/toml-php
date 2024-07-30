<?php

namespace Devium\Toml;

final class TomlLocalDateTime extends AbstractTomlDateTime
{
    public function __construct(
        public readonly int $year,
        public readonly int $month,
        public readonly int $day,
        public readonly int $hour,
        public readonly int $minute,
        public readonly int $second,
        public readonly int $millisecond,
    ) {}

    /**
     * @throws TomlError
     */
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

    public function __toString(): string
    {
        return "$this->year-$this->month-$this->day\T$this->hour-$this->minute-$this->second".($this->millisecond ? '.'.$this->millisecond : '');
    }
}
