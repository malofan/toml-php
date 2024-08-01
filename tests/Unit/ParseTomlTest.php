<?php

use Devium\Toml\TomlDecoder;

$toml = <<<'TOML'
# This is a TOML document
[[tab]]
[[tab.arr]]

arr.val1=1

[[a."b.c"]]

[["b.c"]]
d = 1
TOML;

$json = <<<'JSON'
{
  "tab": [
    {
      "arr": [
        {
          "arr": {
            "val1": 1
          }
        }
      ]
    }
  ],
  "a": {
    "b.c": [
      {}
    ]
  },
  "b.c": [
    {
      "d": 1
    }
  ]
}
JSON;

it('can parse toml', function () use ($toml, $json) {
    $decoder = new TomlDecoder();

    expect($decoder->decode($toml))->toEqual(json_decode($json, false));
});
