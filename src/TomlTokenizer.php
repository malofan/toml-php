<?php

namespace Devium\Toml;

use Throwable;

final class TomlTokenizer
{
    public const PUNCTUATOR_OR_NEWLINE_TOKENS = [
        "\n" => 'NEWLINE',
        '=' => 'EQUALS',
        '.' => 'PERIOD',
        ',' => 'COMMA',
        ':' => 'COLON',
        '+' => 'PLUS',
        '{' => 'LEFT_CURLY_BRACKET',
        '}' => 'RIGHT_CURLY_BRACKET',
        '[' => 'LEFT_SQUARE_BRACKET',
        ']' => 'RIGHT_SQUARE_BRACKET',
    ];

    public const ESCAPES = [
        'b' => "\b",
        't' => "\t",
        'n' => "\n",
        'f' => "\f",
        'r' => "\r",
        '"' => '"',
        '\\' => '\\',
    ];

    protected string $input;

    protected TomlInputIterator $iterator;

    public function __construct(string $input)
    {
        $this->input = $input;
        $this->iterator = new TomlInputIterator($input);
    }

    /**
     * @throws Throwable
     */
    public function assert(...$types): void
    {
        if (! $this->take(...$types)) {
            throw new TomlError();
        }
    }

    /**
     * @throws Throwable
     */
    public function take(...$types): bool
    {
        $token = $this->peek();
        if (in_array($token['type'], $types, true)) {
            $this->next();

            return true;
        }

        return false;
    }

