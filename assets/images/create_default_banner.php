<?php
// Set the content type header to image/png
header('Content-Type: image/png');

// Create a blank image with dimensions 1200x500
$width = 1200;
$height = 500;
$image = imagecreatetruecolor($width, $height);

// Set background color (light gray)
$bg_color = imagecolorallocate($image, 240, 240, 240);
imagefill($image, 0, 0, $bg_color);

// Set text color (dark gray)
$text_color = imagecolorallocate($image, 100, 100, 100);

// Add text to the image
$text = 'Default Banner - Upload Your Own';
$font_size = 5;
$text_width = imagefontwidth($font_size) * strlen($text);
$text_x = ($width - $text_width) / 2;
$text_y = $height / 2 - 10;

imagestring($image, $font_size, $text_x, $text_y, $text, $text_color);

// Save the image
imagepng($image, __DIR__ . '/default-banner.png');

// Free up memory
imagedestroy($image);

echo "Default banner image created successfully at: " . __DIR__ . "/default-banner.png\n";
?>
