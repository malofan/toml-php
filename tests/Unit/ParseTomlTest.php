<?php

use Devium\Toml\TomlDecoder;

$toml = <<<'TOML'
[owner]
name = "Tom Preston-Werner"
dob = 1979-05-27T07:32:00-08:00
TOML;

$json = <<<'JSON'
{
  "owner": {
    "name": "Tom Preston-Werner",
    "dob": "1979-05-27T15:32:00.000Z"
  }
}
JSON;

it('can parse toml', function () use ($toml, $json) {
    $decoder = new TomlDecoder();
    expect($decoder->decode($toml))->dump()->toEqual(json_decode($json, false));
});
