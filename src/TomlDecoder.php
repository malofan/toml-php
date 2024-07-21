<?php

namespace Devium\Toml;

/**
 * @internal
 */
final class TomlDecoder
{
    public function decode($input)
    {
        $parser = new TomlParser($input);
        $node = $parser->parse();

        return TomlNormalizer::normalize($node);
    }
}
