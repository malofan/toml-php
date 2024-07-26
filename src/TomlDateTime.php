<?php

namespace Devium\Toml;

use DateTime;
use DateTimeZone;

class TomlDateTime
{
    protected DateTime $dt;

    public function __construct(string $dateTimeString)
    {
        if (! preg_match('/(\d{4})(-(0[1-9]|1[0-2])(-([12]\d|0[1-9]|3[01]))([Tt\s]((([01]\d|2[0-3])((:)[0-5]\d))(:\d+)?)?(:[0-5]\d([.]\d+)?)?([zZ]|([+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)$/', $dateTimeString)) {
            throw new TomlError('datetime format must have leading zero');
        }
        $this->dt = new DateTime($dateTimeString);
    }

    public function __toString(): string
    {
        return $this->dt
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s.000p');
    }
}
