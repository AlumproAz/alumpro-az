<?php
header('Content-Type: image/png');

require_once '../vendor/autoload.php';

$text = $_GET['text'] ?? '';
$type = $_GET['type'] ?? 'C128';

if (empty($text)) {
    exit('No text provided');
}

// Generate barcode using Picqer library
$barcode = new \Picqer\Barcode\BarcodeGeneratorPNG();
$barcodeImage = $barcode->getBarcode($text, $barcode::TYPE_CODE_128);

// Output image
echo $barcodeImage;