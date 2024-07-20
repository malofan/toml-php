<?php

use Devium\Toml\TomlError;
use Devium\Toml\TomlStruct;

it('parses inline tables', function () {
    expect(TomlStruct::parseInlineTable('{ first = "Tom", last = "Preston-Werner" }', 0))
        ->toEqual([['first' => 'Tom', 'last' => 'Preston-Werner'], 42]);
    expect(TomlStruct::parseInlineTable('{ x = 1, y = 2 }', 0))
        ->toEqual([['x' => 1, 'y' => 2], 16]);
    expect(TomlStruct::parseInlineTable('{ type.name = "pug", type.value = 1, "hehe.owo" = "uwu" }', 0))
        ->toEqual([['type' => ['name' => 'pug', 'value' => 1], 'hehe.owo' => 'uwu'], 57]);
    expect(TomlStruct::parseInlineTable('{}', 0))
        ->toEqual([[], 2]);
});

it('parse inline tables with non traditional spaces', function () {
    expect(TomlStruct::parseInlineTable('{ first = "Tom" ,last = "Preston-Werner" }', 0))
        ->toEqual([['first' => 'Tom', 'last' => 'Preston-Werner'], 42]);
    expect(TomlStruct::parseInlineTable('{ first = "Tom" , last = "Preston-Werner" }', 0))
        ->toEqual([['first' => 'Tom', 'last' => 'Preston-Werner'], 43]);
    expect(TomlStruct::parseInlineTable('{first="Tom",last="Preston-Werner"}', 0))
        ->toEqual([['first' => 'Tom', 'last' => 'Preston-Werner'], 35]);
    expect(TomlStruct::parseInlineTable('{	first="Tom"    ,	last="Preston-Werner"}', 0))
        ->toEqual([['first' => 'Tom', 'last' => 'Preston-Werner'], 41]);
});

it('parses valid multiline tables', function () {
    expect(TomlStruct::parseInlineTable("{ test = \"\"\"Multiline\nstrings\nare\nvalid\"\"\" }", 0))
        ->toEqual([['test' => "Multiline\nstrings\nare\nvalid"], 44]);
});

it('parses nested structures', function () {
    expect(TomlStruct::parseInlineTable('{ uwu = { owo = true, cute = true, mean = false } }', 0))
        ->toEqual([['uwu' => ['owo' => true, 'cute' => true, 'mean' => false]], 51]);
    expect(TomlStruct::parseInlineTable('{ uwu = [ "meow", "nya", "hehe", ] }', 0))
        ->toEqual([['uwu' => ['meow', 'nya', 'hehe']], 36]);
});

it('rejects duplicate keys', function () {
    expect(fn () => TomlStruct::parseInlineTable('{ uwu = false, uwu = true }', 0))
        ->toThrow(TomlError::class);
    expect(fn () => TomlStruct::parseInlineTable('{ uwu.hehe = "owo", uwu = false }', 0))
        ->toThrow(TomlError::class);
    expect(fn () => TomlStruct::parseInlineTable('{ uwu = "owo", uwu.hehe = false }', 0))
        ->toThrow(TomlError::class);
});

it('rejects multiline tables', function () {
    expect(fn () => TomlStruct::parseInlineTable('{ first = "Tom", last = "Preston-Werner"\n}', 0))
        ->toThrow(TomlError::class);
    expect(fn () => TomlStruct::parseInlineTable('{\n  first = "Tom",\n  last = "Preston-Werner"\n}', 0))
        ->toThrow(TomlError::class);
    expect(fn () => TomlStruct::parseInlineTable('{ first = "Tom", last = \n "Preston-Werner" }', 0))
        ->toThrow(TomlError::class);
    expect(fn () => TomlStruct::parseInlineTable('{ first = "Tom" \n, last = "Preston-Werner" }', 0))
        ->toThrow(TomlError::class);
    expect(fn () => TomlStruct::parseInlineTable('{ first = "Tom",  last  \n = "Preston-Werner" }', 0))
        ->toThrow(TomlError::class);
    expect(fn () => TomlStruct::parseInlineTable('{ test = 0 \n  }', 0))
        ->toThrow(TomlError::class);
    /* @todo expect(fn () => TomlStruct::parseInlineTable('{ test = {} \n  }', 0))->toThrow(TomlError::class); */
    /* @todo expect(fn () => TomlStruct::parseInlineTable('{ test = [] \n  }', 0))->toThrow(TomlError::class); */
});

it('rejects tables that are not finished', function () {
    expect(fn () => TomlStruct::parseInlineTable('{ first = "Tom", last = "Preston-Werner"\n', 0))
        ->toThrow(TomlError::class);
    expect(fn () => TomlStruct::parseInlineTable('{', 0))
        ->toThrow(TomlError::class);
});

it('rejects invalid tables', function () {
    expect(fn () => TomlStruct::parseInlineTable('{ first = "Tom",, last = "Preston-Werner" }', 0))
        ->toThrow(TomlError::class);
    expect(fn () => TomlStruct::parseInlineTable('{ first = "Tom", # }', 0))
        ->toThrow(TomlError::class);
    expect(fn () => TomlStruct::parseInlineTable('{ first = "Tom", }', 0))
        ->toThrow(TomlError::class);
});

it('consumes only a table and aborts', function () {
    expect(TomlStruct::parseInlineTable('{ uwu = 1 }\nnext-value = 10', 0))
        ->toEqual([['uwu' => 1], 11]);
    expect(TomlStruct::parseInlineTable('{ a = [ "uwu" ], b = 1, c = false, d = { hehe = 1 } }\nnext-value = 10', 0))
        ->toEqual([['a' => ['uwu'], 'b' => 1, 'c' => false, 'd' => ['hehe' => 1]], 53]);
});

it('respects inner immutability', function () {
    expect(fn () => TomlStruct::parseInlineTable('{ type = { name = "pug", value = 1 }, type.owo = "uwu" }', 0))
        ->toThrow(TomlError::class);
});
