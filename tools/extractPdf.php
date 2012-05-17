<?php

require_once __DIR__ . '/../lib/Util.php';

define('TMP_FILE', '/tmp/extractPdf.html');

// Where the second line in a paragraph is placed in relation to the first line
define('PARA_DX_MIN', 15);
define('PARA_DX_MAX', 25);
define('PARA_DY_MIN', 15);
define('PARA_DY_MAX', 20);

$previousLeft = null; // For detecting text on the same line
$previousTop = null;

if (count($argv) != 2) {
  die("Usage: {$argv[0]} input_file\n");
}

$pdfFileName = realpath($argv[1]);

exec(sprintf("pdftohtml -i -noframes -s %s %s", $argv[1], TMP_FILE));
$html = file_get_contents(TMP_FILE);
$html = preprocessHtml($html);
$xml = simplexml_load_string($html);
//$html = simplexml_load_file("/home/cata/Desktop/text.xml");

traverse($xml->BODY);
//traverse($html->tag1, false, false);
//var_dump((string)$html->tag1->tag4);

/**************************************************************************/

function preprocessHtml($html) {
  $weirdEncoding = strpos($html, 'þ') !== false;
  if ($weirdEncoding) {
    $html = str_replace(array('ã', 'Ã', 'º', 'ª', 'þ', 'Þ', 'Ñ', '”', 'Ò', '&#160;'),
                        array('ă', 'Ă', 'ș', 'Ș', 'ț', 'Ț', '-', '„', '”', ' '), $html);
  }
  $html = preg_replace('/<\\/?b>/i', "'''", $html);
  $html = preg_replace('/<\\/?i>/i', "''", $html);
  $html = preg_replace('/<br\\/>/i', ' ', $html);
  $html = preg_replace('/<A name="outline">(.|\\n)+<hr>/', '', $html); // Remove the outline because it is malformed
  return $html;
}

function traverse($node) {
  global $previousLeft, $previousTop;

  $name = strtolower($node->getName());
  $styleMap = parseStyle($node['style']);
  $contents = trim((string)$node);

  $left = getOrNull($styleMap, 'left');
  $top = getOrNull($styleMap, 'top');

  // Print a ' ' for paragraphs or same-line text, \n otherwise
  if (($top == $previousTop) ||
      ($top - $previousTop >= PARA_DY_MIN && $top - $previousTop <= PARA_DY_MAX &&
       $previousLeft - $left >= PARA_DX_MIN && $previousLeft - $left <= PARA_DX_MAX)) {
    print ' ';
  } else {
    print "\n\n";
  }

  if (!skippableContents($contents)) {
    print "$contents";
  }
  $previousLeft = $left;
  $previousTop = $top;
  foreach ($node->children() as $child) {
    traverse($child);
  }
}

/* Returns a map of key => value pairs. Splits the 'px' prefix on distances. */
function parseStyle($style) {
  $map = array();
  if ($style) {
    $parts = explode(';', $style);
    foreach ($parts as $part) {
      $part = trim($part);
      if ($part) {
        list($key, $value) = explode(':', $part);
        if (StringUtil::endsWith($value, 'px')) {
          $value = trim(substr($value, 0, strlen($value) - 2));
        }
        $map[$key] = $value;
      }
    }
  }
  return $map;
}

function getOrNull($map, $key) {
  return array_key_exists($key, $map) ? $map[$key] : null;
}

function skippableLine($line)

?>
