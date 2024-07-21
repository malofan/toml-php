<?php

namespace Devium\Toml;

require_once './vendor/autoload.php';

use Exception;

/**
 * @internal
 */
final class TomlError extends Exception
{
    public mixed $tomlLine;

    public mixed $tomlColumn;

    public string $tomlCodeBlock;

    public function __construct(string $message = '', array $options = ['toml' => '', 'ptr' => 0])
    {
        [$line, $column] = $this->getLineColFromPtr($options['toml'], $options['ptr']);
        $codeBlock = $this->makeCodeBlock($options['toml'], $line, $column);

        parent::__construct("Invalid TOML document: $message\n\n$codeBlock");

        $this->tomlLine = $line;
        $this->tomlColumn = $column;
        $this->tomlCodeBlock = $codeBlock;
    }

    protected function getLineColFromPtr($string, $pointer): array
    {
        $lines = preg_split('/\r\n|\n|\r/', TomlUtils::stringSlice($string, 0, $pointer));

        return [count($lines), strlen(array_pop($lines))];
    }

    protected function makeCodeBlock($string, $line, $column): string
    {
        $lines = preg_split('/\r\n|\n|\r/', $string);
        $codeBlock = '';

        $numberLen = ((int) log10($line + 1) | 0) + 1;

        for ($i = $line - 1; $i <= $line + 1; $i++) {
            $l = $lines[$i - 1] ?? null;
            if (! $l) {
                continue;
            }

            $codeBlock .= str_pad($i, $numberLen, ' ');
            $codeBlock .= ':  ';
            $codeBlock .= $l;
            $codeBlock .= "\n";

            if ($i === $line) {
                $codeBlock .= ' '.str_repeat(' ', $numberLen + $column + 2);
                $codeBlock .= "^\n";
            }
        }

        return $codeBlock;
    }
}
//
//$exception = new TomlError('unexpected woof!!', [
//    'toml' => 'meow meow woof meow',
//    'ptr' => strpos('meow meow woof meow', 'woof'),
//]);
//
//var_dump($exception->tomlLine);
//var_dump($exception->tomlColumn);
//echo($exception->tomlCodeBlock);
//var_dump($exception->tomlCodeBlock === "1:  meow meow woof meow\n              ^\n");
//
//$exception = new TomlError('unexpected woof!!', [
//    'toml' => "meow meow woof meow\nmeow meow meow meow",
//    'ptr' => strpos("meow meow woof meow\nmeow meow meow meow", 'woof'),
//]);
//
//var_dump($exception->tomlLine);
//var_dump($exception->tomlColumn);
//echo($exception->tomlCodeBlock);
//var_dump($exception->tomlCodeBlock === "1:  meow meow woof meow\n              ^\n2:  meow meow meow meow\n");
