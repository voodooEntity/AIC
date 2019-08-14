<?php

class AnalyzeImageColors {
    
    const ERR_FILEPATH  = "File not existing";
    const ERR_NO_COLORS = "No color compare array set";
    const ERR_NO_HITS   = "No fitting colors found";
    const SKIP_ALPHA    = "Full alpha transparent pixel skip";
    
    private $intMaxDistance;
    private $imageRes;
    private $imageWith;
    private $imageHeight;
    private $intSaturationWeight = 4;
    private $intValueWeight      = 3;
    private $intHueWeight        = 10;
    private $arrColors   = [];
    private $colorCounts = [];
    
    public function __construct($maxDistance = 100) {
        $this->intMaxDistance = $maxDistance;
    }
    
    public function setColors($arrColors) {
        $this->arrColors = $arrColors;
        foreach($arrColors as $key => $drop) {
            $this->colorCounts[$key] = 0;
        }
    }
    
    private function loadImage($filePath) {
        if(file_exists($filePath)) {
            $this->imageRes = imagecreatefrompng($filePath);
            return true;
        }
        return false;
    }
    
    public function process($filePath) {
        // first we load the image
        if(!$this->loadImage($filePath)) {
            return false;
        }
        // calculate image res
        $this->calcImageReso();
        // go through x and y axis to go through the full 2d grid
        for($x = 0 ; $x < $this->imageWidth ; $x++) {
            for($y = 0 ; $y < $this->imageHeight ; $y++) {
                // get the closes color to our given array of colors
                // based on the configured maxc distance
                $color = $this->findClosestColorInListByHsvDistance($x,$y);
                // if a color could be found
                if(AnalyzeImageColors::ERR_NO_HITS !== $color) {
                    $this->colorCounts[$color["name"]]++;
                }
            }
        }
        // finally return the results
        return $this->colorCounts;
    }
    
    private function findClosestColorInListByHsvDistance($x,$y) {
        $closestColor     = false;
        // calculate the hex for the pixel we have to test
        $testRgba         = $this->getPixelColor($x,$y);
        // filter full alpha
        if($testRgba === AnalyzeImageColors::SKIP_ALPHA) {
            return AnalyzeImageColors::ERR_NO_HITS;
        }
        // test all registered colors for closest distance
        foreach($this->arrColors as $colorName => $colorData) {
            // reset min distance to allowed max or
            // if we got a specific precision we use it
            $smallestDistance = $this->intMaxDistance;
            if(isset($colorData["precision"])) {
                $smallestDistance = $colorData["precision"];
            }
            // get the color diffrence bei hsv
            $currDist = $this->colorDiffByHsvDistance(
                $this->hexToRgb($colorData["hex"]),
                $testRgba
            );
            if($colorName == "braun") {
                //echo "|" . $currDist;
            }
            // check if the color is closer than all before
            if($currDist < $smallestDistance) {
                // seems to be, safe it for return
                $smallestDistance = $currDist;
                $closestColor     = $colorName;
            }
        }
        if(false === $closestColor) {
            return AnalyzeImageColors::ERR_NO_HITS;
        }
        return [
            "name"     => $closestColor,
            "distance" => $smallestDistance
        ];
    }
    
    private function colorDiffByHsvDistance($alpha,$beta) {
        // get the hsv values
        $hsvAlpha = $this->rgbToHsv($alpha["red"], $alpha["green"], $alpha["blue"]);
        $hsvBeta  = $this->rgbToHsv($beta["red"], $beta["green"], $beta["blue"]); 
        // first we calc the hue difference, the strongest factor
        $hueDistance   = $this->calcHueDistance($hsvAlpha["h"], $hsvBeta["h"]);
        // second the saturation distance 
        $satuDistance  = $this->calcSaturationDistance($hsvAlpha["s"], $hsvBeta["s"]);
        // and third and final the brightness
        $valueDistance = $this->calcValueDistance($hsvAlpha["v"], $hsvBeta["v"]);
        // finally we add up and return
        return $hueDistance + $satuDistance + $valueDistance;
    }
    
    private function calcHueDistance($alpha, $beta) {
        $arr  = [$alpha,$beta];
        asort($arr);
        $factor = $arr[1] - $arr[0];
        if($arr[0] < 10 && $arr[1] > 90) {
            $factor = 100 - $arr[1] + $arr[0];
            //echo $factor . "|";
        }
        return  $this->intHueWeight / 30 * $factor;
    }
    
    private function calcSaturationDistance($alpha, $beta) {
        $arr  = [$alpha,$beta];
        asort($arr);
        return $this->intSaturationWeight / 100 * ($arr[1] - $arr[0]) ;
    }
    
