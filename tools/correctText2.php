<?php

require_once __DIR__ . '/../lib/Util.php';
$UNIT_DISTANCE = array();
$MIN_FREQUENCY = 0.5;
$IA_PREFIXES = array('alt', 'arhi', 'auto', 'bine', 'de', 'ex', 'ne', 'nemai', 'pre', 'prea', 'răs', 're', 'semi', 'sub', 'super', 'supra', 'tele');
$WORD_MAP = array(); // Map of form->number of occurrences
$MAX_DIST = 2.000; // Maximum Levenshtein distance at which we can still modify a word

if (count($argv) != 3) {
  die("Usage: {$argv[0]} input_file output_file\n");
}

precomputeDistances();
$text = file_get_contents($argv[1]);
$text = replaceMisc($text);
$text = removeHyphenation($text);
$text = removeLineBreaks($text);
$text = correctAndCollect($text);

/* foreach ($WORD_MAP as $word => $occ) { */
/*   print(sprintf("%03d %s\n", $occ, $word)); */
/* } */

$text = correctWithWordMap($text);

file_put_contents($argv[2], $text);

/*************************************************************************/

function replaceMisc($text) {
  return str_replace(array('ã', 'Ã', 'ş', 'Ş', 'ţ', 'Ţ', '~'),
		     array('ă', 'Ă', 'ș', 'Ș', 'ț', 'Ț', '-'), $text);
}

function removeHyphenation($text) {
  return preg_replace("/([a-zA-ZăâîșțĂÂÎȘȚ])(-\\n)([a-zA-ZăâîșțĂÂÎȘȚ])/", "$1$3", $text);
}

function removeLineBreaks($text) {
  return preg_replace("/([a-zA-ZăâîșțĂÂÎȘȚ])\\n([a-zA-ZăâîșțĂÂÎȘȚ])/", "$1 $2", $text);
}

function replaceIA($word) {
  global $IA_PREFIXES;

  $wlow = mb_strtolower($word);
  if ($wlow == 'sînt' || $wlow == 'sîntem' || $wlow == 'sînteți') {
    return str_replace(array('î', 'Î'), array('u', 'U'), $word);
  }

  $len = mb_strlen($word);
  for ($i = 1; $i < $len - 1; $i++) {
    $c = mb_substr($word, $i, 1);
    if (($c == 'î' || $c == 'Î') && !in_array(mb_substr($word, 0, $i), $IA_PREFIXES)) {
      $word = mb_substr($word, 0, $i) . (($c == 'î') ? 'â' : 'Â') . mb_substr($word, $i + 1);
    }
  }
  return $word;
}

function setUnitDistance($a, $b, $val) {
  global $UNIT_DISTANCE;
  $UNIT_DISTANCE[$a][$b] = $UNIT_DISTANCE[$b][$a] = $val;
}

function precomputeDistances() {
  global $UNIT_DISTANCE;
  setUnitDistance('a', 'ă', 0.3);
  setUnitDistance('a', 'â', 0.3);
  setUnitDistance('ă', 'â', 0.3);
  setUnitDistance('â', 'î', 0.1);
  setUnitDistance('i', 'î', 0.3);
  setUnitDistance('i', 'â', 0.3);
  setUnitDistance('s', 'ș', 0.3);
  setUnitDistance('t', 'ț', 0.3);
  setUnitDistance('1', 'l', 0.3);
}

function getUnitDistance($a, $b) {
  global $UNIT_DISTANCE;
  if ($a == $b) {
    return 0.0;
  }
  return array_key_exists($a, $UNIT_DISTANCE) && array_key_exists($b, $UNIT_DISTANCE[$a])
    ? $UNIT_DISTANCE[$a][$b]
    : 1.0;
}

function levenshteinDistance($s, $t) {
  $lens = mb_strlen($s);
  $lent = mb_strlen($t);
  $dist = array();
  $dist[-1][-1] = 0.0;
  for ($i = 0; $i < $lens; $i++) {
    $dist[$i][-1] = $i + 1.0;
  }
  for ($j = 0; $j < $lent; $j++) {
    $dist[-1][$j] = $j + 1.0;
  }
  for ($i = 0; $i < $lens; $i++) {
    for ($j = 0; $j < $lent; $j++) {
      $cs = mb_substr($s, $i, 1);
      $ct = mb_substr($t, $j, 1);
      $dist[$i][$j] = min($dist[$i - 1][$j - 1] + getUnitDistance($cs, $ct),
			  $dist[$i - 1][$j] + 1,
			  $dist[$i][$j - 1] + 1);
    }
  }
  return $dist[$lens - 1][$lent - 1];
}

