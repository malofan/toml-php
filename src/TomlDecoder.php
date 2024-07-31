<?php

namespace Devium\Toml;

use ArrayObject;
use stdClass;

class TomlDecoder
{
    /**
     * @throws TomlError
     */
    public function decode($input): array|stdClass
    {
        $parser = new TomlParser($input);
        $node = $parser->parse();

        $normalized = TomlNormalizer::normalize($node);

        return $this->arrayObjectToStdClass($normalized);
    }

    protected function arrayObjectToStdClass(iterable $arrayObject): array|stdClass
    {
        $return = [];

        foreach ($arrayObject as $key => $value) {
            if ($value instanceof ArrayObject || is_array($value)) {
                $return[$key] = $this->arrayObjectToStdClass($value);
            } else {
                $return[$key] = $value;
            }
        }

        return is_array($arrayObject) ? $return : (object) $return;

    }
}
