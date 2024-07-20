<?php

use Devium\Toml\TomlError;
use Devium\Toml\TomlStruct;

it('parses simple keys', function () {
    expect(TomlStruct::parseKey('key =', 0))->toEqual([['key'], 5]);
    expect(TomlStruct::parseKey('bare_key =', 0))->toEqual([['bare_key'], 10]);
    expect(TomlStruct::parseKey('bare-key =', 0))->toEqual([['bare-key'], 10]);
    expect(TomlStruct::parseKey('1234 =', 0))->toEqual([['1234'], 6]);

    expect(TomlStruct::parseKey('key=', 0))->toEqual([['key'], 4]);
    expect(TomlStruct::parseKey('bare_key=', 0))->toEqual([['bare_key'], 9]);
    expect(TomlStruct::parseKey('bare-key=', 0))->toEqual([['bare-key'], 9]);
    expect(TomlStruct::parseKey('1234=', 0))->toEqual([['1234'], 5]);
});

it('parses quoted keys', function () {
    expect(TomlStruct::parseKey('"127.0.0.1" =', 0)[0])->toEqual(['127.0.0.1']);
    expect(TomlStruct::parseKey('"character encoding" =', 0)[0])->toEqual(['character encoding']);
    expect(TomlStruct::parseKey('"ʎǝʞ" =', 0)[0])->toEqual(['ʎǝʞ']);
    expect(TomlStruct::parseKey("'key2' =", 0)[0])->toEqual(['key2']);
    expect(TomlStruct::parseKey("'quoted \"value\"' =", 0)[0])->toEqual(['quoted "value"']);
});

it('parses empty keys', function () {
    expect(fn () => TomlStruct::parseKey(' =', 0))->toThrow(TomlError::class);
    expect(TomlStruct::parseKey('"" =', 0)[0])->toEqual(['']);
    expect(TomlStruct::parseKey("'' =", 0)[0])->toEqual(['']);
});

it('parses dotted keys', function () {
    expect(TomlStruct::parseKey('physical.color =', 0)[0])->toEqual(['physical', 'color']);
    expect(TomlStruct::parseKey('physical.shape =', 0)[0])->toEqual(['physical', 'shape']);
    expect(TomlStruct::parseKey('site."google.com" =', 0)[0])->toEqual(['site', 'google.com']);
});

it('ignores whitespace', function () {
    expect(TomlStruct::parseKey('fruit.name =', 0)[0])->toEqual(['fruit', 'name']);
    expect(TomlStruct::parseKey('fruit. color =', 0)[0])->toEqual(['fruit', 'color']);
    expect(TomlStruct::parseKey('fruit . flavor =', 0)[0])->toEqual(['fruit', 'flavor']);
    expect(TomlStruct::parseKey('fruit . "flavor" =', 0)[0])->toEqual(['fruit', 'flavor']);
    expect(TomlStruct::parseKey('"fruit" . flavor =', 0)[0])->toEqual(['fruit', 'flavor']);
    expect(TomlStruct::parseKey("\"fruit\"\t.\tflavor =", 0)[0])->toEqual(['fruit', 'flavor']);
});

it('rejects invalid keys', function () {
    expect(fn () => TomlStruct::parseKey('"uwu"\n =', 0))->toThrow(TomlError::class);
    expect(fn () => TomlStruct::parseKey('uwu. =', 0))->toThrow(TomlError::class);
    expect(fn () => TomlStruct::parseKey('éwé =', 0))->toThrow(TomlError::class);
    expect(fn () => TomlStruct::parseKey('uwu..owo =', 0))->toThrow(TomlError::class);
    expect(fn () => TomlStruct::parseKey('uwu.\nowo =', 0))->toThrow(TomlError::class);
    expect(fn () => TomlStruct::parseKey('uwu\n.owo =', 0))->toThrow(TomlError::class);
    expect(fn () => TomlStruct::parseKey('"uwu"\n.owo =', 0))->toThrow(TomlError::class);
    expect(fn () => TomlStruct::parseKey('uwu\n =', 0))->toThrow(TomlError::class);
    expect(fn () => TomlStruct::parseKey('"uwu =', 0))->toThrow(TomlError::class);

    expect(fn () => TomlStruct::parseKey('uwu."owo"hehe =', 0))->toThrow(TomlError::class);

    expect(fn () => TomlStruct::parseKey('uwu hehe =', 0))->toThrow(TomlError::class);

    expect(fn () => TomlStruct::parseKey('"""long\nkey""" = 1', 0))->toThrow(TomlError::class);
});
