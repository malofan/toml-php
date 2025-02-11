<?php

namespace Devium\Toml\Nodes;

use Devium\Toml\TomlDateTime;

final readonly class OffsetDateTimeNode implements Node
{
    public function __construct(public TomlDateTime $value) {}
}
