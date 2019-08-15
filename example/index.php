<?php
set_time_limit(0);
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

?>
<html>
    <head>
        <title>Analyze Image Colors - example/test page</title>
    </head>
    <body>
<?php
if(isset($_GET["image"]) && file_exists("images/" . $_GET["image"])) {
    $imagePath = "images/" . $_GET["image"];
?>
        <h1>ImageAnalyzeColors Testscript</h1>
	    <h2>Given Image (<?php echo $imagePath;?>)</h2>
		<img src="<?php echo $imagePath;?>" style="height:600px;">
		<h2>Color Table of identified colors and upcome count. </h2>
		<ul>
<?php
    $ret = analyzeImage($arrColors,$imagePath);
    foreach($ret as $color => $count) {
        echo "		    <li>Color: " . $color . " | Count:" . $count . " | Example color <span style='width:16p;height:16px;display:inline-block;background-color:#" . $arrColors[$color] . "'>&nbsp;</span></li>\n";
    }
}
?>
        </ul>
        <div style="margin-top:50px;border-top:2px solid #000;overflow-x:scroll;">
<?php
$arrImages = scandir("images/");
foreach($arrImages as $image) {
    if(!in_array($image,[".",".."])) {
?>
            <a href="?image=<?php echo $image;?>" style="display:inline-block;margin-right:30px;"><img src="images/<?php echo $image;?>" width="200" ></a>
<?php
    }
}

?>
        </div>
    </body>
</html>
<?php
function analyzeImage($arrColors,$imagePath) {
    $objAIC  = new AnalyzeImageColors(100);
    $objAIC->setColors($arrColors);
    return $objAIC->process($imagePath);
}
