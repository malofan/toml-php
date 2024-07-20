<?php

namespace Devium\Toml;

require_once './vendor/autoload.php';

/**
 * @internal
 */
final class TomlUtils
{
    public static function setHas(&$set, $array): bool
    {
        if (is_array($array)) {
            ksort($array);
            $array = json_encode($array);
        }

        return array_key_exists($array, $set);
    }

    public static function setAdd(&$set, $array): void
    {
        if (! self::setHas($set, $array)) {
            if (is_array($array)) {
                ksort($array);
                $array = json_encode($array);
            }
            $set[$array] = 1;
        }
    }

    public static function stringSlice($str, $start, $end): string
    {
        var_dump([
            'str' => $str,
            'start' => $start,
            'end' => $end,
        ]);
        $end = $end - $start;

        return substr($str, $start, $end);
    }

    public static function getSymbol($str, $index): string
    {
        return $str[$index] ?? '';
    }

    public static function indexOfNewLine($string, $start = 0, $end = null): int
    {
        if (is_null($end)) {
            $end = strlen($string);
        }
        $pos = strpos($string, "\n", $start);
        if ($pos === false) {
            return -1;
        }
        if ($pos > 0 && self::getSymbol($string, $pos - 1) === "\r") {
            $pos--;
        }

        return $pos <= $end ? $pos : -1;
    }

    public static function skipComment($string, $ptr)
    {
        for ($i = $ptr; $i < strlen($string); $i++) {
            $c = self::getSymbol($string, $i);
            if ($c === "\n") {
                return $i;
            }

            if ($c === "\r" && self::getSymbol($string, $i + 1) === "\n") {
                return $i + 1;
            }

            if (($c < "\x20" && $c !== "\t") || $c === "\x7f") {
                throw new TomlError('control characters are not allowed in comments', [
                    'toml' => $string,
                    'ptr' => $ptr,
                ]);
            }
        }

        return strlen($string);
    }

    public static function skipVoid(
        $str,
        $ptr,
        $banNewLines = null,
        $banComments = null
    ): int {
        while (
            ($c = self::getSymbol($str, $ptr)) === ' ' ||
            $c === "\t" ||
            (! $banNewLines && ($c === "\n" || ($c === "\r" && self::getSymbol($str, $ptr + 1) === "\n")))
        ) {
            $ptr++;
        }

        return $banComments || $c !== '#'
            ? $ptr
            : self::skipVoid($str, self::skipComment($str, $ptr), $banNewLines);
    }

    public static function skipUntil(
        $str,
        $ptr,
        $sep,
        $end = null,
        $banNewLines = null
    ) {
        if (! $end) {
            $ptr = self::indexOfNewline($str, $ptr);

            return $ptr < 0 ? strlen($str) : $ptr;
        }

        for ($i = $ptr; $i < strlen($str); $i++) {
            $c = self::getSymbol($str, $i);
            if ($c === '#') {
                $i = self::indexOfNewline($str, $i);
            } elseif ($c === $sep) {
                return $i + 1;
            } elseif ($c === $end) {
                return $i;
            } elseif (
                $banNewLines &&
                ($c === "\n" || ($c === "\r" && self::getSymbol($str, $i + 1) === "\n"))
            ) {
                return $i;
            }
        }

        throw new TomlError('cannot find end of structure', [
            'toml' => $str,
            'ptr' => $ptr,
        ]);
    }

    public static function getStringEnd($str, $seek): int
    {
        $first = self::getSymbol($str, $seek);
        $target =
            $first === self::getSymbol($str, $seek + 1) && self::getSymbol($str, $seek + 1) === self::getSymbol($str, $seek + 2)
                ? self::stringSlice($str, $seek, $seek + 3)
                : $first;

        $seek += strlen($target) - 1;
        $pos = strpos($str, $target, ++$seek);
        if ($pos === false) {
            return -1;
        }
        do {
            $seek = $pos;
        } while (
            $seek > -1 &&
            $first !== "'" &&
            self::getSymbol($str, $seek - 1) === '\\' &&
            self::getSymbol($str, $seek - 2) !== '\\'
        );

        if ($seek > -1) {
            $seek += strlen($target);
            if (strlen($target) > 1) {
                if (self::getSymbol($str, $seek) === $first) {
                    $seek++;
                }
                if (self::getSymbol($str, $seek) === $first) {
                    $seek++;
                }
            }
        }

        return $seek;
    }
}

//var_dump(TomlUtils::indexOfNewline("test\n") === 4);
//var_dump(TomlUtils::indexOfNewline("test\r\n") === 4);
//var_dump(TomlUtils::indexOfNewline("test\ruwu\n") === 8);
//var_dump(TomlUtils::indexOfNewline("test") === -1);
//
//var_dump(TomlUtils::skipVoid("    uwu", 0) === 4);
//var_dump(TomlUtils::skipVoid("    uwu", 2) === 4);
//var_dump(TomlUtils::skipVoid("\t uwu", 0) === 2);
//var_dump(TomlUtils::skipVoid("uwu", 0) === 0);
//var_dump(TomlUtils::skipVoid("\r\nuwu", 0) === 2);
//
//var_dump(TomlUtils::skipVoid("    uwu", 0, true) === 4);
//var_dump(TomlUtils::skipVoid("\r\nuwu", 0, true) === 0);
//
//var_dump(TomlUtils::skipVoid("    # this is a comment\n   uwu", 0) === 27);
//var_dump(TomlUtils::skipVoid("    # this is a comment\n   uwu", 0, true) === 23);
//
//var_dump(TomlUtils::skipUntil("[ 3, 4, ]", 1, ",", "]") === 4);
//var_dump(TomlUtils::skipUntil("[ 3, 4, ]", 4, ",", "]") === 7);
//var_dump(TomlUtils::skipUntil("[ 3, 4, ]", 7, ",", "]") === 8);
//
//var_dump(TomlUtils::skipUntil("[ [ 1, 2 ], [ 3, 4 ] ]", 6, ",", "]") === 9);
