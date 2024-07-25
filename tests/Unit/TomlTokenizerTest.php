<?php

use Devium\Toml\TomlTokenizer;

it('can iterate TOML string', function () {
    $toml = 'title = "TOML Example"';
    $tokenizer = new TomlTokenizer($toml);

    $string = '';

    while ($c = $tokenizer->next()) {
        if ($c['type'] === 'EOF') {
            break;
        }
        $string .= $c['value'];
    }

    expect($string)->toBe('title = TOML Example');
});
