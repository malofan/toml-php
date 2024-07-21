<?php

namespace Devium\Toml;

require_once './vendor/autoload.php';

/**
 * @internal
 */
final class TomlParse
{
    public const DOTTED = 0;

    public const EXPLICIT = 1;

    public const ARRAY = 2;

    public const ARRAY_DOTTED = 3;

    public static function peekTable($key, &$table, &$meta, $type)
    {
        $t = $table;
        $m = $meta;
        $k = null;
        $hasOwn = false;
        $state = null;

        for ($i = 0; $i < count($key); $i++) {
            if ($i) {
                $t = $hasOwn ? $t[$k] : $t[$k] = [];
                $m = ($state = $m[$k])->c;

                if ($type === self::DOTTED && ($state->t === self::EXPLICIT || $state->t === self::ARRAY)) {
                    return null;
                }
                if ($state->t === self::ARRAY) {
                    $l = count($t) - 1;
                    $t = $t[$l];
                    $m = $m[$l]->c;
                }
            }
            $k = $key[$i];
            $hasOwn = array_key_exists($k, $t) && isset($m[$k]) && $m[$k]->t === self::DOTTED && $m[$k]->d;

            if ($hasOwn) {
                return null;
            }

            if ($k === '__proto__') {
                $t[$k] = [];
                $m[$k] = [];
            }

            $m[$k] = (object) [
                't' => $i < count($key) - 1 && $type === self::ARRAY ? self::ARRAY_DOTTED : $type,
                'd' => false,
                'i' => 0,
                'c' => [],
            ];
        }

        $state = $m[$k];
        if ($state->t !== $type && ! ($type === self::EXPLICIT && $state->t === self::ARRAY_DOTTED)) {
            return null;
        }

        if ($type === self::ARRAY) {
            if (! $state->d) {
                $state->d = true;
                $t[$k] = [];
            }
            array_push($t[$k], $t = []);
            $state->c[$state->i++] = $state = (object) ['t' => 1, 'd' => false, 'i' => 0, 'c' => []];
        }

        if ($state->d) {
            return null;
        }

        $state->d = true;
        if ($type === self::EXPLICIT) {
            $t = $hasOwn ? $t[$k] : $t[$k] = [];
        } elseif ($type === self::DOTTED && $hasOwn) {
            return null;
        }

        return [$k, $t, $state->c];
    }

    /**
     * @throws TomlError
     */
    public static function parse($toml)
    {
        $res = [];
        $meta = [];
        $tbl = &$res;
        $m = &$meta;
        for ($ptr = TomlUtils::skipVoid($toml, 0); $ptr < strlen($toml);) {
            if ($toml[$ptr] === '[') {
                $isTableArray = $toml[++$ptr] === '[';
                $k = TomlStruct::parseKey($toml, $ptr += (int) $isTableArray, ']');
                if ($isTableArray) {
                    if ($toml[$k[1] - 1] !== ']') {
                        throw new TomlError('expected end of table declaration', [
                            'toml' => $toml,
                            'ptr' => $k[1] - 1,
                        ]);
                    }
                    $k[1]++;
                }
                $p = self::peekTable(
                    $k[0],
                    $res,
                    $meta,
                    $isTableArray ? 2 : 1
                );
                if (! $p) {
                    throw new TomlError('trying to redefine an already defined table or value', [
                        'toml' => $toml,
                        'ptr' => $ptr,
                    ]);
                }
                $m = $p[2];
                $tbl = $p[1];
                $ptr = $k[1];
            } else {
                $k = TomlStruct::parseKey($toml, $ptr);
                $p = self::peekTable(
                    $k[0],
                    $tbl,
                    $m,
                    0
                );
                if (! $p) {
                    throw new TomlError('trying to redefine an already defined table or value', [
                        'toml' => $toml,
                        'ptr' => $ptr,
                    ]);
                }
                $v = TomlExtract::extractValue($toml, $k[1]);
                $p[1][$p[0]] = $v[0];
                $ptr = $v[1];
            }
            $ptr = TomlUtils::skipVoid($toml, $ptr, true);
            if (isset($toml[$ptr]) && $toml[$ptr] !== "\n" && $toml[$ptr] !== "\r") {
                throw new TomlError('each key-value declaration must be followed by an end-of-line', [
                    'toml' => $toml,
                    'ptr' => $ptr,
                ]);
            }
            $ptr = TomlUtils::skipVoid($toml, $ptr);
        }

        return $res;
    }
}
