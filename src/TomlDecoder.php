<?php

namespace Devium\Toml;

class TomlDecoder
{
    /**
     * @throws TomlError
     */
    public function decode($input)
    {
        $parser = new TomlParser($input);
        $node = $parser->parse();

        return TomlNormalizer::normalize($node);
    }
}
