<?php

namespace Devium\Toml\Nodes;

use Devium\Toml\TomlLocalDateTime;

final readonly class LocalDateTimeNode implements Node
{
    public function __construct(public TomlLocalDateTime $value) {}
}