/**
 * Performs some trivial corrections and collect the correct words in a map.
 **/
function correctAndCollect($text) {
  global $WORD_MAP;

  $words = preg_split("/([-.,;:?!'\"_ \\r\\n])/", $text, -1, PREG_SPLIT_DELIM_CAPTURE);
  $output = '';
  foreach ($words as $word) {
    $result = correctWordCommon($word);
    if ($result['correct']) {
      $slow = mb_strtolower($result['form']);
      $WORD_MAP[$slow] = 1 + (array_key_exists($slow, $WORD_MAP) ? $WORD_MAP[$slow] : 0);
    }
    $output .= $result['form'];
  }

  return $output;
}

function correctWithWordMap($text) {
  $words = preg_split("/([-.,;:?!'\"_ \\r\\n])/", $text, -1, PREG_SPLIT_DELIM_CAPTURE);
  $output = '';
  foreach ($words as $word) {
    $output .= correctWordWithWordMap($word);
  }

  return $output;
}

/**
 * Returns an array with the fields:
 * - form: the corrected form;
 * - ignore: whether the form is short and should be ignored. Future processing should not attempt to correct this form;
 * - correct: whether this form was found to be correct and can be added to the word map.
 */
function correctWordCommon($s) {
  global $MIN_FREQUENCY;
  global $WORD_MAP;

  if (mb_strlen($s) <= 2 || is_numeric($s)) {
    return array('form' => $s, 'ignore' => true, 'correct' => false);
  }
  $s = replaceIA($s);

  $query = "select i.formNoAccent from DEX.InflectedForm i, DEX.Lexem l " .
    "where lexemId = l.id and i.formNoAccent = '{$s}' and l.frequency >= $MIN_FREQUENCY";
  $matches = ORM::for_table('Variable')->raw_query($query, null)->find_many();
  if (count($matches)) {
    return array('form' => $s, 'ignore' => false, 'correct' => true);
  }

  // Search by formUtf8General and see if there is exactly one match
  $query = "select distinct i.formNoAccent from DEX.InflectedForm i, DEX.Lexem l " .
    "where lexemId = l.id and i.formUtf8General = '{$s}' and l.frequency >= $MIN_FREQUENCY";
  $matches = ORM::for_table('Variable')->raw_query($query, null)->find_many();
  if (count($matches) == 1) {
    return array('form' => $matches[0]->formNoAccent, 'ignore' => false, 'correct' => true);
  }

  return array('form' => $s, 'ignore' => false, 'correct' => false);
}

function correctWordWithWordMap($s) {
  global $WORD_MAP;
  global $MAX_DIST;

  $map = correctWordCommon($s);
  $s = $map['form'];
  if ($map['ignore'] || $map['correct']) {
    return $s;
  }

  // Search by Levenshtein distance; use occurrence count as tie breaker
  $bestDist = 100;
  $bestCount = 0;
  $bestForm = null;
  foreach ($WORD_MAP as $form => $count) {
    $dist = levenshteinDistance($s, $form);
    if (($dist < $bestDist) || ($dist == $bestDist && $count > $bestCount)) {
      $bestDist = $dist;
      $bestForm = $form;
      $bestCount = $count;
    }
  }

  if ($bestDist <= $MAX_DIST) {
    $bestForm = emulateCase($bestForm, $s);
    print("[$s] [$bestForm] dist $bestDist count: $bestCount\n");
    return $bestForm;
  }
  
  print("Unknown: [$s]\n");
  return $s;
}

function emulateCase($s, $after) {
  if (StringUtil::isUppercase($after)) {
    return mb_strtoupper($s);
  } else if (StringUtil::isCapitalized($after)) {
    return StringUtil::capitalize($s);
  } else {
    return $s;
  }
}

?>
