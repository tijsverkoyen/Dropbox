<?php
// http://board.gulli.com/thread/417587-php-get-verschluesseln/
function x0rcrypt($text, $xorkey) {
    if (strlen($xorkey) == 0) {
        return;
    }
    $ergebnis = '';
    $i = 0;
    while ($i < strlen($text)) {
        for ($j=0; $j < strlen($xorkey); $j++) {
            if ($i >= strlen($text)) {
                break;
            }
            // Text XOR Schuessel
            $ergebnis .= $text{$i} ^ $xorkey{$j};
            $i++;
        }
    }
    return($ergebnis);
}

// Hex2Bin
function hex2bin($string) {
    return pack('H*', $string);
}

// crypt, return in hex
function x0rencrypt($text, $xorkey) {
    return bin2hex(x0rcrypt($text, $xorkey));
}

// decrypt, input in hex
function x0rdecrypt($text, $xorkey) {
    return x0rcrypt(hex2bin($text), $xorkey);
}
?>
