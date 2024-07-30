<?php

namespace Devium\Toml\Nodes;

final readonly class StringNode implements Node
{
    public string $type;

    public function __construct(public string $value) {}

    public function type(): string
    {
        return $this->type;
    }
}
