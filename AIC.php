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
    private $intSaturationWeight = 30;
    private $intValueWeight      = 20;
    private $intHueWeight        = 80;
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
        // reset min distance to allowed max or
        $maxDistance   = $this->intMaxDistance;
        $closestColor  = false;
        // calculate the hex for the pixel we have to test
        $testRgba         = $this->getPixelColor($x,$y);
        // filter full alpha
        if($testRgba === AnalyzeImageColors::SKIP_ALPHA) {
            return AnalyzeImageColors::ERR_NO_HITS;
        }
        // test all registered colors for closest distance
        foreach($this->arrColors as $colorName => $colorData) {
            // reset precision 
            $precision = $this->intMaxDistance;
            // get the color diffrence bei hsv
            $currDist = $this->colorDiffByHsvDistance(
                $this->hexToRgb($colorData["hex"]),
                $testRgba
            );
            if($colorName == "braun") {
                //echo "|" . $currDist;
            }
            // if we got a specific precision gonne evaluate it
            if(isset($colorData["precision"])) {
                $precision = $colorData["precision"];
            }
            // check if the color is closer than all before
            if($currDist < $maxDistance && $currDist < $precision) {
                // seems to be, safe it for return
                $maxDistance  = $currDist;
                $closestColor = $colorName;
            }
        }
        if(false === $closestColor) {
            return AnalyzeImageColors::ERR_NO_HITS;
        }
        return [
            "name"     => $closestColor,
            "distance" => $maxDistance
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
        $oneWay   = abs(($alpha + 360 - $beta) % 360);
        $otherWay = abs(($alpha -360 - $beta) % 360);
        return  $this->intHueWeight / 180 * min($oneWay, $otherWay);
    }
    
    private function calcSaturationDistance($alpha, $beta) {
        $arr  = [$alpha,$beta];
        asort($arr);
        $arr = array_values($arr);
        return $this->intSaturationWeight / 100 * ($arr[1] - $arr[0]) ;
    }
    
    private function calcValueDistance($alpha, $beta) {
        $arr  = [$alpha,$beta];
        asort($arr);
        $arr = array_values($arr);
        return $this->intValueWeight / 360 * ($arr[1] - $arr[0]) ;
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
        return ["h" => round($h * 360), "s" => round($s * 100), "v" => round($v * 100) ];
    }
    
}
