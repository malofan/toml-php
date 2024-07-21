<?php
function isDecimal($char)
{
    return $char >= '0' && $char <= '9';
}

function isHexadecimal($char)
{
    return ($char >= 'A' && $char <= 'Z') || ($char >= 'a' && $char <= 'z') || ($char >= '0' && $char <= '9');
}

function isOctal($char)
{
    return $char >= '0' && $char <= '7';
}

function isBinary($char)
{
    return $char === '0' || $char === '1';
}
?>

