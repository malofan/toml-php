#!/usr/bin/php

<?php

use Devium\Toml\TomlDecoder;

include_once './vendor/autoload.php';

$decode = new TomlDecoder;

try {
    echo $decode->decode($argv[1]);
    exit(0);
} catch (Throwable $e) {
    exit(1);
}
