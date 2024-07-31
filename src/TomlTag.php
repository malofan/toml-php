<?php

namespace Devium\Toml;

use stdClass;

class TomlTag
{
    public static function tagObject(mixed $obj): stdClass|array
    {
        if (is_int($obj)) {
            return ['type' => 'integer', 'value' => (string) $obj];
        }

        if (is_string($obj)) {
            if (preg_match('/^[+-]?\d+[.]\d+$/', $obj)) {
                return ['type' => 'float', 'value' => $obj];
            }

            return ['type' => 'string', 'value' => $obj];
        }

        if (is_numeric($obj)) {
            if (is_nan($obj)) {
                $obj = 'nan';
            }
            if ($obj === -INF) {
                $obj = '-inf';
            }
            if ($obj === INF) {
                $obj = 'inf';
            }

            return ['type' => 'float', 'value' => (string) $obj];
        }

        if (is_bool($obj)) {
            return ['type' => 'bool', 'value' => $obj ? 'true' : 'false'];
        }

        if ($obj instanceof TomlDateTime) {
            return ['type' => 'datetime', 'value' => (string) $obj];
        }

        if ($obj instanceof TomlLocalDate) {
            return ['type' => 'date-local', 'value' => (string) $obj];
        }

        if ($obj instanceof TomlLocalTime) {
            return ['type' => 'time-local', 'value' => (string) $obj];
        }

        if ($obj instanceof TomlLocalDateTime) {
            return ['type' => 'datetime-local', 'value' => (string) $obj];
        }

        if (is_array($obj)) {
            return array_map(fn ($item) => self::tagObject($item), $obj);
        }

        $tagged = new stdClass();

        if ($obj instanceof stdClass) {
            $obj = get_object_vars($obj);
        }

        foreach ($obj as $key => $value) {
            $tagged->{$key} = self::tagObject($value);
        }

        return $tagged;
    }
}
