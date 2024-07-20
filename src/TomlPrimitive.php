<?php

namespace Devium\Toml;

use Throwable;

require_once './vendor/autoload.php';

/**
 * @internal
 */
final class TomlPrimitive
{
    public const INT_REGEX = '/^((0x[0-9a-fA-F](_?[0-9a-fA-F])*)|(([+-]|0[ob])?\d(_?\d)*))$/';

    public const FLOAT_REGEX = '/^[+-]?\d(_?\d)*(\.\d(_?\d)*)?([eE][+-]?\d(_?\d)*)?$/';

    public const LEADING_ZERO = '/^[+-]?0[0-9_]/';

    public const ESCAPE_REGEX = '/^[0-9a-f]{4,8}$/i';

    public const ESC_MAP = [
        'b' => "\b",
        't' => "\t",
        'n' => "\n",
        'f' => "\f",
        'r' => "\r",
        '"' => '"',
        '\\' => '\\',
    ];

    /**
     * @throws TomlError
     */
    public static function parseString($str, $ptr = 0, $endPtr = null): string
    {
        if (is_null($endPtr)) {
            $endPtr = strlen($str);
        }

        $isLiteral = TomlUtils::getSymbol($str, $ptr) === "'";
        $isMultiline =
            TomlUtils::getSymbol($str, $ptr++) === TomlUtils::getSymbol($str, $ptr)
            &&
            TomlUtils::getSymbol($str, $ptr) === TomlUtils::getSymbol($str, $ptr + 1);

        if ($isMultiline) {
            $endPtr -= 2;
            if (TomlUtils::getSymbol($str, ($ptr += 2)) === "\r") {
                $ptr++;
            }
            if (TomlUtils::getSymbol($str, $ptr) === "\n") {
                $ptr++;
            }
        }

        $tmp = 0;
        $isEscape = false;
        $parsed = '';
        $sliceStart = $ptr;
        while ($ptr < $endPtr - 1) {
            $c = TomlUtils::getSymbol($str, $ptr++);
            if ($c === "\n" || ($c === "\r" && TomlUtils::getSymbol($str, $ptr) === "\n")) {
                if (! $isMultiline) {
                    throw new TomlError('newlines are not allowed in strings', [
                        'toml' => $str,
                        'ptr' => $ptr - 1,
                    ]);
                }
            } elseif (($c < "\x20" && $c !== "\t") || $c === "\x7f") {
                throw new TomlError('control characters are not allowed in strings', [
                    'toml' => $str,
                    'ptr' => $ptr - 1,
                ]);
            }

            if ($isEscape) {
                $isEscape = false;
                if ($c === 'u' || $c === 'U') {
                    // Unicode escape
                    $code = TomlUtils::stringSlice($str, $ptr, ($ptr += ($c === 'u' ? 4 : 8)));
                    if (! preg_match(self::ESCAPE_REGEX, $code)) {
                        throw new TomlError('invalid unicode escape', [
                            'toml' => $str,
                            'ptr' => $tmp,
                        ]);
                    }

                    try {
                        $parsed .= mb_chr(intval($code, 16));
                    } catch (Throwable) {
                        throw new TomlError('invalid unicode escape', [
                            'toml' => $str,
                            'ptr' => $tmp,
                        ]);
                    }
                } elseif (
                    $isMultiline &&
                    ($c === "\n" || $c === ' ' || $c === "\t" || $c === "\r")
                ) {
                    // Multiline escape
                    $ptr = TomlUtils::skipVoid($str, $ptr - 1, true);
                    if (TomlUtils::getSymbol($str, $ptr) !== "\n" && TomlUtils::getSymbol($str, $ptr) !== "\r") {
                        throw new TomlError(
                            'invalid escape: only line-ending whitespace may be escaped',
                            [
                                'toml' => $str,
                                'ptr' => $tmp,
                            ],
                        );
                    }
                    $ptr = TomlUtils::skipVoid($str, $ptr);
                } elseif (in_array($c, array_keys(self::ESC_MAP))) {
                    // Classic escape
                    $parsed .= self::ESC_MAP[$c];
                } else {
                    throw new TomlError('unrecognized escape sequence', [
                        'toml' => $str,
                        'ptr' => $tmp,
                    ]);
                }

                $sliceStart = $ptr;
            } elseif (! $isLiteral && $c === '\\') {
                $tmp = $ptr - 1;
                $isEscape = true;
                $parsed .= TomlUtils::stringSlice($str, $sliceStart, $tmp);
            }
        }

        return $parsed.TomlUtils::stringSlice($str, $sliceStart, $endPtr - 1);
    }

    /**
     * @throws TomlError
     */
    public static function parseValue($value, $toml, $ptr): int|bool|float|TomlDate
    {
        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }
        if ($value === '-inf') {
            return -INF;
        }
        if ($value === 'inf' || $value === '+inf') {
            return INF;
        }
        if ($value === 'nan' || $value === '+nan' || $value === '-nan') {
            return NAN;
        }

        if ($value === '-0') {
            return 0;
        } // Avoid FP representation of -0

        // Numbers
        if (($isInt = preg_match(self::INT_REGEX, $value)) || preg_match(self::FLOAT_REGEX, $value)) {
            if (preg_match(self::LEADING_ZERO, $value)) {
                throw new TomlError('leading zeroes are not allowed', [
                    'toml' => $toml,
                    'ptr' => $ptr,
                ]);
            }

            $numeric = str_replace('_', '', $value);

            if (is_nan(floatval($numeric))) {
                throw new TomlError('invalid number', [
                    'toml' => $toml,
                    'ptr' => $ptr,
                ]);
            }

            if ($isInt && ! (is_int($numeric - 1) && is_int($numeric + 1))) {
                throw new TomlError('integer value cannot be represented losslessly', [
                    'toml' => $toml,
                    'ptr' => $ptr,
                ]);
            }

            if (str_starts_with($numeric, '0x')) {
                return hexdec($numeric);
            }

            if (str_starts_with($numeric, '0o')) {
                return octdec($numeric);
            }

            if (str_starts_with($numeric, '0b')) {
                return bindec($numeric);
            }

            return $numeric;
        }

        $date = new TomlDate($value);
        if (! $date->isValid()) {
            throw new TomlError('invalid value', [
                'toml' => $toml,
                'ptr' => $ptr,
            ]);
        }

        return $date;
    }
}
