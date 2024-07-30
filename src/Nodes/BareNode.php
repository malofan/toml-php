<?php

namespace Devium\Toml\Nodes;

final readonly class BareNode implements Node
{
    public function __construct(public string $value) {}
}
