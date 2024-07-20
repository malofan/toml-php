<?php

namespace Devium\Toml;

require_once './vendor/autoload.php';

/**
 * @internal
 */
final class TomlExtract
{
    /**
     * @throws TomlError
     */
    public static function sliceAndTrimEndOf($str, $startPtr, $endPtr, $allowNewLines = null): array
    {
        $value = TomlUtils::stringSlice($str, $startPtr, $endPtr);

        $commentIdx = strpos($value, '#');
        if ($commentIdx > -1) {
            // The call to skipComment allows to "validate" the comment
            // (absence of control characters)
            TomlUtils::skipComment($str, $commentIdx);
            $value = TomlUtils::stringSlice($value, 0, $commentIdx);
        }

        $trimmed = rtrim($value);

        if (! $allowNewLines) {
            $newlineIdx = strpos($value, "\n", strlen($trimmed));
            if ($newlineIdx > -1) {
                throw new TomlError('newlines are not allowed in inline tables', [
                    'toml' => $str,
                    'ptr' => $startPtr + $newlineIdx,
                ]);
            }
        }

        return [$trimmed, $commentIdx];
    }

    /**
     * @throws TomlError
     */
    public static function extractValue($str, $ptr, $end): array
    {
        $c = TomlUtils::getSymbol($str, $ptr);
        if ($c === '[' || $c === '{') {
            [$value, $endPtr] =
                $c === '[' ? TomlStruct::parseArray($str, $ptr) : TomlStruct::parseInlineTable($str, $ptr);

            $newPtr = TomlUtils::skipUntil($str, $endPtr, ',', $end);
            if ($end === '}') {
                $nextNewLine = TomlUtils::indexOfNewline($str, $endPtr, $newPtr);
                if ($nextNewLine > -1) {
                    throw new TomlError('newlines are not allowed in inline tables', [
                        'toml' => $str,
                        'ptr' => $nextNewLine,
                    ]);
                }
            }

            return [$value, $newPtr];
        }

        if ($c === '"' || $c === "'") {
            $endPtr = TomlUtils::getStringEnd($str, $ptr);
            $parsed = TomlPrimitive::parseString($str, $ptr, $endPtr);
            if ($end) {
                $endPtr = TomlUtils::skipVoid($str, $endPtr, $end !== ']');

                $endS = TomlUtils::getSymbol($str, $endPtr);

                if (
                    $endS &&
                    $endS !== ',' &&
                    $endS !== $end &&
                    $endS !== "\n" &&
                    $endS !== "\r"
                ) {
                    throw new TomlError('unexpected character encountered', [
                        'toml' => $str,
                        'ptr' => $endPtr,
                    ]);
                }

                $endPtr += +(TomlUtils::getSymbol($str, $endPtr) === ',');
            }

            return [$parsed, $endPtr];
        }

        $endPtr = TomlUtils::skipUntil($str, $ptr, ',', $end);
        $slice = self::sliceAndTrimEndOf(
            $str,
            $ptr,
            $endPtr - +(TomlUtils::getSymbol($str, $endPtr - 1) === ','),
            $end === ']',
        );
        if (! $slice || ! $slice[0]) {
            throw new TomlError(
                'incomplete key-value declaration: no value specified',
                [
                    'toml' => $str,
                    'ptr' => $ptr,
                ],
            );
        }

        if ($end && $slice[1] > -1) {
            $endPtr = TomlUtils::skipVoid($str, $ptr + $slice[1]);
            $endPtr += +(TomlUtils::getSymbol($str, $endPtr) === ',');
        }

        return [TomlPrimitive::parseValue($slice[0], $str, $ptr), $endPtr];
    }
}
