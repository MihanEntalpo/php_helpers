# ImageHash is a class, that creates and compares short image hashes

It could be used to *fastly* find almost duplicate images (with changed size, or file format)

*Requirements:*

1. Php 5.3

2. GD2 library

*Usage:*

```php

require_once("./ImageHash.php");

//Create new hasher instance:
$ih = new ImageHash();

//Calculate hash from image, stored in file:
$hash1 = $ih->createHashFromFile("./file1.jpeg");

//Calculate hash from image, which content is loaded into a var:
$content = file_get_contents("./file2.png");
$hash2 = $ih->createHashFromFileContents($content);

//Calculate hash from image, created (or loaded) by GD
$image = imagecreatefromjpeg("./file3.jpeg");
$hash3 = $ih->createHash($image);

//Compare hashes to exact equalaity:
echo ($hash1 == $hash2) ? "Hashes are equal!" : "Hashes are not equal";

//Comapre hashes to approximate equality:
echo $ih->compareImageHashes($hash2, $hash3, 0.05) ? "Hashes are equal up to 5%" : "Hashes are not equal, even with 5% threshold";

//Create longer hash, 225 bytes long (15*15), with maximum color detalization, and don't encode it to base64:
$longer_hash = $ih->createHash($image, 15, 6, false)

//Create smaller hash, 16 bytes long (4*4) with minimum color detalization, and encode it into base64:
$short_hash = $ih->createHash($image, 4, 2, true);

