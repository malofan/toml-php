<?php

namespace Devium\Toml;

use Devium\Toml\Nodes\ArrayTableNode;
use Devium\Toml\Nodes\BareNode;
use Devium\Toml\Nodes\KeyNode;
use Devium\Toml\Nodes\KeyValuePairNode;
use Devium\Toml\Nodes\StringNode;
use Devium\Toml\Nodes\TableNode;

final class TomlKeystore
{
    /**
     * @var array<string, true>
     */
    private array $keys = [];

    /**
     * @var string[]
     */
    private array $tables = [];

    /**
     * @var array<string, true>
     */
    private array $implicitTables = [];

    /**
     * @var string[]
     */
    private array $arrayTables = [];

    /**
     * <code>
     * [[fruits]]
     * name = "apple"
     *
     * [fruits.physical]  # subtable
     * color = "red"
     * shape = "round"
     *
     * [[fruits.varieties]]  # nested array of tables
     * name = "red delicious"
     *
     * [[fruits.varieties]]
     * name = "granny smith"
     *
     *
     * [[fruits]]
     * name = "banana"
     *
     * [[fruits.varieties]]
     * name = "plantain"
     * </code>
     * <code>
     *     {
     *         "fruits": [
     *             {
     *                 "name": "apple",
     *                 "physical": {
     *                     "color": "red",
     *                     "shape": "round"
     *                 },
     *                 "varieties": [
     *                     { "name": "red delicious" },
     *                     { "name": "granny smith" }
     *                 ]
     *             },
     *             {
     *                 "name": "banana",
     *                 "varieties": [
     *                     { "name": "plantain" }
     *                 ]
     *             }
     *         ]
     *     }
     * </code>
     * <code>
     * $this->hierarchyCounter = [
     *  'fruits' => 2,
     *  'fruits.[0].varieties' => 2,
     *  'fruits.[1].varieties' => 1,
     * ];
     * </code>
     *
     * @var array<string, int>
     */
    private array $hierarchyCounter = [];

    /**
     * @throws TomlError
     */
    public function addNode(KeyValuePairNode|TableNode|ArrayTableNode $node): void
    {
        switch ($node::class) {
            case KeyValuePairNode::class:
                $this->addKeyValuePairNode($node);
                break;
            case TableNode::class:
                $this->addTableNode($node);
                break;
            case ArrayTableNode::class:
                $this->addArrayTableNode($node);
                break;
            default:
                throw new TomlError();
        }
    }

    /**
     * @throws TomlError
     */
    protected function addKeyValuePairNode(KeyValuePairNode $node): void
    {
        $key = '';

        if (isset($this->tables[count($this->tables) - 1])) {
            $table = $this->tables[count($this->tables) - 1];
            $key = "$table.";
        }

        $components = self::makeKeyComponents($node->key);

        for ($i = 0; $i < count($components); $i++) {
            $component = $components[$i];

            if ($i === 0) {
                $key .= $component;
            } else {
                $key .= ".$component";
            }

            if ($this->keysContains($key) || in_array($key, $this->tables)) {
                throw new TomlError();
            }

            if (count($components) > 1 && $i < count($components) - 1) {
                $this->implicitTablesAdd($key);

                continue;
            }

            if ($this->implicitTablesContains($key)) {
                throw new TomlError();
            }
        }

        $this->keysAdd($key);
    }

    public static function makeKeyComponents(KeyNode $keyNode): array
    {
        return array_map(fn (BareNode|StringNode $key) => $key->value, $keyNode->keys());
    }

    protected function keysContains(string $key): bool
    {
        return isset($this->keys[$key]);
    }

    protected function implicitTablesAdd(string $key): void
    {
        $this->implicitTables[$key] = true;
    }

    protected function implicitTablesContains(string $key): bool
    {
        return isset($this->implicitTables[$key]);
    }

    protected function keysAdd(string $key): void
    {
        $this->keys[$key] = true;
    }

    /**
     * @throws TomlError
     */
    protected function addTableNode(TableNode $tableNode): void
    {
        $components = self::makeKeyComponents($tableNode->key);
        $header = implode('.', $components);
        $arrayTable = array_reverse($this->arrayTables);
        $foundArrayTable = null;

        foreach ($arrayTable as $arrayTableItem) {
            if (str_starts_with($header, self::makeHeaderFromArrayTable($arrayTableItem))) {
                $foundArrayTable = $arrayTableItem;

                break;
            }
        }

        $key = '';

        if ($foundArrayTable !== null) {
            $foundArrayTableHeader = self::makeHeaderFromArrayTable($foundArrayTable);

            $components = array_filter(
                explode('.', substr($header, strlen($foundArrayTableHeader))),
                static fn (string $component) => $component !== ''
            );

            if (empty($components)) {
                throw new TomlError();
            }

            $key = "$foundArrayTable.";
        }

        $i = 0;
        foreach ($components as $component) {

            if ($i === 0) {
                $key .= $component;
            } else {
                $key .= ".$component";
            }

            $i++;

            if ($this->keysContains($key)) {
                throw new TomlError();
            }
        }

        if (in_array($key, $this->arrayTables) || in_array($key, $this->tables) || $this->implicitTablesContains($key)) {
            throw new TomlError();
        }

        $this->tables[] = $key;
    }

    public static function makeHeaderFromArrayTable(string $arrayTable): string
    {
        $items = explode('.', $arrayTable);
        $items = array_filter($items, fn ($item) => ! str_starts_with($item, '['));

        return implode('.', $items);
    }

    /**
     * @throws TomlError
     */
    protected function addArrayTableNode(ArrayTableNode $arrayTableNode): void
    {
        $header = self::makeKey($arrayTableNode->key);

        if ($this->keysContains($header)) {
            throw new TomlError();
        }

        if (in_array($header, $this->tables) || $this->implicitTablesContains($header)) {
            throw new TomlError();
        }

        $key = $header;
        $index = 0;

        for ($i = count($this->arrayTables) - 1; $i >= 0; $i--) {
            $arrayTable = $this->arrayTables[$i];
            $arrayTableHeader = self::makeHeaderFromArrayTable($arrayTable);

            if ($arrayTableHeader === $header) {
                $index++;

                continue;
            }

            if (str_starts_with($header, $arrayTableHeader)) {
                $key = $arrayTable.substr($header, strlen($arrayTableHeader));

                break;
            }
        }

        if ($index === 0 && ! empty(array_filter($this->tables, fn ($table) => str_starts_with($table, $header)))) {
            throw new TomlError();
        }

        if ($this->keysContains($key) || in_array($key, $this->tables)) {
            throw new TomlError();
        }

        $key .= ".[$index]";
        $this->arrayTables[] = $key;
        $this->tables[] = $key;
    }

    public static function makeKey(KeyNode $keyNode): string
    {
        return implode('.', self::makeKeyComponents($keyNode));
    }
}
