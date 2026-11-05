<?php

$im = imagecreatefrompng(__DIR__.'/../storage/app/public/branding/hiramoni-logo.png');
$w = imagesx($im);
$h = imagesy($im);
$pts = [[0, 0], [$w - 1, 0], [0, $h - 1], [$w - 1, $h - 1], [(int) ($w / 2), (int) ($h / 2)], [8, 8], [40, 40]];
foreach ($pts as [$x, $y]) {
    $rgba = imagecolorat($im, $x, $y);
    $a = ($rgba >> 24) & 0x7F;
    $r = ($rgba >> 16) & 0xFF;
    $g = ($rgba >> 8) & 0xFF;
    $b = $rgba & 0xFF;
    echo "($x,$y) r=$r g=$g b=$b a=$a\n";
}
echo "size={$w}x{$h}\n";
