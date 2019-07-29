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
    
    public function colorDiff($alpha,$beta) {
        // do the math on each tuple
        // could use bitwise operates more efficiently but just do strings for now.
        // alpha
        $red1   = hexdec(substr($alpha,0,2));
        $green1 = hexdec(substr($alpha,2,2));
        $blue1  = hexdec(substr($alpha,4,2));
        // beta
        $red2   = hexdec(substr($beta,0,2));
        $green2 = hexdec(substr($beta,2,2));
        $blue2  = hexdec(substr($beta,4,2));
    
        return abs($red1 - $red2) + abs($green1 - $green2) + abs($blue1 - $blue2) ;
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
                $color = $this->findClosestColorInList($x,$y);
                // if a color could be found
                if(AnalyzeImageColors::ERR_NO_HITS !== $color) {
                    $this->colorCounts[$color["name"]]++;
                }
            }
        }
        // finally return the results
        return $this->colorCounts;
    }
    
    private function findClosestColorInList($x,$y) {
        // reset min distance to allowed max
        $smallestDistance = $this->intMaxDistance;
        $closestColor     = false;
        // calculate the hex for the pixel we have to test
        $testHex          = $this->getPixelColorHex($x,$y);
        // filter full alpha
        if($testHex === AnalyzeImageColors::SKIP_ALPHA) {
            return AnalyzeImageColors::ERR_NO_HITS;
        }
        // test all registered colors for closest distance
        foreach($this->arrColors as $colorName => $colorHex) {
            $currDist = $this->colorDiff($colorHex,$testHex);
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
        //var_dump($smallestDistance);
        return [
            "name"     => $closestColor,
            "distance" => $smallestDistance
        ];
    }
    
    private function getPixelColorHex($x,$y) {
        $rgb    = imagecolorat($this->imageRes,$x,$y);
        $colors = imagecolorsforindex($this->imageRes, $rgb);
        if($colors["alpha"] != 127) {
            $hex    = sprintf("%02x%02x%02x", $colors["red"], $colors["green"], $colors["blue"]);
            //var_dump($colors);
            //var_dump($hex);
            return $hex;            
        }
        return AnalyzeImageColors::SKIP_ALPHA;
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
    
}
$imagePath = "824304416009-06.png";
$arrColors = [
    "beige" => "d1bc8a",
    "blau" => "0000ff",
    "braun" => "783000",
    "coral" => "f88379",
    "gelb" => "ffff00",
    "gold" => "d4af37",
    "grau" => "808080",
    "grün" => "33ff33", // 006400
    "lachs" => "f07030",
    "lila" => "800080",
    "oliv" => "bab86c",
    "orange" => "ffa500",
    "pink" => "ffc0cb",
    "rosa" => "ea899a",
    "rot" => "ff0000",
    "schwarz" => "000000",
    "silber" => "c0c0c0",
    "türkis" => "48d1cc",
    "weiß" => "ffffff"
];


// test
$objAIC  = new AnalyzeImageColors(70);
$objAIC->setColors($arrColors);


$ret = $objAIC->process($imagePath);
var_dump($ret);
?>
