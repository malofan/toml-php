<?php

use Devium\Toml\TomlInputIterator;

it('can iterate TOML string', function () {
    $toml = <<<'TOML_WRAP'
# This is a TOML document

title = "TOML Example"

[owner]
name = "Tom Preston-Werner"
dob = 1979-05-27T07:32:00-08:00

[database]
enabled = true
ports = [ 8000, 8001, 8002 ]
data = [ ["delta", "phi"], [3.14] ]
temp_targets = { cpu = 79.5, case = 72.0 }

[servers]

[servers.alpha]
ip = "10.0.0.1"
role = "frontend"

[servers.beta]
ip = "10.0.0.2"
role = "backend"
TOML_WRAP;
    $iterator = new TomlInputIterator($toml);

    $string = '';

    while (($c = $iterator->next()) !== '-1') {
        $string .= "$c";
    }

    expect($string)->toBe($toml);
});
