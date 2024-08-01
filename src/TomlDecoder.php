<?php

namespace Devium\Toml;

use ArrayObject;
use stdClass;

readonly class TomlDecoder
{
    /**
     * @throws TomlError
     */
    public function decode(string $input): array|stdClass
    {
        $parser = new TomlParser($input);
        $normalizer = new TomlNormalizer;

        return $this->arrayObjectToStdClass($normalizer->normalize($parser->parse()));
    }

    protected function arrayObjectToStdClass(iterable $arrayObject): array|stdClass
    {
        $return = [];

        foreach ($arrayObject as $key => $value) {
            $return[$key] = $value instanceof ArrayObject || is_array($value) ? $this->arrayObjectToStdClass($value) : $value;
        }

        return is_array($arrayObject) ? $return : (object) $return;
    }
}
