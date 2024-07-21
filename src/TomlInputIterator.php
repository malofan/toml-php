<?php

namespace Devium\Toml;

/**
 * @internal
 */
final class TomlInputIterator
{
    protected string $input;

    protected int $pos = -1;

    public function __construct(string $input)
    {
        $this->input = $input;
    }

    public function peek(): int|string
    {
        $pos = $this->pos;
        $char = $this->next();
        $this->pos = $pos;

        return $char;
    }

    public function take(...$chars): bool
    {
        $char = $this->peek();
        if ($char !== -1 && in_array($char, $chars)) {
            $this->next();

            return true;
        }

        return false;
    }

    public function next(): int|string
    {
        if ($this->pos + 1 === strlen($this->input)) {
            return -1;
        }
        $this->pos++;
        $char = $this->input[$this->pos];
        if ($char === "\r" && $this->input[$this->pos + 1] === "\n") {
            $this->pos++;

            return "\n";
        }

        return $char;
    }
}
