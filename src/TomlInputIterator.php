<?php

namespace Devium\Toml;

/**
 * @internal
 */
final class TomlInputIterator
{
    public const EOF = '-1';

    public int $pos = -1;

    protected string $input;

    public function __construct(string $input)
    {
        $this->input = $input;
    }

    public function take(...$chars): bool
    {
        $char = $this->peek();
        if ($char !== self::EOF && in_array($char, $chars)) {
            $this->next();

            return true;
        }

        return false;
    }

    public function peek(): string
    {
        $pos = $this->pos;
        $char = $this->next();
        $this->pos = $pos;

        return $char;
    }

    public function next(): string
    {
        if ($this->pos + 1 === strlen($this->input)) {
            return self::EOF;
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
