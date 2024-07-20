<?php

use Devium\Toml\TomlDate;
use Devium\Toml\TomlError;
use Devium\Toml\TomlPrimitive;

it('parses integers', function() {
    expect(TomlPrimitive::parseValue('+99', '', 0))->toBe(99);
	expect(TomlPrimitive::parseValue('42', '', 0))->toBe(42);
	expect(TomlPrimitive::parseValue('0', '', 0))->toBe(0);
	expect(TomlPrimitive::parseValue('-17', '', 0))->toBe(-17);
});

it('parses integers with underscores', function() {
    expect(TomlPrimitive::parseValue('1_000', '', 0))->toBe(1000);
	expect(TomlPrimitive::parseValue('5_349_221', '', 0))->toBe(5349221);
	expect(TomlPrimitive::parseValue('53_49_221', '', 0))->toBe(5349221);
	expect(TomlPrimitive::parseValue('1_2_3_4_5', '', 0))->toBe(12345);
});

it('parses hex integers', function() {
    expect(TomlPrimitive::parseValue('0xDEADBEEF', '', 0))->toEqual(0xDEADBEEF);
	expect(TomlPrimitive::parseValue('0xdeadbeef', '', 0))->toEqual(0xDEADBEEF);
	expect(TomlPrimitive::parseValue('0xdead_beef', '', 0))->toEqual(0xDEADBEEF);
});

it('parses octal integers', function() {
    expect(TomlPrimitive::parseValue('0o01234567', '', 0))->toEqual(0o01234567);
	expect(TomlPrimitive::parseValue('0o0123_4567', '', 0))->toEqual(0o01234567);
});

it('parses binary integers', function() {
    expect(TomlPrimitive::parseValue('0b11010110', '', 0))->toEqual(0b11010110);
	expect(TomlPrimitive::parseValue('0b1101_0110', '', 0))->toEqual(0b11010110);
});

it('rejects numbers too large', function() {
    expect(fn() => TomlPrimitive::parseValue(PHP_INT_MAX, '', 0))->toThrow(TomlError::class);
});

it('rejects leading zeroes', function() {
    expect(fn() => TomlPrimitive::parseValue('0123', '', 0))->toThrow(TomlError::class);
	expect(fn() => TomlPrimitive::parseValue('01.10', '', 0))->toThrow(TomlError::class);
	expect(fn() => TomlPrimitive::parseValue('0_1.10', '', 0))->toThrow(TomlError::class);
});

it('rejects invalid numbers', function() {
    expect(fn() => TomlPrimitive::parseValue('Infinity', '', 0))->toThrow(TomlError::class);
	expect(fn() => TomlPrimitive::parseValue('NaN', '', 0))->toThrow(TomlError::class);
	expect(fn() => TomlPrimitive::parseValue('+0x01', '', 0))->toThrow(TomlError::class);
	expect(fn() => TomlPrimitive::parseValue('-0x01', '', 0))->toThrow(TomlError::class);
});

it('rejects invalid underscores', function() {
    expect(fn() => TomlPrimitive::parseValue('_10', '', 0))->toThrow(TomlError::class);
	expect(fn() => TomlPrimitive::parseValue('10_', '', 0))->toThrow(TomlError::class);
	expect(fn() => TomlPrimitive::parseValue('1__0', '', 0))->toThrow(TomlError::class);

	expect(fn() => TomlPrimitive::parseValue('+_10', '', 0))->toThrow(TomlError::class);
	expect(fn() => TomlPrimitive::parseValue('0x_10', '', 0))->toThrow(TomlError::class);
});

it('parses floats', function() {
    expect(TomlPrimitive::parseValue('+1.0', '', 0))->toEqual(1);
	expect(TomlPrimitive::parseValue('3.1415', '', 0))->toBe(3.1415);
	expect(TomlPrimitive::parseValue('-0.01', '', 0))->toBe(-0.01);

	expect(TomlPrimitive::parseValue('5e+22', '', 0))->toBe(5e22);
	expect(TomlPrimitive::parseValue('1e06', '', 0))->toEqual(1e6);
	expect(TomlPrimitive::parseValue('-2E-2', '', 0))->toBe(-2e-2);

	expect(TomlPrimitive::parseValue('6.626e-34', '', 0))->toBe(6.626e-34);
});

