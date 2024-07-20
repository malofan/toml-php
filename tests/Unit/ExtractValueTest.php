<?php

use Devium\Toml\TomlExtract;

it('extracts value of correct type', function () {
    expect(TomlExtract::extractValue('[ 1, 2 ]', 2, ']'))->toEqual([1, 4]);
    expect(TomlExtract::extractValue('[ "uwu", 2 ]', 2, ']'))->toEqual(['uwu', 8]);
    expect(TomlExtract::extractValue('[ {}, 2 ]', 2, ']'))->toEqual([[], 5]);
    expect(TomlExtract::extractValue('[ 2 ]', 2, ']'))->toEqual([2, 4]);
    expect(TomlExtract::extractValue("2\n", 0))->toEqual([2, 1]);

    expect(TomlExtract::extractValue('"""uwu"""\n', 0))->toEqual(['uwu', 9]);
    expect(TomlExtract::extractValue('"""this is a "multiline string""""\n', 0))
        ->toEqual(['this is a "multiline string"', 34]);
    expect(TomlExtract::extractValue('"""this is a "multiline string"""""\n', 0))
        ->toEqual(['this is a "multiline string""', 35]);
    expect(TomlExtract::extractValue('"uwu""\n', 0))->toEqual(['uwu', 5]);

    expect(TomlExtract::extractValue('"\\\\"\n', 0))->toEqual(['\\', 4]);
    expect(TomlExtract::extractValue("'uwu\\'", 0))->toEqual(['uwu\\', 6]);
});