    /**
     * @throws Throwable
     */
    public function peek(): array
    {
        $pos = $this->iterator->pos;
        try {
            $token = $this->next();
            $this->iterator->pos = $pos;

            return $token;
        } catch (Throwable $e) {
            $this->iterator->pos = $pos;
            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function next(): array
    {
        $char = $this->iterator->next();
        $start = $this->iterator->pos;
        if ($this->isPunctuatorOrNewline($char)) {
            return [
                'type' => self::PUNCTUATOR_OR_NEWLINE_TOKENS[$char],
                'value' => $char,
            ];
        }
        if ($this->isBare($char)) {
            return $this->scanBare($start);
        }
        switch ($char) {
            case ' ':
            case "\t":
                return $this->scanWhitespace($start);
            case '#':
                return $this->scanComment($start);
            case "'":
                return $this->scanLiteralString();
            case '"':
                return $this->scanBasicString();
            case '-1':
                return ['type' => 'EOF'];
        }
        throw new TomlError();
    }

    public function isPunctuatorOrNewline($char): bool
    {
        return array_key_exists($char, self::PUNCTUATOR_OR_NEWLINE_TOKENS);
    }

    public function isBare($char): bool
    {
        return ($char >= 'A' && $char <= 'Z') ||
            ($char >= 'a' && $char <= 'z') ||
            ($char >= '0' && $char <= '9') ||
            $char === '-' ||
            $char === '_';
    }

    public function scanBare($start): array
    {
        while ($this->isBare($this->iterator->peek())) {
            $this->iterator->next();
        }

        return $this->returnScan('BARE', $start);
    }

    public function returnScan(string $type, $start): array
    {
        return ['type' => $type, 'value' => $this->stringSlice($this->input, $start, $this->iterator->pos + 1)];
    }

    public function stringSlice($str, $start, $end): string
    {
        $end = $end - $start;

        return substr($str, $start, $end);
    }

    public function scanWhitespace($start): array
    {
        while ($this->isWhitespace($this->iterator->peek())) {
            $this->iterator->next();
        }

        return $this->returnScan('WHITESPACE', $start);
    }

    public function isWhitespace($char): bool
    {
        return $char === ' ' || $char === "\t";
    }

    public function scanComment($start): array
    {
        for (; ;) {
            $char = $this->iterator->peek();
            if (! $this->isControlCharacterOtherThanTab($char)) {
                $this->iterator->next();

                continue;
            }

            return $this->returnScan('COMMENT', $start);
        }
    }

    public function isControlCharacterOtherThanTab($char): bool
    {
        return $this->isControlCharacter($char) && $char !== "\t";
    }

    public function isControlCharacter($char): bool
    {
        return ($char >= "\u{0}" && $char < "\u{20}") || $char === "\u{7f}";
    }

    /**
     * @throws Throwable
     */
    public function scanLiteralString(): array
    {
        return $this->scanString("'");
    }

    /**
     * @throws TomlError
     */
    public function scanString($delimiter): array
    {
        $isMultiline = false;
        if ($this->iterator->take($delimiter)) {
            if (! $this->iterator->take($delimiter)) {
                return ['type' => 'STRING', 'value' => '', 'isMultiline' => false];
            }
            $isMultiline = true;
        }
        if ($isMultiline) {
            $this->iterator->take("\n");
        }
        $value = '';
        for (; ;) {
            $char = $this->iterator->next();
            switch ($char) {
                case "\n":
                    if (! $isMultiline) {
                        throw new TomlError();
                    }
                    $value .= $char;

                    continue 2;
                case $delimiter:
                    if ($isMultiline) {
                        if (! $this->iterator->take($delimiter)) {
                            $value .= $delimiter;

                            continue 2;
                        }
                        if (! $this->iterator->take($delimiter)) {
                            $value .= $delimiter;
                            $value .= $delimiter;

                            continue 2;
                        }
                        if ($this->iterator->take($delimiter)) {
                            $value .= $delimiter;
                        }
                        if ($this->iterator->take($delimiter)) {
                            $value .= $delimiter;
                        }
                    }
                    break;
                    /* @todo case undefined:
                     * throw new TomlError();*/
                default:
                    if ($this->isControlCharacterOtherThanTab($char)) {
                        throw new TomlError();
                    }
                    switch ($delimiter) {
                        case "'":
                            $value .= $char;

                            continue 3;
                        case '"':
                            if ($char === '\\') {
                                $char = $this->iterator->next();
                                if ($this->isEscaped($char)) {
                                    $value .= self::ESCAPES[$char];

                                    continue 3;
                                }
                                if ($char === 'u' || $char === 'U') {
                                    $size = $char === 'u' ? 4 : 8;
                                    $codePoint = '';
                                    for ($i = 0; $i < $size; $i++) {
                                        $char = $this->iterator->next();
                                        if ($char === '-1' || ! TomlUtils::isHexadecimal($char)) {
                                            throw new TomlError();
                                        }
                                        $codePoint .= $char;
                                    }
                                    $result = mb_chr(intval($codePoint, 16));
                                    if (! $this->isUnicodeCharacter($result)) {
                                        throw new TomlError();
                                    }
                                    $value .= $result;

                                    continue 3;
                                }
                                if ($isMultiline && ($this->isWhitespace($char) || $char === "\n")) {
                                    /** @noinspection PhpStatementHasEmptyBodyInspection */
                                    while ($this->iterator->take(' ', "\t", "\n")) {
                                    }

                                    continue 3;
                                }
                                throw new TomlError();
                            }
                            $value .= $char;

                            continue 3;
                    }
            }
            break;
        }

        return [
            'type' => 'STRING',
            'value' => $value,
            'isMultiline' => $isMultiline,
        ];
    }

    public function isEscaped($char): bool
    {
        return array_key_exists($char, self::ESCAPES);
    }

    public function isUnicodeCharacter($char): bool
    {
        return $char <= "\u{10ffff}";
    }

    /**
     * @throws Throwable
     */
    public function scanBasicString(): array
    {
        return $this->scanString('"');
    }

    /**
     * @throws Throwable
     */
    public function sequence(...$types): array
    {
        return array_map(fn ($type) => $this->expect($type), $types);
    }

    /**
     * @throws Throwable
     */
    public function expect($type): array
    {
        $token = $this->next();
        if ($token['type'] !== $type) {
            throw new TomlError();
        }

        return $token;
    }
}
