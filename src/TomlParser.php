<?php

namespace Devium\Toml;

use Throwable;

final class TomlParser
{
    protected TomlTokenizer $tokenizer;

    protected TomlKeystore $keystore;

    protected TomlToken $rootTableNode;

    protected TomlToken $tableNode;

    public function __construct(string $input)
    {
        $this->tokenizer = new TomlTokenizer($input);
        $this->keystore = new TomlKeystore();
        $this->rootTableNode = TomlToken::fromArray([
            'type' => 'ROOT_TABLE',
            'elements' => [],
        ]
        );
        $this->tableNode = $this->rootTableNode;
    }

    /**
     * @throws TomlError
     */
    public function parse(): TomlToken
    {
        for (; ;) {
            $node = $this->expression();
            if (! $node) {
                break;
            }

            $this->tokenizer->take('WHITESPACE');
            $this->tokenizer->take('COMMENT');
            $this->tokenizer->assert('NEWLINE', 'EOF');
            $this->keystore->addNode($node);
            if ($node->type === 'ARRAY_TABLE' || $node->type === 'TABLE') {
                $this->tableNode = $node;
                $this->rootTableNode->elements[] = $node;
            } else {
                $this->tableNode->elements[] = $node;
            }
        }

        return $this->rootTableNode;
    }

    /**
     * @throws TomlError
     */
    public function expression(): ?TomlToken
    {
        $this->takeCommentsAndNewlines();
        $token = $this->tokenizer->peek();

        return match ($token->type) {
            'LEFT_SQUARE_BRACKET' => $this->table(),
            'EOF' => null,
            default => $this->keyValuePair(),
        };
    }

    /**
     * @throws TomlError
     */
    public function takeCommentsAndNewlines(): void
    {
        for (; ;) {
            $this->tokenizer->take('WHITESPACE');
            if ($this->tokenizer->take('COMMENT')) {
                if ($this->tokenizer->isEOF()) {
                    break;
                }
                $this->tokenizer->assert('NEWLINE');

                continue;
            }
            if (! $this->tokenizer->take('NEWLINE')) {
                break;
            }
        }
    }

    /**
     * @throws TomlError
     */
    public function table(): TomlToken
    {
        $this->tokenizer->next();
        $isArrayTable = $this->tokenizer->take('LEFT_SQUARE_BRACKET');
        $key = $this->key();
        $this->tokenizer->assert('RIGHT_SQUARE_BRACKET');
        if ($isArrayTable) {
            $this->tokenizer->assert('RIGHT_SQUARE_BRACKET');
        }

        return TomlToken::fromArray([
            'type' => $isArrayTable ? 'ARRAY_TABLE' : 'TABLE',
            'key' => $key,
            'elements' => [],
        ]);
    }

    /**
     * @throws TomlError
     */
    public function key(): TomlToken
    {
        $keyNode = TomlToken::fromArray([
            'type' => 'KEY',
            'keys' => [],
        ]);
        do {
            $this->tokenizer->take('WHITESPACE');
            $token = $this->tokenizer->next();
            switch ($token->type) {
                case 'BARE':
                    $keyNode->keys[] = TomlToken::fromArray([
                        'type' => 'BARE',
                        'value' => $token->value,
                    ]);
                    break;
                case 'STRING':
                    if ($token->isMultiline) {
                        throw new TomlError();
                    }
                    $keyNode->keys[] = TomlToken::fromArray([
                        'type' => 'STRING',
                        'value' => $token->value,
                    ]);
                    break;
                default:
                    throw new TomlError();
            }
            $this->tokenizer->take('WHITESPACE');
        } while ($this->tokenizer->take('PERIOD'));

        return $keyNode;
    }

