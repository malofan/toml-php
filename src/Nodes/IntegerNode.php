<?php

namespace Devium\Toml\Nodes;

final readonly class IntegerNode implements Node
{
    public function __construct(public int $value) {}
}
