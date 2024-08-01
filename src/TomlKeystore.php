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
    private array $keys = []; // set

    private array $tables = []; // list

    private array $arrayTables = []; // list

    private array $implicitTables = []; // set

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

        $components = $this->makeKeyComponents($node->key);

        for ($i = 0; $i < count($components); $i++) {
            $component = $components[$i];

            $key .= ($i ? '.' : '').$component;

            if ($this->keysContains($key) || $this->tablesContains($key) || $this->tablesContainsZeroIndex($key)) {
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

    /**
     * @throws TomlError
     */
    protected function addTableNode(TableNode $tableNode): void
    {
        $components = $this->makeKeyComponents($tableNode->key);
        $header = $this->makeKey($tableNode->key);
        $arrayTable = array_reverse($this->arrayTables);
        $foundArrayTable = null;

        foreach ($arrayTable as $arrayTableItem) {
            if (str_starts_with($header, $this->makeHeaderFromArrayTable($arrayTableItem))) {
                $foundArrayTable = $arrayTableItem;

                break;
            }
        }

        $key = '';

        if ($foundArrayTable !== null) {
            $foundArrayTableHeader = $this->makeHeaderFromArrayTable($foundArrayTable);

            $components = array_filter(
                $this->unescapedExplode('.', substr($header, strlen($foundArrayTableHeader))),
                static fn (string $component) => $component !== ''
            );

            if (! $components) {
                throw new TomlError();
            }

            $key = "$foundArrayTable.";
        }

        $i = 0;
        foreach ($components as $component) {

            $component = str_replace('.', '\.', $component);

            $key .= ($i ? '.' : '').$component;

            $i++;

            if ($this->keysContains($key)) {
                throw new TomlError();
            }
        }

        if ($this->arrayTablesContains($key) || $this->tablesContains($key) || $this->implicitTablesContains($key)) {
            throw new TomlError();
        }

        $this->tables[] = $key;
    }

    /**
     * @throws TomlError
     */
    protected function addArrayTableNode(ArrayTableNode $arrayTableNode): void
    {
        $header = $this->makeKey($arrayTableNode->key);

        if (
            $this->keysContains($header) || $this->tablesContains($header) || $this->implicitTablesContains($header)
        ) {
            throw new TomlError();
        }

        $key = $header;
        $index = 0;

        for ($i = count($this->arrayTables) - 1; $i >= 0; $i--) {
            $arrayTable = $this->arrayTables[$i];
            $arrayTableHeader = $this->makeHeaderFromArrayTable($arrayTable);

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

        if ($this->keysContains($key) || $this->tablesContains($key)) {
            throw new TomlError();
        }

        $key .= ".[$index]";
        $this->arrayTables[] = $key;
        $this->tables[] = $key;
    }

    protected function keysContains(string $key): bool
    {
        return isset($this->keys[$key]);
    }

    protected function tablesContains(string $key): bool
    {
        return in_array($key, $this->tables);
    }

    protected function tablesContainsZeroIndex(string $key): bool
    {
        return in_array("$key.[0]", $this->tables);
    }

    protected function arrayTablesContains(string $key): bool
    {
        return in_array($key, $this->arrayTables);
    }

    protected function implicitTablesContains(string $key): bool
    {
        return isset($this->implicitTables[$key]);
    }

    protected function implicitTablesAdd(string $key): void
    {
        $this->implicitTables[$key] = true;
    }

    protected function keysAdd(string $key): void
    {
        $this->keys[$key] = true;
    }

    protected function makeKey(KeyNode $keyNode): string
    {
        return implode('.', $this->makeKeyComponents($keyNode));
    }

    protected function makeKeyComponents(KeyNode $keyNode): array
    {
        return array_map(fn (BareNode|StringNode $key) => $key->value, $keyNode->keys());
    }

    protected function makeHeaderFromArrayTable(string $arrayTable): string
    {
        return implode(
            '.',
            array_filter(
                $this->unescapedExplode('.', $arrayTable),
                fn ($item) => ! str_starts_with($item, '[')
            )
        );
    }

    protected function unescapedExplode(string $character, string $value): array
    {
        return array_map(
            fn ($item) => str_replace('~!~!~', $character, $item),
            explode($character, str_replace('\\'.$character, '~!~!~', $value))
        );
    }
}
