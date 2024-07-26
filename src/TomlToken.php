<?php

namespace Devium\Toml;

final class TomlToken
{
    public string $type;

    public mixed $key;

    public mixed $value;

    public array $keys = [];

    public array $elements = [];

    public bool $isMultiline = false;

    public function __construct(
        string $type,
        mixed $key = null,
        mixed $value = null,
        array $keys = [],
        array $elements = [],
        bool $isMultiline = false
    ) {
        $this->type = $type;
        $this->key = $key;
        $this->value = $value;
        $this->keys = $keys;
        $this->elements = $elements;
        $this->isMultiline = $isMultiline;
    }

    public static function fromArray(array $from): self
    {
        $type = $from['type'];
        $key = $from['key'] ?? null;
        $value = $from['value'] ?? null;
        $keys = $from['keys'] ?? [];
        $elements = $from['elements'] ?? [];
        $isMultiline = $from['isMultiline'] ?? false;

        return new self($type, $key, $value, $keys, $elements, $isMultiline);
    }
}
