<?php

namespace Devium\Toml\Nodes;

final readonly class BooleanNode implements Node
{
    public function __construct(public bool $value) {}
}
