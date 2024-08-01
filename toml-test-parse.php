#!/usr/bin/php

<?php

use Devium\Toml\TomlDecoder;
use Devium\Toml\TomlTag;

include_once './vendor/autoload.php';

$decode = new TomlDecoder;

try {
    $parsed = TomlTag::tagObject($decode->decode(file_get_contents('php://stdin')));
    echo json_encode(is_array($parsed) && ! $parsed ? new stdClass : $parsed);
    exit(0);
} catch (Throwable $e) {
    //exit($e->getMessage()."\n".$e->getTraceAsString());
    exit(1);
}
