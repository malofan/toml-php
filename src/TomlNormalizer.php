<?php

namespace Devium\Toml;

/**
 * @internal
 */
final class TomlNormalizer
{
    public static function normalize($node)
    {
        switch ($node['type']) {
            case 'ROOT_TABLE':
                $elements = self::mapNormalize($node['elements']);

                return self::merge(...$elements);
            case 'KEY':
                return self::mapNormalize($node['keys']);
            case 'KEY_VALUE_PAIR':

                $key = self::normalize($node['key']);
                $value = self::normalize($node['value']);

                return self::objectify($key, $value);
            case 'TABLE':

                $key = self::normalize($node['key']);
                $elements = self::mapNormalize($node['elements']);

                return self::objectify($key, self::merge(...$elements));
            case 'ARRAY_TABLE':

                $key = self::normalize($node['key']);
                $elements = self::mapNormalize($node['elements']);

                return self::objectify($key, [self::merge(...$elements)]);
            case 'INLINE_TABLE':
                $elements = self::mapNormalize($node['elements']);

                return self::merge(...$elements);
            case 'ARRAY':
                return self::mapNormalize($node['elements']);
            case 'BARE':
            case 'STRING':
            case 'INTEGER':
            case 'FLOAT':
            case 'BOOLEAN':
            case 'OFFSET_DATE_TIME':
            case 'LOCAL_DATE_TIME':
            case 'LOCAL_DATE':
            case 'LOCAL_TIME':
                return $node['value'];
        }
    }

    public static function mapNormalize(array $items): array
    {
        return array_map(function ($element) {
            return self::normalize($element);
        }, $items);
    }

    public static function merge(...$values)
    {
        return array_reduce($values, function ($acc, $value) {
            foreach (($value ?: []) as $key => $nextValue) {
                $prevValue = $acc[$key] ?? null;
                if (is_array($prevValue) && is_array($nextValue)) {
                    $acc[$key] = array_merge($prevValue, $nextValue);
                } elseif (self::isKeyValuePair($prevValue) && self::isKeyValuePair($nextValue)) {
                    $acc[$key] = self::merge($prevValue, $nextValue);
                } elseif (is_array($prevValue) &&
                    self::isKeyValuePair(end($prevValue)) &&
                    self::isKeyValuePair($nextValue)) {
                    $prevValueLastElement = end($prevValue);
                    $acc[$key] = array_merge(array_slice($prevValue, 0, -1), [self::merge($prevValueLastElement, $nextValue)]);
                } elseif (isset($prevValue)) {
                    throw new TomlError();
                } else {
                    $acc[$key] = $nextValue;
                }
            }

            return $acc;
        }, []);
    }

    public static function isKeyValuePair($value): bool
    {
        if ($value instanceof TomlLocalDateTime || $value instanceof TomlLocalDate || $value instanceof TomlLocalTime) {
            return false;
        }

        if (! is_array($value)) {
            return false;
        }

        return true;
    }

    public static function objectify($key, $value): array
    {
        $initialValue = [];
        $object = &$initialValue;
        foreach (array_slice($key, 0, -1) as $prop) {
            $object[$prop] = [];
            $object = &$object[$prop];
        }
        $object[array_pop($key)] = $value;

        return $initialValue;
    }
}
