<?php
if(!$argv[1]) {
    die("no path given");
}
$imagePath = $argv[1];

include'../AIC.php';
$arrColors = [
    "beige" => [
        "hex"       => "d1bc8a",
        "precision" => 5
    ],
    "blau" => [
        "hex"       => "0000ff",
        "precision" => 10
    ],
    "braun" => [
        "hex"       => "A05E31",
        "precision" => 8
    ],
    "coral" => [
        "hex"       => "f88379",
        "precision" => 8
    ],
    "gelb" => [
        "hex"       => "ffff00",
        "precision" => 8
    ],
    "gold" => [
        "hex"       => "d4af37",
        "precision" => 5
    ],
    "grau" => [
        "hex"       => "808080",
        "precision" => 7
    ],
    "grün" => [
        "hex"       => "33ff33",
        "precision" => 10
    ],
    "lachs" => [
        "hex"       => "f07030",
        "precision" => 4
    ],
    "lila"    => [
        "hex"       => "800080",
        "precision" => 4
    ],
    "oliv"    => [
        "hex"       => "bab86c",
        "precision" => 4
    ],
    "orange"  => [
        "hex"       => "ffa500",
        "precision" => 4
    ],
    "pink"    => [
        "hex"       => "ffc0cb",
        "precision" => 5
    ],
    "rosa"    => [
        "hex"       => "ea899a",
        "precision" => 4
    ],
    "rot"     => [
        "hex"       => "ff0000",
        "precision" => 10
    ],
    "schwarz" => [
        "hex"       => "000000",
        "precision" => 10
    ],
    "silber"  => [
        "hex"       => "c0c0c0",
        "precision" => 4
    ],
    "türkis"  => [
        "hex"       => "48d1cc",
        "precision" => 4
    ],
    "weiß"    => [
        "hex"       => "ffffff",
        "precision" => 6
    ],
];
$objAIC  = new AnalyzeImageColors(100);
$objAIC->setColors($arrColors);
$ret = $objAIC->process($imagePath);
var_dump($ret);