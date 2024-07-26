#!/usr/bin/php

<?php

use Devium\Toml\TomlDateTime;
use Devium\Toml\TomlDecoder;

include_once './vendor/autoload.php';

function tagObject(mixed $obj)
{
    if (is_int($obj)) {
        return ['type' => 'integer', 'value' => (string) $obj];
    }

    if (is_string($obj)) {
        return ['type' => 'string', 'value' => $obj];
    }

    if (is_bool($obj)) {
        return ['type' => 'bool', 'value' => $obj ? 'true' : 'false'];
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

    if ($obj instanceof TomlDateTime) {
        return ['type' => 'datetime', 'value' => (string) $obj];
    }

    if (is_array($obj)) {
        return array_map(fn ($item) => tagObject($item), $obj);
    }

    $tagged = [];
    foreach ($obj as $key => $value) {
        $tagged[$key] = tagObject($value);
    }

    return $tagged;
}

$decode = new TomlDecoder();

try {
    echo json_encode(tagObject($decode->decode(file_get_contents('php://stdin'))));
    exit(0);
} catch (Throwable $e) {
    exit(1);
}
