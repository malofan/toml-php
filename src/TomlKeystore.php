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
    private array $keys = [];

    /**
     * @var string[]
     */
    private array $tables = [];

    private array $implicitTables = [];

    /**
     * @var string[]
     */
    private array $arrayTables = [];

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
        return array_map(fn (BareNode|StringNode $key) => str_replace('.', '\.', $key->value), $keyNode->keys());
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
                self::unescapedExplode('.', substr($header, strlen($foundArrayTableHeader))),
                static fn (string $component) => $component !== ''
            );

            if (empty($components)) {
                throw new TomlError();
            }

            $key = "$foundArrayTable.";
        }

        $i = 0;
        foreach ($components as $component) {

            if (str_contains($component, '.')) {
                $component = str_replace('.', '\.', $component);
            }

            if ($i === 0) {
                $key .= $component;
            } else {

                $key .= ".{$component}";
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
        $items = self::unescapedExplode('.', $arrayTable);
        $items = array_filter($items, fn ($item) => ! str_starts_with($item, '['));

        return implode('.', $items);
    }

    /**
     * @throws TomlError
     */
    protected function addArrayTableNode(ArrayTableNode $arrayTableNode): void
    {
        $header = $this->makeTableKeyPrefix($arrayTableNode->key);

        if ($this->keysContains($header)) {
            throw new TomlError();
        }

        if (in_array($header, $this->tables) || $this->implicitTablesContains($header)) {
            throw new TomlError();
        }

        $key = $header;
        $index = 0;

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

    private function makeTableKeyPrefix(KeyNode $keyNode): string
    {
        $components = self::makeKeyComponents($keyNode);

        $headerParts = [];
        for ($c = 0; $c < count($components); $c++) {
            $component = $components[$c];
            $headerParts[] = $component;

            $header = implode('.', $headerParts);

            if (! isset($components[$c + 1])) {
                return $header;
            }

            if (! in_array($header, $this->tables)) {
                $this->tables[] = $header;
            }

            $i = 0;
            if (! in_array($header . ".[$i]", $this->arrayTables)) {
                continue;
            }

            while (in_array($header . ".[" . ($i+1) . "]", $this->arrayTables)) {
                $i++;
            }

            $headerParts[] = "[$i]";
        }

        return implode('.', $headerParts);
    }

    protected static function unescapedExplode(string $character, string $value): array
    {
        return array_map(
            fn ($item) => str_replace('~!~!~', $character, $item),
            explode($character, str_replace('\\'.$character, '~!~!~', $value))
        );
    }
}