it('rejects invalid floats', function() {
    expect(fn() => TomlPrimitive::parseValue('.7', '', 0))->toThrow(TomlError::class);
	expect(fn() => TomlPrimitive::parseValue('7.', '', 0))->toThrow(TomlError::class);
	expect(fn() => TomlPrimitive::parseValue('3.e+20', '', 0))->toThrow(TomlError::class);
});

it('parses floats with underscores', function() {
    expect(TomlPrimitive::parseValue('224_617.445_991_228', '', 0))->toBe(224617.445991228);
});

it('handles +0.0 and -0.0', function() {
    expect(TomlPrimitive::parseValue('+0.0', '', 0))->toEqual(+0);
	expect(TomlPrimitive::parseValue('-0.0', '', 0))->toEqual(-0);
});

it('parses infinity', function() {
    expect(TomlPrimitive::parseValue('inf', '', 0))->toBeInfinite();
	expect(TomlPrimitive::parseValue('+inf', '', 0))->toBeInfinite();
	expect(TomlPrimitive::parseValue('-inf', '', 0))->toBeInfinite();

	expect(fn() => TomlPrimitive::parseValue('Inf', '', 0))->toThrow(TomlError::class);
	expect(fn() => TomlPrimitive::parseValue('Infinity', '', 0))->toThrow(TomlError::class);
});

it('parses not a number', function() {
    expect(TomlPrimitive::parseValue('nan', '', 0))->toBeNan();
	expect(TomlPrimitive::parseValue('+nan', '', 0))->toBeNan();
	expect(TomlPrimitive::parseValue('-nan', '', 0))->toBeNan();

    expect(fn() => TomlPrimitive::parseValue('NaN', '', 0))->toThrow(TomlError::class);
});

it('parses booleans', function() {
    expect(TomlPrimitive::parseValue('true', '', 0))->toBeTrue();
	expect(TomlPrimitive::parseValue('false', '', 0))->toBeFalse();

	expect(fn () =>  TomlPrimitive::parseValue('True', '', 0))->toThrow(TomlError::class);
});

it('parses datetimes', function() {
    expect(TomlPrimitive::parseValue('1979-05-27T07:32:00', '', 0))->toEqual(new TomlDate('1979-05-27T07:32:00'));
	expect(TomlPrimitive::parseValue('1979-05-27T00:32:00.999999', '', 0))->toEqual(new TomlDate('1979-05-27T00:32:00.999999'));
	expect(TomlPrimitive::parseValue('1979-05-27T07:32:00Z', '', 0))->toEqual(new TomlDate('1979-05-27T07:32:00Z'));
	expect(TomlPrimitive::parseValue('1979-05-27T00:32:00-07:00', '', 0))->toEqual(new TomlDate('1979-05-27T00:32:00-07:00'));
	expect(TomlPrimitive::parseValue('1979-05-27T00:32:00.999999-07:00', '', 0))->toEqual(new TomlDate('1979-05-27T00:32:00.999999-07:00'));
});

it('parses datetimes with a space instead of T', function() {
    expect(TomlPrimitive::parseValue('1979-05-27 07:32:00Z', '', 0))->toEqual(new TomlDate('1979-05-27T07:32:00Z'));
});

it('parses datetimes with lowercase T', function() {
    expect(TomlPrimitive::parseValue('1979-05-27t07:32:00Z', '', 0))->toEqual(new TomlDate('1979-05-27T07:32:00Z'));
});

it('parses dates', function() {
    expect(TomlPrimitive::parseValue('1979-05-27', '', 0))->toEqual(new TomlDate('1979-05-27'));
});

it('parses times', function() {
    expect(TomlPrimitive::parseValue('07:32:00', '', 0))->toEqual(new TomlDate('07:32:00'));
	expect(TomlPrimitive::parseValue('00:32:00.999999', '', 0))->toEqual(new TomlDate('00:32:00.999999'));
});

it('rejects invalid dates', function() {
    expect(fn () => TomlPrimitive::parseValue('07:3:00', '', 0))->toThrow(TomlError::class);
    expect(fn() => TomlPrimitive::parseValue('27-05-1979', '', 0))->toThrow(TomlError::class);
});
