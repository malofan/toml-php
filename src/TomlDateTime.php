<?php

namespace Devium\Toml;

use DateTime;
use DateTimeZone;

class TomlDateTime
{
    protected DateTime $dt;

    public function __construct(string $dateTimeString)
    {
        $this->dt = new DateTime($dateTimeString);
    }

    public function __toString(): string
    {
        return $this->dt
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s.000p');
    }
}
