<?php

namespace Devium\Toml;

final class TomlKeystore
{
    private array $keys;

    private array $tables;

    private array $implicitTables;

    private array $arrayTables;

    public function __construct()
    {
        $this->keys = [];
        $this->tables = [];
        $this->implicitTables = [];
        $this->arrayTables = [];
    }

    /**
     * @throws TomlError
     */
    public function addNode(TomlToken $node): void
    {
        switch ($node->type) {
            case 'KEY_VALUE_PAIR':
                $this->addKeyValuePairNode($node);
                break;
            case 'TABLE':
                $this->addTableNode($node);
                break;
            case 'ARRAY_TABLE':
                $this->addArrayTableNode($node);
                break;
        }
    }

    /**
     * @throws TomlError
     */
    protected function addKeyValuePairNode(TomlToken $keyValuePairNode): void
    {
        $key = '';
        if (count($this->tables)) {
            $table = $this->tables[count($this->tables) - 1];
            $key .= "$table.";
        }
        $components = self::makeKeyComponents($keyValuePairNode->key);
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
            } elseif ($this->implicitTablesContains($key)) {
                throw new TomlError();
            }
        }
        $this->keysAdd($key);
    }

    public static function makeKeyComponents(TomlToken $keyNode): array
    {
        return array_map(fn (TomlToken $key) => $key->value, $keyNode->keys);
    }

    protected function keysContains($key): bool
    {
        return in_array($key, $this->keys, true);
    }

    protected function implicitTablesAdd($key): void
    {
        $this->implicitTables[] = $key;
    }

    protected function implicitTablesContains($key): bool
    {
        return in_array($key, $this->implicitTables);
    }

    protected function keysAdd($key): void
    {
        $this->keys[] = $key;
    }

    /**
     * @throws TomlError
     */
    protected function addTableNode(TomlToken $tableNode): void
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
            $components = array_filter(explode('.', substr($header, strlen(self::makeHeaderFromArrayTable($foundArrayTable)))), function ($component) {
                return $component !== '';
            });
            if (count($components) === 0) {
                throw new TomlError();
            }
            $key = "$foundArrayTable.";
        }
        for ($i = 0; $i < count($components); $i++) {
            $component = $components[$i];
            if ($i === 0) {
                $key .= $component;
            } else {
                if (str_contains($component, '.')) {
                    $key .= '.';
                }
                $key .= ".$component";
            }
            if ($this->keysContains($key)) {
                throw new TomlError();
            }
        }
        if (in_array($key, $this->arrayTables) || in_array($key, $this->tables) || $this->implicitTablesContains($key)) {
            throw new TomlError();
        }
        $this->tables[] = $key;
    }

    public static function makeHeaderFromArrayTable($arrayTable): string
    {

        $items = explode('.', $arrayTable);
        $items = array_filter($items, fn ($item) => str_starts_with($item, '['));

        return implode('.', $items);
    }

    /**
     * @throws TomlError
     */
    protected function addArrayTableNode(TomlToken $arrayTableNode): void
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
                $key = "$arrayTable".substr($header, strlen($arrayTableHeader));
                break;
            }
        }
        if ($index === 0 && array_filter($this->tables, function ($table) use ($header) {
            return str_starts_with($table, $header);
        })) {
            throw new TomlError();
        }
        if ($this->keysContains($key) || in_array($key, $this->tables)) {
            throw new TomlError();
        }
        $key .= ".[$index]";
        $this->arrayTables[] = $key;
        $this->tables[] = $key;
    }

    public static function makeKey($keyNode): string
    {
        return implode('.', self::makeKeyComponents($keyNode));
    }
}
