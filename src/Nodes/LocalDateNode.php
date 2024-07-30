<?php

namespace Devium\Toml\Nodes;

use Devium\Toml\TomlLocalDate;

final class LocalDateNode implements Node
{
    public string $type;

    public function __construct(public readonly TomlLocalDate $value)
    {
        $this->type = 'LOCAL_DATE';
    }
}