    private function calcValueDistance($alpha, $beta) {
        $arr  = [$alpha,$beta];
        asort($arr);
        return $this->intValueWeight / 100 * ($arr[1] - $arr[0]) ;
    }
    
    private function getPixelColor($x, $y) {
        $rgb    = imagecolorat($this->imageRes,$x,$y);
        $colors = imagecolorsforindex($this->imageRes, $rgb);
        if($colors["alpha"] != 127) {
            return $colors;
        }
        return AnalyzeImageColors::SKIP_ALPHA;
    }
    
    private function getPixelColorHex($x,$y) {
        $rgb    = imagecolorat($this->imageRes,$x,$y);
        $colors = imagecolorsforindex($this->imageRes, $rgb);
        if($colors["alpha"] != 127) {
            $hex    = sprintf("%02x%02x%02x", $colors["red"], $colors["green"], $colors["blue"]);
            return $hex;            
        }
        return AnalyzeImageColors::SKIP_ALPHA;
    }
    
    public function hexToRgb($hex) {
        $split = str_split($hex, 2);
        $r = hexdec($split[0]);
        $g = hexdec($split[1]);
        $b = hexdec($split[2]);
        return ["red" => $r, "green" => $g, "blue" => $b];
    }

    private function calcImageReso() {
        $this->imageHeight = imagesy($this->imageRes);
        $this->imageWidth  = imagesx($this->imageRes);
    }
    
    public function getImageReso() {
        return [
            "width"  => $this->imageWidth,
            "height" => $this->imageHeight
        ];
    }
    
    function rgbToHsv($r, $g, $b) {
        $r = $r / 255;
        $g = $g / 255;
        $b = $b / 255;
        $v = max($r, $g, $b);
        $diff  = $v - min($r, $g, $b);
        $diffc = function($c) use ($v, $diff) {
            return ($v - $c) / 6 / $diff + 1 / 2;
        };
        if($diff == 0) {
            $h = $s = 0;
        } else {
            $s  = $diff / $v;
            $rr = $diffc($r);
            $gg = $diffc($g);
            $bb = $diffc($b);
            if($r === $v) {
                $h = $bb - $gg;
            } else if($g === $v) {
                $h = (1 / 3) + $rr - $bb;
            } else if($b === $v) {
                $h = (2 / 3) + $gg - $rr;
            }
            if($h < 0) {
                $h += 1;
            } else if($h > 1) {
                $h -= 1;
            }
        }
        return ["h" => round($h * 100), "s" => round($s * 100), "v" => round($v * 100) ];
        //return ["h" => round($h * 360), "s" => round($s * 100), "v" => round($v * 100) ];
    }
    
}
$imagePath = "test.png";
$arrColors = [
    "beige" => [
        "hex"       => "d1bc8a",
        "precision" => 10
    ],
    "blau" => [
        "hex"       => "0000ff",
        "precision" => 15
    ],
    "braun" => [
        "hex"       => "783000",
        "precision" => 17
    ],
    "coral" => [
        "hex"       => "f88379",
        "precision" => 10
    ],
    "gelb" => [
        "hex"       => "ffff00",
        "precision" => 10
    ],
    "gold" => [
        "hex"       => "d4af37",
        "precision" => 8
    ],
    "grau" => [
        "hex"       => "808080",
        "precision" => 10
    ],
    "grün" => [
        "hex"       => "33ff33",
        "precision" => 15
    ],
    "lachs" => [
        "hex"       => "f07030",
        "precision" => 8
    ],
    "lila"    => [
        "hex"       => "800080",
        "precision" => 8
    ],
    "oliv"    => [
        "hex"       => "bab86c",
        "precision" => 8
    ],
    "orange"  => [
        "hex"       => "ffa500",
        "precision" => 10
    ],
    "pink"    => [
        "hex"       => "ffc0cb",
        "precision" => 9
    ],
    "rosa"    => [
        "hex"       => "ea899a",
        "precision" => 9
    ],
    "rot"     => [
        "hex"       => "ff0000",
        "precision" => 15
    ],
    "schwarz" => [
        "hex"       => "000000",
        "precision" => 7
    ],
    "silber"  => [
        "hex"       => "c0c0c0",
        "precision" => 9
    ],
    "türkis"  => [
        "hex"       => "48d1cc",
        "precision" => 7
    ],
    "weiß"    => [
        "hex"       => "ffffff",
        "precision" => 7
    ],
];


// test
$objAIC  = new AnalyzeImageColors(15);
$objAIC->setColors($arrColors);


$ret = $objAIC->process($imagePath);
var_dump($ret);
?>
