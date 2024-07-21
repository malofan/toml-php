<?php
function isDecimal($char) {
    return '0' <= $char && $char <= '9';
}

function isHexadecimal($char) {
    return (('A' <= $char && $char <= 'Z') || ('a' <= $char && $char <= 'z') || ('0' <= $char && $char <= '9'));
}

function isOctal($char) {
    return '0' <= $char && $char <= '7';
}

function isBinary($char) {
    return $char === '0' || $char === '1';
}
?>

