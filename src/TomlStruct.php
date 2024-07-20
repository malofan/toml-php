<?php

namespace Devium\Toml;

require_once './vendor/autoload.php';

/**
 * @internal
 */
final class TomlStruct
{
    public const KEY_PART_RE = '/^[a-zA-Z0-9-_]+[ \t]*$/';

    /**
     * @throws TomlError
     */
    public static function parseKey($str, $ptr, $end = '='): array
    {
        $dot = $ptr - 1;
        $parsed = [];

        $endPtr = strpos($str, $end, $ptr);
        if ($endPtr === false) {
            throw new TomlError('incomplete key-value: cannot find end of key', [
                'toml' => $str,
                'ptr' => $ptr,
            ]);
        }

        do {
            $c = TomlUtils::getSymbol($str, ($ptr = ++$dot));

            // If it's whitespace, ignore
            if ($c !== ' ' && $c !== "\t") {
                // If it's a string
                if ($c === '"' || $c === "'") {
                    if ($c === TomlUtils::getSymbol($str, $ptr + 1) && $c === TomlUtils::getSymbol($str, $ptr + 2)) {
                        throw new TomlError('multiline strings are not allowed in keys', [
                            'toml' => $str,
                            'ptr' => $ptr,
                        ]);
                    }

                    $eos = TomlUtils::getStringEnd($str, $ptr);
                    if ($eos < 0) {
                        throw new TomlError('unfinished string encountered', [
                            'toml' => $str,
                            'ptr' => $ptr,
                        ]);
                    }

                    $dotPos = strpos($str, '.', $eos);
                    if ($dotPos === false) {
                        $dotPos = -1;
                    }
                    $dot = $dotPos;
                    $strEnd = TomlUtils::stringSlice($str, $eos, $dot < 0 || $dot > $endPtr ? $endPtr : $dot);

                    $newLine = TomlUtils::indexOfNewLine($strEnd);
                    if ($newLine > -1) {
                        throw new TomlError('newlines are not allowed in keys', [
                            'toml' => $str,
                            'ptr' => $ptr + $dot + $newLine,
                        ]);
                    }

                    if (ltrim($strEnd)) {
                        throw new TomlError('found extra tokens after the string part', [
                            'toml' => $str,
                            'ptr' => $eos,
                        ]);
                    }

                    if ($endPtr < $eos) {
                        $endPtr = strpos($str, $end, $eos);
                        if ($endPtr === false) {
                            throw new TomlError(
                                'incomplete key-value: cannot find end of key',
                                [
                                    'toml' => $str,
                                    'ptr' => $ptr,
                                ],
                            );
                        }
                    }

                    $parsed[] = TomlPrimitive::parseString($str, $ptr, $eos);
                } else {
                    // Normal raw key part consumption and validation
                    $dot = strpos($str, '.', $ptr);
                    if ($dot === false) {
                        $dot = -1;
                    }
                    $part = TomlUtils::stringSlice($str, $ptr, $dot < 0 || $dot > $endPtr ? $endPtr : $dot);
                    if (! preg_match(self::KEY_PART_RE, $part)) {
                        throw new TomlError(
                            'only letter, numbers, dashes and underscores are allowed in keys',
                            [
                                'toml' => $str,
                                'ptr' => $ptr,
                            ],
                        );
                    }

                    $parsed[] = rtrim($part);

                }
            }
            // Until there's no more dot
        } while ($dot + 1 && $dot < $endPtr);

        return [$parsed, TomlUtils::skipVoid($str, $endPtr + 1, true, true)];
    }

    /**
     * @throws TomlError
     */
    public static function parseArray($str, $ptr): array
    {
        $res = [];

        $ptr++;
        while (($c = TomlUtils::getSymbol($str, $ptr++)) !== ']' && $c) {
            if ($c === ',') {
                throw new TomlError('expected value, found comma', [
                    'toml' => $str,
                    'ptr' => $ptr - 1,
                ]);
            } elseif ($c === '#') {
                $ptr = TomlUtils::skipComment($str, $ptr);
            } elseif ($c !== ' ' && $c !== "\t" && $c !== "\n" && $c !== "\r") {
                $e = TomlExtract::extractValue($str, $ptr - 1, ']');
                $res[] = $e[0];
                $ptr = $e[1];
            }
        }

        if (! $c) {
            throw new TomlError('unfinished array encountered', [
                'toml' => $str,
                'ptr' => $ptr,
            ]);
        }

        return [$res, $ptr];
    }

    /**
     * @throws TomlError
     */
    public static function parseInlineTable($str, $ptr): array
    {
        $res = [];
        $seen = [];
        $comma = 0;

        $ptr++;
        while (($c = TomlUtils::getSymbol($str, $ptr++)) !== '}' && $c) {
            if ($c === "\n") {
                throw new TomlError('newlines are not allowed in inline tables', [
                    'toml' => $str,
                    'ptr' => $ptr - 1,
                ]);
            } elseif ($c === '#') {
                throw new TomlError('inline tables cannot contain comments', [
                    'toml' => $str,
                    'ptr' => $ptr - 1,
                ]);
            } elseif ($c === ',') {
                throw new TomlError('expected key-value, found comma', [
                    'toml' => $str,
                    'ptr' => $ptr - 1,
                ]);
            } elseif ($c !== ' ' && $c !== "\t") {
                $k = '';
                $t = &$res;
                $hasOwn = false;

                [$key, $keyEndPtr] = self::parseKey($str, $ptr - 1);
                for ($i = 0; $i < count($key); $i++) {
                    if ($i) {
                        if (! $hasOwn) {
                            $t[$k] = [];
                        }
                        $t = &$t[$k];
                    }

                    $k = $key[$i];

                    if (
                        ($hasOwn = array_key_exists($k, $t)) && (! is_array($t[$k] ?? '') || TomlUtils::setHas($seen, $t[$k]))
                    ) {
                        throw new TomlError('trying to redefine an already defined value', [
                            'toml' => $str,
                            'ptr' => $ptr,
                        ]);
                    }

                    if (! $hasOwn) {
                        $t[$k] = [];
                    }
                }

                if ($hasOwn) {
                    throw new TomlError('trying to redefine an already defined value', [
                        'toml' => $str,
                        'ptr' => $ptr,
                    ]);
                }

                [$value, $valueEndPtr] = TomlExtract::extractValue($str, $keyEndPtr, '}');
                TomlUtils::setAdd($seen, $value);

                $t[$k] = $value;
                $ptr = $valueEndPtr;
                $comma = TomlUtils::getSymbol($str, $ptr - 1) === ',' ? $ptr - 1 : 0;
            }
        }

        if ($comma) {
            throw new TomlError('trailing commas are not allowed in inline tables', [
                'toml' => $str,
                'ptr' => $comma,
            ]);
        }

        if (! $c) {
            throw new TomlError('unfinished table encountered', [
                'toml' => $str,
                'ptr' => $ptr,
            ]);
        }

        return [$res, $ptr];
    }
}
