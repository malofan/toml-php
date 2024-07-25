<?php

namespace Devium\Toml;

class TomlDecoder
{
    public function decode($input)
    {
        $parser = new TomlParser($input);
        $node = $parser->parse();

        var_dump(json_encode(['after parse' => $node], JSON_PRETTY_PRINT));
        var_dump(json_encode(['after normalized' => TomlNormalizer::normalize($node)], JSON_PRETTY_PRINT));

        return TomlNormalizer::normalize($node);
    }
}
