<?php

use Devium\Toml\TomlError;
use Devium\Toml\TomlParse;

it('parses a simple key-value', function () {
    expect(TomlParse::parse('key = "value"'))->toEqual(json_decode('{ "key": "value" }', true));
    expect(TomlParse::parse("key = \"value\"\nother = 1"))->toEqual(json_decode("{ key: 'value', other: 1 }", true));
    expect(TomlParse::parse("key = \"value\"\r\nother = 1"))->toEqual(json_decode("{ key: 'value', other: 1 }", true));
});

it('parses dotted key-values', function () {
    expect(TomlParse::parse("fruit.apple.color = \"red\"\nfruit.apple.taste.sweet = true"))
        ->toEqual(json_decode("{ fruit: { apple: { color: 'red', taste: { sweet: true } } } }", true));
});

//it('handles comments', function() {
//    const doc = `
//# This is a full-line comment
//key = "value"  # This is a comment at the end of a line
//another = "# This is not a comment"
//`.trim()
//
//	expect(parse(doc))->toEqual({ key: 'value', another: '# This is not a comment' })
//})
//
//it('rejects unspecified values', function() {
//    expect(() => parse('key = # INVALID')).toThrowError(TomlError)
//})
//
//it('rejects invalid keys', function() {
//    expect(() => parse('key."uwu"owo = test')).toThrowError(TomlError)
//})
//
//it('rejects multiple key-values on a single line', function() {
//    expect(() => parse('first = "Tom" last = "Preston-Werner" # INVALID')).toThrowError(TomlError)
//})
//
//it('rejects invalid strings', function() {
//    expect(() => parse('first = "To\nm"')).toThrowError(TomlError)
//})
//
//it('parses docs with tables', function() {
//    const doc = `
//[table-1]
//key1 = "some string"
//key2 = 123
//
//[table-2]
//key1 = "another string"
//key2 = 456
//`.trim()
//
//	expect(parse(doc))->toEqual({
//		'table-1': { key1: 'some string', key2: 123 },
//		'table-2': { key1: 'another string', key2: 456 },
//	})
//})
//
//it('rejects unfinished tables', function() {
//    expect(() => parse('[test\nuwu = test')).toThrowError(TomlError)
//})
//
//it('rejects invalid tables', function() {
//    expect(() => parse('[key."uwu"owo]')).toThrowError(TomlError)
//})
//
//it('parses docs with dotted table and dotted keys', function() {
//    const doc = `
//[dog."tater.man"]
//type.name = "pug"
//`.trim()
//
//	expect(parse(doc))->toEqual({ dog: { 'tater.man': { type: { name: 'pug' } } } })
//})
//
//it('ignores spaces in keys', function() {
//    const doc = `
//[a.b.c]            # this is best practice
//uwu = "owo"
//
//[ d.e.f ]          # same as [d.e.f]
//uwu = "owo"
//
//[ g .  h  . i ]    # same as [g.h.i]
//uwu = "owo"
//
//	[ j . "ʞ" . 'l' ]  # same as [j."ʞ".'l']
//	uwu = "owo"
//`.trim()
//
//	expect(parse(doc))->toEqual({
//		a: { b: { c: { uwu: 'owo' } } },
//		d: { e: { f: { uwu: 'owo' } } },
//		g: { h: { i: { uwu: 'owo' } } },
//		j: { 'ʞ': { l: { uwu: 'owo' } } },
//	})
//})
//
//it('handles empty tables', function() {
//    expect(parse('[uwu]\n'))->toEqual({ uwu: {} })
//})
//
//it('lets super table be defined afterwards', function() {
//    const doc = `
//[x.y.z.w]
//a = 0
//
//[x]
//b = 0
//`.trim()
//
//	expect(parse(doc))->toEqual({
//		x: { b: 0, y: { z: { w: { a: 0 } } } }
//	})
//})
//
//it('allows adding sub-tables', function() {
//    const doc = `[fruit]
//apple.color = "red"
//apple.taste.sweet = true
//
//[fruit.apple.texture]  # you can add sub-tables
//smooth = true
//`.trim()
//
//	expect(parse(doc))->toEqual({
//		fruit: { apple: { color: 'red', taste: { sweet: true }, texture: { smooth: true } } }
//	})
//})
//
//it('rejects tables overriding a defined value', function() {
//    const doc = `
//[fruit]
//apple = "red"
//
//[fruit.apple]
//texture = "smooth"
//`.trim()
//
//	expect(() => parse(doc)).toThrowError(TomlError)
//})
//
//it('parses arrays of tables', function() {
//    const doc = `
//[[products]]
//name = "Hammer"
//sku = 738594937
//
//[[products]]  # empty table within the array
//
//[[products]]
//name = "Nail"
//sku = 284758393
//
//color = "gray"
//`.trim()
//
//	expect(parse(doc))->toEqual({
//		products: [
//			{ name: 'Hammer', sku: 738594937 },
//			{},
//			{ name: 'Nail', sku: 284758393, color: 'gray' },
//		]
//	})
//})
//
//it('rejects invalid arrays of table', function() {
//    expect(() => parse('[[uwu] ]')).toThrowError(TomlError)
//})
//
//it('parses arrays of tables with subtables', function() {
//    const doc = `
//[[fruits]]
//name = "apple"
//
//[fruits.physical]  # subtable
//color = "red"
//shape = "round"
//
//[fruits.physical.cute]  # subtable
//uwu = true
//
//[[fruits.varieties]]  # nested array of tables
//name = "red delicious"
//
//[[fruits.varieties]]
//name = "granny smith"
//
//
//[[fruits]]
//name = "banana"
//
//[[fruits.varieties]]
//name = "plantain"
//`.trim()
//
//	expect(parse(doc))->toEqual({
//		fruits: [
//		  {
//              name: 'apple',
//			physical: {
//              color: 'red',
//			  shape: 'round',
//			  cute: { uwu: true },
//			},
//			varieties: [
//			  { name: 'red delicious' },
//			  { name: 'granny smith' },
//			]
//		  },
//		  {
//              name: 'banana',
//			varieties: [
//			  { name: 'plantain' },
//			],
//		  },
//		],
//	  })
//})
//
//it('rejects subtables of an array of tables if order is reversed', function() {
//    const doc = `
//[fruit.physical]
//color = "red"
//shape = "round"
//
//[[fruit]]
//name = "apple"
//`.trim()
//
//	expect(() => parse(doc)).toThrowError(TomlError)
//})
//
//it('does not allow redefining a statically defined array', function() {
//    const doc = `
//fruits = []
//
//[[fruits]]
//`.trim()
//
//	expect(() => parse(doc)).toThrowError(TomlError)
//})
//
//it('rejects conflicts between arrays of tables and normal tables (array then simple)', function() {
//    const doc = `
//[[fruits]]
//name = "apple"
//
//[[fruits.varieties]]
//name = "red delicious"
//
//[fruits.varieties]
//name = "granny smith"
//`.trim()
//
//	expect(() => parse(doc)).toThrowError(TomlError)
//})
//
//it('rejects conflicts between arrays of tables and normal tables (simple then array)', function() {
//    const doc = `
//[[fruits]]
//name = "apple"
//
//[fruits.physical]
//color = "red"
//shape = "round"
//
//[[fruits.physical]]
//color = "green"
//`.trim()
//
//	expect(() => parse(doc)).toThrowError(TomlError)
//})
//
//describe('table clashes', function() {
//    it('does not allow redefining a table', function() {
//        const doc = `
//[fruit]
//apple = "red"
//
//[fruit]
//orange = "orange"
//`.trim()
//
//		expect(() => parse(doc)).toThrowError(TomlError)
//	})
//
//	it('does not allow dotted keys to redefine tables', function() {
//        const doc = `
//[a.b.c]
//  z = 9
//[a]
//  b.c.t = 9
//`.trim()
//
//		expect(() => parse(doc)).toThrowError(TomlError)
//	})
//
//	it('does not allow redefining tables with [table]', function() {
//        const doc = `
//[fruit]
//apple.color = "red"
//
//[fruit.apple]
//kind = "granny smith"
//`.trim()
//
//		expect(() => parse(doc)).toThrowError(TomlError)
//	})
//
//	it('does not allow clashes between [[table]] and [table]', function() {
//        const doc = `
//[[uwu]]
//[uwu]
//`.trim()
//
//		expect(() => parse(doc)).toThrowError(TomlError)
//	})
//
//	it('does not allow clashes between [[table.a]] and a dotted key within [table]', function() {
//        const doc = `
//[[uwu.owo]]
//
//[uwu]
//owo.hehe = "meow!"
//`.trim()
//
//		expect(() => parse(doc)).toThrowError(TomlError)
//	})
//
//	it('does not allow clashes between [table] and [[table]]', function() {
//        const doc = `
//[uwu]
//[[uwu]]
//`.trim()
//
//		expect(() => parse(doc)).toThrowError(TomlError)
//	})
//
//	it('rejects tables overriding a defined value (inline table)', function() {
//        const doc = `
//[fruit]
//apple = { uwu = "owo" }
//
//[fruit.apple]
//texture = "smooth"
//`.trim()
//
//		expect(() => parse(doc)).toThrowError(TomlError)
//	})
//
//	it('rejects tables overriding a defined value (inline table inner)', function() {
//        const doc = `
//[fruit]
//apple = { uwu = "owo" }
//
//[fruit.apple.hehe]
//texture = "smooth"
//`.trim()
//
//		expect(() => parse(doc)).toThrowError(TomlError)
//	})
//
//	it('does NOT reject duplicate [tables] for arrays of tables', function() {
//        const doc = `
//[[uwu]]
//[uwu.owo]
//hehe = true
//
//[[uwu]]
//[uwu.owo]
//hehe = true
//`.trim()
//
//		expect(parse(doc))->toEqual({
//			uwu: [
//				{ owo: { hehe: true } },
//				{ owo: { hehe: true } },
//			]
//		})
//	})
//
//	it('does NOT reject duplicate [tables] when the table was originally defined as an array', function() {
//        const doc = `
//[[uwu.owo]]
//hehe = true
//
//[[uwu.owo]]
//hehe = false
//
//[uwu]
//meow = "nya"
//`.trim()
//
//		expect(parse(doc))->toEqual({
//			uwu: {
//            owo: [
//					{ hehe: true },
//					{ hehe: false },
//				],
//				meow: "nya",
//			},
//		})
//	})
//})