    /**
     * @throws TomlError
     */
    public function keyValuePair(): TomlToken
    {
        $key = $this->key();
        $this->tokenizer->assert('EQUALS');
        $this->tokenizer->take('WHITESPACE');
        $value = $this->value();

        return TomlToken::fromArray([
            'type' => 'KEY_VALUE_PAIR',
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * @throws TomlError
     */
    public function value(): TomlToken
    {
        $token = $this->tokenizer->next();

        return match ($token->type) {
            'STRING' => TomlToken::fromArray([
                'type' => 'STRING',
                'value' => $token->value,
            ]),
            'BARE' => $this->booleanOrNumberOrDateOrDateTimeOrTime($token->value),
            'PLUS' => $this->plus(),
            'LEFT_SQUARE_BRACKET' => $this->array(),
            'LEFT_CURLY_BRACKET' => $this->inlineTable(),
            default => throw new TomlError(),
        };
    }

    /**
     * @throws TomlError
     */
    public function booleanOrNumberOrDateOrDateTimeOrTime($value): TomlToken
    {
        if ($value === 'true' || $value === 'false') {
            return
                TomlToken::fromArray([
                    'type' => 'BOOLEAN', 'value' => $value === 'true',
                ]);
        }
        if (str_contains(substr($value, 1), '-') && ! str_contains($value, 'e-') && ! str_contains($value, 'E-')) {
            return $this->dateOrDateTime($value);
        }
        if ($this->tokenizer->peek()->type === 'COLON') {
            return $this->time($value);
        }

        return $this->number($value);
    }

    /**
     * @throws TomlError
     */
    public function dateOrDateTime($value): TomlToken
    {
        $token = $this->tokenizer->peek();
        if ($token->type === 'WHITESPACE' && $token->value === ' ') {
            $this->tokenizer->next();
            $token = $this->tokenizer->peek();
            if ($token->type !== 'BARE') {
                return
                    TomlToken::fromArray([
                        'type' => 'LOCAL_DATE', 'value' => TomlLocalDate::fromString($value),
                    ]);
            }
            $this->tokenizer->next();
            $value .= 'T';
            $value .= $token->value;
        }
        if (! str_contains($value, 'T') && ! str_contains($value, 't')) {
            return TomlToken::fromArray([
                'type' => 'LOCAL_DATE', 'value' => TomlLocalDate::fromString($value),
            ]);
        }

        $tokens = $this->tokenizer->sequence('COLON', 'BARE', 'COLON', 'BARE');
        $value .= implode('', array_map(function (TomlToken $token) {
            return $token->value;
        }, $tokens));
        if (
            str_ends_with($tokens[count($tokens) - 1]->value, 'Z') ||
            str_ends_with($tokens[count($tokens) - 1]->value, 'z')
        ) {
            return
                TomlToken::fromArray([
                    'type' => 'OFFSET_DATE_TIME', 'value' => $this->parseDate($value),
                ]);
        }
        if (str_contains($tokens[count($tokens) - 1]->value, '-')) {
            $this->tokenizer->assert('COLON');
            $token = $this->tokenizer->expect('BARE');
            $value .= ':';
            $value .= $token->value;

            return TomlToken::fromArray([
                'type' => 'OFFSET_DATE_TIME', 'value' => $this->parseDate($value),
            ]);
        }
        switch ($this->tokenizer->peek()->type) {
            case 'PLUS':

                $this->tokenizer->next();
                $tokens = $this->tokenizer->sequence('BARE', 'COLON', 'BARE');
                $value .= '+';
                $value .= implode('', array_map(function (TomlToken $token) {
                    return $token->value;
                }, $tokens));

                return TomlToken::fromArray([
                    'type' => 'OFFSET_DATE_TIME', 'value' => $this->parseDate($value),
                ]);

            case 'PERIOD':

                $this->tokenizer->next();
                $token = $this->tokenizer->expect('BARE');
                $value .= '.';
                $value .= $token->value;
                if (str_ends_with($token->value, 'Z')) {
                    return TomlToken::fromArray([
                        'type' => 'OFFSET_DATE_TIME', 'value' => $this->parseDate($value),
                    ]);
                }
                if (str_contains($token->value, '-')) {
                    $this->tokenizer->assert('COLON');
                    $token = $this->tokenizer->expect('BARE');
                    $value .= ':';
                    $value .= $token->value;

                    return TomlToken::fromArray([
                        'type' => 'OFFSET_DATE_TIME', 'value' => $this->parseDate($value),
                    ]);
                }
                if ($this->tokenizer->take('PLUS')) {
                    $tokens = $this->tokenizer->sequence('BARE', 'COLON', 'BARE');
                    $value .= '+';
                    $value .= implode('', array_map(function (TomlToken $token) {
                        return $token->value;
                    }, $tokens));

                    return TomlToken::fromArray([
                        'type' => 'OFFSET_DATE_TIME', 'value' => $this->parseDate($value),
                    ]);
                }
                break;

        }

        return TomlToken::fromArray([
            'type' => 'LOCAL_DATE_TIME', 'value' => TomlLocalDateTime::fromString($value),
        ]);
    }

    /**
     * @throws TomlError
     */
    public function parseDate($value): TomlDateTime
    {
        try {
            return new TomlDateTime($value);
        } catch (Throwable) {
            throw new TomlError();
        }
    }

    /**
     * @throws TomlError
     */
    public function time($value): TomlToken
    {
        $tokens = $this->tokenizer->sequence('COLON', 'BARE', 'COLON', 'BARE');
        $value .= implode('', array_map(function (TomlToken $token) {
            return $token->value;
        }, $tokens));
        if ($this->tokenizer->take('PERIOD')) {
            $token = $this->tokenizer->expect('BARE');
            $value .= '.';
            $value .= $token->value;
        }

        return TomlToken::fromArray([
            'type' => 'LOCAL_TIME', 'value' => TomlLocalTime::fromString($value),
        ]);
    }

    /**
     * @throws TomlError
     */
    public function number($value): TomlToken
    {
        switch ($value) {
            case 'inf':
            case '+inf':
                return TomlToken::fromArray([
                    'type' => 'FLOAT', 'value' => INF,
                ]);
            case '-inf':
                return TomlToken::fromArray([
                    'type' => 'FLOAT', 'value' => -INF,
                ]);
            case 'nan':
            case '+nan':
            case '-nan':
                return TomlToken::fromArray([
                    'type' => 'FLOAT', 'value' => NAN,
                ]);
        }
        if (str_starts_with($value, '0x')) {
            return $this->integer(substr($value, 2), 16);
        }
        if (str_starts_with($value, '0o')) {
            return $this->integer(substr($value, 2), 8);
        }
        if (str_starts_with($value, '0b')) {
            return $this->integer(substr($value, 2), 2);
        }
        if (str_contains($value, 'e') || str_contains($value, 'E') || $this->tokenizer->peek()->type === 'PERIOD') {
            return $this->float($value);
        }

        return $this->integer($value, 10);
    }

    /**
     * @throws TomlError
     */
    public function integer($value, $radix): TomlToken
    {
        $isSignAllowed = $radix === 10;
        $areLeadingZerosAllowed = $radix !== 10;
        $int = $this->parseInteger($value, $isSignAllowed, $areLeadingZerosAllowed, false, $radix)['int'];

        return TomlToken::fromArray([
            'type' => 'INTEGER', 'value' => +$int,
        ]);
    }

    /**
     * @throws TomlError
     */
    public function parseInteger($value, $isSignAllowed, $areLeadingZerosAllowed, $isUnparsedAllowed, $radix): array
    {
        $i = 0;
        if ($value[$i] === '+' || $value[$i] === '-') {
            if (! $isSignAllowed) {
                throw new TomlError();
            }
            $i++;
        }
        if (! $areLeadingZerosAllowed && $value[$i] === '0' && ($i + 1) !== strlen($value)) {
            throw new TomlError();
        }
        $isUnderscoreAllowed = false;
        for (; $i < strlen($value); $i++) {
            $char = $value[$i];
            if ($char === '_') {
                if (! $isUnderscoreAllowed) {
                    throw new TomlError();
                }
                $isUnderscoreAllowed = false;

                continue;
            }
            if (! $this->digitalChecks($radix, $char)) {
                break;
            }
            $isUnderscoreAllowed = true;
        }
        if (! $isUnderscoreAllowed) {
            throw new TomlError();
        }
        $int = str_replace('_', '', TomlUtils::stringSlice($value, 0, $i));
        $unparsed = TomlUtils::stringSlice($value, $i);
        if (! $isUnparsedAllowed && $unparsed !== '') {
            throw new TomlError();
        }

        return ['int' => $int, 'unparsed' => $unparsed];
    }

    /**
     * @throws TomlError
     */
    public function digitalChecks($radix, $value): bool
    {
        if ($radix === 10) {
            return TomlUtils::isDecimal($value);
        }
        if ($radix === 16) {
            return TomlUtils::isHexadecimal($value);
        }
        if ($radix === 8) {
            return TomlUtils::isOctal($value);
        }
        if ($radix === 2) {
            return TomlUtils::isBinary($value);
        }

        throw new TomlError('digitalChecks radix problem');
    }

    /**
     * @throws TomlError
     */
    public function float($value): TomlToken
    {
        $parsed = $this->parseInteger($value, true, false, true, 10);
        $float = $parsed['int'];
        $unparsed = $parsed['unparsed'];
        if ($this->tokenizer->take('PERIOD')) {
            if ($unparsed !== '') {
                throw new TomlError();
            }
            $token = $this->tokenizer->expect('BARE');
            $result = $this->parseInteger($token->value, false, true, true, 10);
            $float .= ".{$result['int']}";
            $unparsed = $result['unparsed'];
        }
        if (str_starts_with($unparsed, 'e') || str_starts_with($unparsed, 'E')) {
            $float .= 'e';
            if (strlen($unparsed) === 1) {
                $this->tokenizer->assert('PLUS');
                $token = $this->tokenizer->expect('BARE');
                $float .= '+';
                $float += $this->parseInteger($token->value, false, true, false, 10)['int'];
            } else {
                $float .= $this->parseInteger(substr($unparsed, 1), true, true, false, 10)['int'];
            }
        } elseif ($unparsed !== '') {
            throw new TomlError();
        }

        return TomlToken::fromArray([
            'type' => 'FLOAT', 'value' => (float) $float,
        ]);
    }

    /**
     * @throws TomlError
     */
    public function plus(): TomlToken
    {
        $token = $this->tokenizer->expect('BARE');

        return $this->number("+{$token->value}");
    }

    /**
     * @throws TomlError
     */
    public function array(): TomlToken
    {
        $arrayNode = TomlToken::fromArray([
            'type' => 'ARRAY', 'elements' => []]);
        for (; ;) {
            $this->takeCommentsAndNewlines();
            if ($this->tokenizer->peek()->type === 'RIGHT_SQUARE_BRACKET') {
                break;
            }
            $value = $this->value();
            $arrayNode->elements[] = $value;
            $this->takeCommentsAndNewlines();
            if (! $this->tokenizer->take('COMMA')) {
                $this->takeCommentsAndNewlines();
                break;
            }
        }
        $this->tokenizer->assert('RIGHT_SQUARE_BRACKET');

        return $arrayNode;
    }

    /**
     * @throws TomlError
     */
    public function inlineTable(): TomlToken
    {
        $this->tokenizer->take('WHITESPACE');
        $inlineTableNode = TomlToken::fromArray([
            'type' => 'INLINE_TABLE', 'elements' => []]);
        if ($this->tokenizer->take('RIGHT_CURLY_BRACKET')) {
            return $inlineTableNode;
        }
        $keystore = new TomlKeystore();
        for (; ;) {
            $keyValue = $this->keyValuePair();
            $keystore->addNode($keyValue);
            $inlineTableNode->elements[] = $keyValue;
            $this->tokenizer->take('WHITESPACE');
            if ($this->tokenizer->take('RIGHT_CURLY_BRACKET')) {
                break;
            }
            $this->tokenizer->assert('COMMA');
        }

        return $inlineTableNode;
    }
}
