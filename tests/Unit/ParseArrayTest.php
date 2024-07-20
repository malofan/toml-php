<?php

use Devium\Toml\TomlError;
use Devium\Toml\TomlStruct;

it('parses arrays', function () {
    expect(TomlStruct::parseArray('[ 1, 2, 3 ]', 0))->toEqual([[1, 2, 3], 11]);
    expect(TomlStruct::parseArray('[1,2,3]', 0))->toEqual([[1, 2, 3], 7]);
    expect(TomlStruct::parseArray('[ "red", "yellow", "green" ]', 0)[0])->toEqual(['red', 'yellow', 'green']);
    expect(TomlStruct::parseArray('[ "all", \'strings\', """are the same""", \'\'\'type\'\'\' ]', 0)[0])
        ->toEqual(['all', 'strings', 'are the same', 'type']);
});

it('parses arrays of mixed types', function () {
    expect(TomlStruct::parseArray('[ 0.1, 0.2, 0.5, 1, 2, 5 ]', 0)[0])->toEqual([0.1, 0.2, 0.5, 1, 2, 5]);
    expect(TomlStruct::parseArray('[ 10, "red", false ]', 0)[0])->toEqual([10, 'red', false]);
});

it('parses nested arrays', function () {
    expect(TomlStruct::parseArray('[ [ 1, 2 ], [3, 4, 5] ]', 0)[0])->toEqual([[1, 2], [3, 4, 5]]);
    expect(TomlStruct::parseArray('[ [ 1, 2 ], ["a", "b", "c"] ]', 0)[0])->toEqual([[1, 2], ['a', 'b', 'c']]);
});

it('parses inline table values', function () {
    expect(TomlStruct::parseArray('[ { a = "uwu", b = 1, c = false } ]', 0)[0])->toEqual([['a' => 'uwu', 'b' => 1, 'c' => false]]);
});

it('handles multiline arrays', function () {
    expect(TomlStruct::parseArray("[\n  1, 2, 3\n]", 0)[0])->toEqual([1, 2, 3]);
    expect(TomlStruct::parseArray("[\n  1,\n  2\n]", 0)[0])->toEqual([1, 2]);

    expect(TomlStruct::parseArray("[\r\n  1, 2, 3\r\n]", 0)[0])->toEqual([1, 2, 3]);
    expect(TomlStruct::parseArray("[\r\n  1,\r\n  2\r\n]", 0)[0])->toEqual([1, 2]);
});

it('tolerates trailing commas', function () {
    expect(TomlStruct::parseArray('[ 1, 2, 3, ]', 0)[0])->toEqual([1, 2, 3]);
    expect(TomlStruct::parseArray("[\n  1,\n  2,\n]", 0)[0])->toEqual([1, 2]);

    expect(TomlStruct::parseArray("[\r\n  1,\r\n  2,\r\n]", 0)[0])->toEqual([1, 2]);
});

it('is not bothered by comments', function () {
    expect(TomlStruct::parseArray("[\n  1,\n  2, # uwu\n  # hehe 3,\n  4,\n  # owo\n]", 0)[0])->toEqual([1, 2, 4]);
    expect(TomlStruct::parseArray("[\r\n  1,\r\n  2, # uwu\r\n  # hehe 3,\r\n  4,\r\n  # owo\r\n]", 0)[0])->toEqual([1, 2, 4]);

    expect(TomlStruct::parseArray("[ 1,# 9, 9,\n2#,9\n,#9\n3#]\n,4]", 0))->toEqual([[1, 2, 3, 4], 28]);
    expect(TomlStruct::parseArray("[ 1,# 9, 9,\n2#,9\n]", 0))->toEqual([[1, 2], 18]);
    expect(TomlStruct::parseArray("[[[[#[\"#\"],\n[\"#\"]]]]#]\n]", 0))->toEqual([[[[[['#']]]]], 24]);
});

it('rejects invalid arrays', function () {
    expect(fn () => TomlStruct::parseArray('[ 1,, 2]', 0))->toThrow(TomlError::class);
    expect(fn () => TomlStruct::parseArray('[ 1, 2, 3 ', 0))->toThrow(TomlError::class);
    expect(fn () => TomlStruct::parseArray('[ 1, "2" a, 3 ]', 0))->toThrow(TomlError::class);
});

it('consumes only an array and aborts', function () {
    expect(TomlStruct::parseArray('[ 1, 2, 3 ]\nnext-value = 10', 0))->toEqual([[1, 2, 3], 11]);
    expect(TomlStruct::parseArray('[ { a = "uwu", b = 1, c = false, d = [ 1 ] } ]\nnext-value = 10', 0))
        ->toEqual([[['a' => 'uwu', 'b' => 1, 'c' => false, 'd' => [1]]], 46]);
});
