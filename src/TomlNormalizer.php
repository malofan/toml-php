<?php

namespace Devium\Toml;

use ArrayObject;
use Devium\Toml\Nodes\ArrayNode;
use Devium\Toml\Nodes\ArrayTableNode;
use Devium\Toml\Nodes\BareNode;
use Devium\Toml\Nodes\BooleanNode;
use Devium\Toml\Nodes\FloatNode;
use Devium\Toml\Nodes\InlineTableNode;
use Devium\Toml\Nodes\IntegerNode;
use Devium\Toml\Nodes\KeyNode;
use Devium\Toml\Nodes\KeyValuePairNode;
use Devium\Toml\Nodes\LocalDateNode;
use Devium\Toml\Nodes\LocalDateTimeNode;
use Devium\Toml\Nodes\LocalTimeNode;
use Devium\Toml\Nodes\OffsetDateTimeNode;
use Devium\Toml\Nodes\RootTableNode;
use Devium\Toml\Nodes\StringNode;
use Devium\Toml\Nodes\TableNode;

/**
 * @internal
 */
final class TomlNormalizer
{
    /**
     * @throws TomlError
     */
    public function normalize(Nodes\Node $node): mixed
    {
        switch ($node::class) {
            case InlineTableNode::class:
            case RootTableNode::class:
                $elements = $this->mapNormalize($node->elements());

                return $this->merge(...$elements);

            case KeyNode::class:
                return $this->mapNormalize($node->keys());

            case KeyValuePairNode::class:

                $key = $this->normalize($node->key);
                $value = $this->normalize($node->value);

                return $this->objectify($key, $value);

            case TableNode::class:
                $key = $this->normalize($node->key);
                $elements = $this->mapNormalize($node->elements());

                return $this->objectify($key, $this->merge(...$elements));

            case ArrayTableNode::class:
                $key = $this->normalize($node->key);
                $elements = $this->mapNormalize($node->elements());

                return $this->objectify($key, [$this->merge(...$elements)]);

            case ArrayNode::class:
                return $this->mapNormalize($node->elements());

            case OffsetDateTimeNode::class:
            case LocalDateTimeNode::class:
            case LocalDateNode::class:
            case LocalTimeNode::class:
            case BareNode::class:
            case StringNode::class:
            case IntegerNode::class:
            case FloatNode::class:
            case BooleanNode::class:
                return $node->value;

            default:
                throw new TomlError('unsupported type: '.$node::class);
        }
    }

    /**
     * @throws TomlError
     */
    public function mapNormalize(array $items): array
    {
        return array_map(fn ($element) => $this->normalize($element), $items);
    }

    /**
     * @throws TomlError
     */
    public function merge(...$values): ArrayObject
    {
        return array_reduce($values, function (ArrayObject $acc, $value) {
            foreach ($value as $key => $nextValue) {

                $prevValue = $acc->offsetExists($key) ? $acc->offsetGet($key) : null;

                if (is_array($prevValue) && is_array($nextValue)) {
                    $acc->{$key} = array_merge($prevValue, $nextValue);
                } elseif ($this->isKeyValuePair($prevValue) && $this->isKeyValuePair($nextValue)) {
                    $acc->{$key} = $this->merge($prevValue, $nextValue);
                } elseif (is_array($prevValue) &&
                    $this->isKeyValuePair(end($prevValue)) &&
                    $this->isKeyValuePair($nextValue)) {
                    $prevValueLastElement = end($prevValue);
                    $acc->{$key} = array_merge(
                        array_slice($prevValue, 0, -1),
                        [$this->merge($prevValueLastElement, $nextValue)]
                    );
                } elseif (isset($prevValue)) {
                    throw new TomlError;
                } else {
                    $acc->{$key} = $nextValue;
                }
            }

            return $acc;
        }, new ArrayObject([], ArrayObject::ARRAY_AS_PROPS));
    }

    public function isKeyValuePair($value): bool
    {
        if ($value instanceof AbstractTomlDateTime) {
            return false;
        }

        return is_object($value);
    }

    /**
     * @param  string[]  $keys
     */
    public function objectify(array $keys, $value): ArrayObject
    {
        $initialValue = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);
        $object = &$initialValue;
        foreach (array_slice($keys, 0, -1) as $prop) {
            $object->{$prop} = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);
            $object = &$object->{$prop};
        }

        $key = array_pop($keys);
        $object->{$key} = $value;

        return $initialValue;
    }
}
