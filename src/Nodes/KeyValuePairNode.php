<?php

namespace Devium\Toml\Nodes;

final readonly class KeyValuePairNode implements Node
{
    public function __construct(
        public KeyNode $key,
        public StringNode|IntegerNode|FloatNode|BooleanNode|OffsetDateTimeNode|LocalDateTimeNode|LocalDateNode|LocalTimeNode|ArrayNode|InlineTableNode $value
    ) {}
}
