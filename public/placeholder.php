<?php
/**
 * Dynamic Placeholder Image Generator
 * Generates a colored placeholder with store initial
 */

$text = $_GET['text'] ?? '?';
$size = intval($_GET['size'] ?? 400);
$bg = $_GET['bg'] ?? 'random';

// Limit size for security
$size = min(max($size, 100), 800);

// Generate random color based on text
if ($bg === 'random') {
    $hash = md5($text);
    $r = hexdec(substr($hash, 0, 2));
    $g = hexdec(substr($hash, 2, 2));
    $b = hexdec(substr($hash, 4, 2));
} else {
    // Use provided color
    $r = 102;
    $g = 126;
    $b = 234;
}

// Create image
$image = imagecreatetruecolor($size, $size);

// Allocate colors
$bgColor = imagecolorallocate($image, $r, $g, $b);
$textColor = imagecolorallocate($image, 255, 255, 255);

// Fill background
imagefilledrectangle($image, 0, 0, $size, $size, $bgColor);

// Add text
$fontSize = $size / 3;
$font = 5; // Built-in font

// Get first letter
$letter = strtoupper(substr($text, 0, 1));

// Calculate text position (center)
$textWidth = imagefontwidth($font) * strlen($letter);
$textHeight = imagefontheight($font);
$x = ($size - $textWidth) / 2;
$y = ($size - $textHeight) / 2;

// Draw text
imagestring($image, $font, $x, $y, $letter, $textColor);

// Output image
header('Content-Type: image/png');
header('Cache-Control: max-age=86400'); // Cache for 1 day
imagepng($image);
imagedestroy($image);
?>
