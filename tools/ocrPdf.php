<?php

require_once __DIR__ . '/../lib/Util.php';

define('TMPDIR', '/tmp/civvic-ocr');

if (count($argv) != 3) {
  die("Usage: {$argv[0]} input_file output_file\n");
}

$pdfFileName = realpath($argv[1]);
touch($argv[2]);
$outputFileName = realpath($argv[2]);

// Convert the pdf to ppm
@mkdir(TMPDIR);
chdir(TMPDIR);
print("Converting PDF to PPM...\n");
exec("pdftoppm -r 300 $pdfFileName pp");

// Run tesseract on every page
foreach (scandir(TMPDIR) as $ppmFile) {
  if (preg_match("/pp-\\d+\\.ppm/", $ppmFile)) {
    $base = basename($ppmFile, '.ppm');
    printf("OCRing $ppmFile to {$base}.txt...\n");
    exec("tesseract $ppmFile $base -l ron");
  }
}

$output = "";
foreach (scandir(TMPDIR) as $txtFile) {
  if (preg_match("/pp-\\d+\\.txt/", $txtFile)) {
    $output .= file_get_contents($txtFile);
  }
}

file_put_contents($outputFileName, $output);
exec("rm -rf " . TMPDIR);

?>
