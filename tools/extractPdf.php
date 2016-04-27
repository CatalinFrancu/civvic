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

exec(sprintf("pdftohtml -nodrm -i -noframes -s %s %s", $argv[1], TMP_FILE));
$html = file_get_contents(TMP_FILE);
$html = preprocessHtml($html);
$xml = simplexml_load_string($html);
$text = traverse($xml->BODY);
$text = postprocess($text);
print($text);

/**************************************************************************/

function preprocessHtml($html) {
  $weirdEncoding = strpos($html, 'þ') !== false;
  if ($weirdEncoding) {
    $html = str_replace(array('ã', 'Ã', 'º', 'ª', 'þ', 'Þ', 'Ñ', '”', 'Ò'),
                        array('ă', 'Ă', 'ș', 'Ș', 'ț', 'Ț', '-', '„', '”'), $html);
  }
  $html = str_replace(array('—', '&#160;'),
                      array('-', ' '), $html);
  $html = preg_replace('/<\\/?b>/i', "'''", $html);
  $html = preg_replace('/<\\/?i>/i', "''", $html);
  $html = preg_replace('/<br\\/>/i', ' ', $html);
  $html = preg_replace('/<A name="outline">(.|\\n)+<hr>/', '', $html); // Remove the outline because it is malformed
  $html = preg_replace("/<P[^>]+>MONITORUL OFICIAL AL ROMÂNIEI, PARTEA I, Nr\\. \\d+\\/\\d+\\.[IVX]+\\.\\d{4}<\\/P>\\s+" .
                       "<P[^>]+>\\d+<\\/P>\\s+/", '', $html);

  // Replace spaced-out titles
  $matches = array();
  preg_match_all("/((([A-Z ]|Ă|Â|Î|Ș|Ț) ){3,}([A-Z ]|Ă|Â|Î|Ș|Ț))/", $html, $matches, PREG_OFFSET_CAPTURE);
  foreach (array_reverse($matches[0]) as $match) {
    $text = $match[0];
    $len = mb_strlen($text);
    $pos = $match[1];

    $result = '';
    for ($i = 0; $i < $len; $i++) {
      if ($i % 2 == 0) {
        $result .= mb_substr($text, $i, 1);
      }
    }
    $html = substr($html, 0, $pos) . $result . substr($html, $pos + strlen($text));
  }
  return $html;
}

function traverse($node) {
  $result = '';
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
    $result .= ' ';
  } else {
    $result .= "\n\n";
  }

  if (!skippableLine($contents)) {
    $result .= "$contents";
  }
  $previousLeft = $left;
  $previousTop = $top;

  $children = array();
  // Collect the children
  foreach ($node->children() as $child) {
    $children[] = $child;
  }

  foreach ($children as $child) {
      $result .= traverse($child);
  }
  return $result;
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

function skippableLine($line) {
  if (!$line) {
    return true;
  }

  if (stripos("produs electronic destinat exclusiv informării gratuite a persoanelor fizice " .
              "asupra actelor ce se publică în monitorul oficial al româniei", $line) !== false) {
    return true;
  }

  return false;
}

function postprocess($text) {
  // Remove extra newlines
  $text = preg_replace("/\\n{3,}/", "\n\n", $text);

  // Extract the number and date from the technical box at the end.
  $match = array();
  $matchFound = preg_match("/Monitorul Oficial al României, Partea I, nr. (?P<number>[^\\/]+)\\/" .
                           "(?P<day>\\d+)\\.(?P<month>[IVX]+)\\.(?P<year>\\d{4}) conține \\d+ pagini\\./",
                           $text, $match);
  $headerTemplate = "__FORCETOC__\n" .
    "[[Category:Monitorul Oficial|*%s %04s]]\n" .
    "\n" .
    "= Monitorul Oficial al României =\n" .
    "Anul %s, Nr. [[issue::%04s]] - Partea I - %s, %s %s [[year::%s]]\n";

  if ($matchFound) {
    $month = 1 + array_search(strtolower($match['month']), StringUtil::$monthsRoman);
    $monthName = StringUtil::$months[$month - 1];
    $timestamp = mktime(0, 0, 0, $month, $match['day'], $match['year']);
    $dow = StringUtil::$daysOfWeek[date('w', $timestamp)];
    $header = sprintf($headerTemplate,
                      $match['year'], $match['number'], StringUtil::arabicToRoman($match['year'] - 1988),
                      $match['number'], StringUtil::capitalize($dow), $match['day'], $monthName, $match['year']
                      );
  } else {
    $header = vsprintf($headerTemplate, array_fill(0, 8, '??'));
  }

  // Remove the summary and prepend the header
  $sumStart = strpos($text, "\n'''SUMAR'''\n");
  $actStart = strpos($text, "\n'''", $sumStart + 10);
  if ($sumStart !== false && $actStart !== false) {
    // $text = substr($text, $actStart); // Breaks for P2 of M.O. 201/2010
  }
  $text = $header . $text;

  // Convert some known section titles
  $text = str_replace("\n'''ACTE ALE COMISIEI DE SUPRAVEGHERE AASIGURĂRILOR'''\n",
                      "\n== Acte ale Comisiei de Supraveghere a Asigurărilor ==\n", $text);
  $text = str_replace("\n'''ACTE ALE ORGANELOR DE SPECIALITATE'''\n\n'''ALE ADMINISTRAȚIEI PUBLICE CENTRALE'''\n",
                      "\n== Acte ale organelor de specialitate ale administrației publice centrale ==\n", $text);
  $text = str_replace("\n'''DECIZII ALE CURȚII CONSTITUȚIONALE'''\n",
                      "\n== Decizii ale Curții Constituționale ==\n", $text);
  $text = str_replace("\n'''LEGI ȘI DECRETE'''\n",
                      "\n== Legi și decrete ==\n", $text);
  $text = str_replace("\n'''ORDONANȚE ALE GUVERNULUI ROMÂNIEI'''\n",
                      "\n== Ordonanțe ale Guvernului României ==\n", $text);

  return $text;
}

?>
