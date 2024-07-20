<?php

use Devium\Toml\TomlError;
use Devium\Toml\TomlPrimitive;

it('parses a string', function () {
    expect(TomlPrimitive::parseString('"this is a string"'))->toBe('this is a string');
    expect(TomlPrimitive::parseString("'this is a string'"))->toBe('this is a string');
});

it('handles escapes in strings', function () {
    expect(TomlPrimitive::parseString('"uwu \\b uwu"'))->toBe("uwu \b uwu");
    expect(TomlPrimitive::parseString('"uwu \\t uwu"'))->toBe("uwu \t uwu");
    expect(TomlPrimitive::parseString('"uwu \\n uwu"'))->toBe("uwu \n uwu");
    expect(TomlPrimitive::parseString('"uwu \\f uwu"'))->toBe("uwu \f uwu");
    expect(TomlPrimitive::parseString('"uwu \\r uwu"'))->toBe("uwu \r uwu");
    expect(TomlPrimitive::parseString('"uwu \\" uwu"'))->toBe('uwu " uwu');
    expect(TomlPrimitive::parseString('"uwu \\\\ uwu"'))->toBe('uwu \\ uwu');
    expect(TomlPrimitive::parseString('"uwu \\u2764 uwu"'))->toBe('uwu ❤ uwu');
    expect(TomlPrimitive::parseString('"uwu \\U0001F43F uwu"'))->toBe('uwu 🐿 uwu');
});

it('ignores escapes in literal strings', function () {
    expect(TomlPrimitive::parseString("'uwu \\ uwu'"))->toBe('uwu \\ uwu');
});

it('rejects invalid escapes', function () {
    expect(fn () => TomlPrimitive::parseString('"uwu \\x uwu"'))->toThrow(TomlError::class);
    expect(fn () => TomlPrimitive::parseString('"uwu \\\' uwu"'))->toThrow(TomlError::class);
    expect(fn () => TomlPrimitive::parseString("\"uwu \\\n uwu\""))->toThrow(TomlError::class);
    expect(fn () => TomlPrimitive::parseString('"uwu \\ uwu"'))->toThrow(TomlError::class);
    expect(fn () => TomlPrimitive::parseString('"""uwu \\ uwu"""'))->toThrow(TomlError::class);
    /* @todo expect(fn() => TomlPrimitive::parseString('"uwu \\UFFFFFFFF uwu"'))->toThrow(TomlError::class);*/

    expect(fn () => TomlPrimitive::parseString('"uwu \\u276 uwu"'))->toThrow(TomlError::class);
    expect(fn () => TomlPrimitive::parseString('"uwu \\U0001F43 uwu"'))->toThrow(TomlError::class);
});

it('rejects control characters', function () {
    expect(fn () => TomlPrimitive::parseString('"uwu \x00 uwu'))->toThrow(TomlError::class);
    /* @todo expect(fn() => TomlPrimitive::parseString('"uwu \b uwu"'))->toThrow(TomlError::class);*/
    expect(fn () => TomlPrimitive::parseString('"uwu \x1f uwu"'))->toThrow(TomlError::class);
});

it('parses multiline strings', function () {
    expect(TomlPrimitive::parseString('"""this is a\nmultiline string"""'))->toBe("this is a\nmultiline string");
    expect(TomlPrimitive::parseString("'''this is a\nmultiline string'''"))->toBe("this is a\nmultiline string");
    expect(TomlPrimitive::parseString('"""this is a "multiline string""""'))->toBe('this is a "multiline string"');
});

it('handles escaped line returns in multiline', function () {
    expect(TomlPrimitive::parseString("\"\"\"this is a \\\nmultiline string that has no real linebreak\"\"\""))->toBe('this is a multiline string that has no real linebreak');
    expect(TomlPrimitive::parseString("\"\"\"this is a \\\n\n\n   multiline string that has no real linebreak\"\"\""))->toBe('this is a multiline string that has no real linebreak');

    expect(TomlPrimitive::parseString("\"\"\"this is a \\\r\nmultiline string that has no real linebreak\"\"\""))->toBe('this is a multiline string that has no real linebreak');
    /* @todo expect(TomlPrimitive::parseString('"""this is a \\\r\n\r\n\r\n   multiline string that has no real linebreak"""'))->toBe('this is a multiline string that has no real linebreak');*/
    expect(TomlPrimitive::parseString("\"\"\"this is a \\    \nmultiline string that has no real linebreak\"\"\""))->toBe('this is a multiline string that has no real linebreak');
});

it('trims initial whitespace in multiline strings', function () {
    expect(TomlPrimitive::parseString("\"\"\"\nuwu\"\"\""))->toBe('uwu');
    expect(TomlPrimitive::parseString("\"\"\"\ruwu\"\"\""))->toBe('uwu');
    expect(TomlPrimitive::parseString("\"\"\"\r\nuwu\"\"\""))->toBe('uwu');

    expect(TomlPrimitive::parseString("\"\"\"\nuwu\n\"\"\""))->toBe("uwu\n");
});
