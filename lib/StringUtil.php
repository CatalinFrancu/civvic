<?php

class StringUtil {
  static $months = array('ianuarie', 'februarie', 'martie', 'aprilie', 'mai', 'iunie',
                         'iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie');
  static $monthsRoman = array('i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix', 'x', 'xi', 'xii');
  // Useful because Ubuntu + PHP + locales = idiot party
  static $daysOfWeek = array('duminică', 'luni', 'marți', 'miercuri', 'joi', 'vineri', 'sâmbătă');

  static function startsWith($string, $substring) {
    $startString = substr($string, 0, strlen($substring));
    return $startString == $substring;
  }

  static function endsWith($string, $substring) {
    $lenString = strlen($string);
    $lenSubstring = strlen($substring);
    $endString = substr($string, $lenString - $lenSubstring, $lenSubstring);
    return $endString == $substring;
  }

  static function randomCapitalLetters($length) {
    $result = '';
    for ($i = 0; $i < $length; $i++) {
      $result .= chr(rand(0, 25) + ord("A"));
    }
    return $result;
  }

  static function isValidYear($s) {
    return self::isNumberBetween($s, 1800, 2100);
  }

  static function isNumberBetween($s, $min, $max) {
    if (!preg_match('/^\d+$/', $s)) {
      return false;
    }
    $i = (int)$s;
    return $i >= $min && $i <= $max;
  }

  static function isValidDate($s) {
    return self::isDateBetween($s, '1800-01-01', '2100-12-31');
  }

  static function isDateBetween($s, $ymd1, $ymd2) {
    $a = date_parse($s);
    return $a && ($a['error_count'] == 0) && ($a['warning_count'] == 0) && ($s >= $ymd1) && ($s <= $ymd2);
  }

  static function parseRomanianDate($s) {
    $regexp = sprintf("/(?P<day>\\d{1,2})\\s+(?P<month>%s)\\s+(?P<year>\\d{4})/i", implode('|', self::$months));
    preg_match($regexp, $s, $matches);
    if (!count($matches)) {
      return null;
    }
    $month = 1 + array_search($matches['month'], self::$months);

    return sprintf("%4d-%02d-%02d", $matches['year'], $month, $matches['day']);
  }

  static function shortenString($s, $maxLength) {
    $l = mb_strlen($s);
    if ($l >= $maxLength + 3) {
      return mb_substr($s, 0, $maxLength - 3) . '...';
    }
    return $s;
  }

  static function sanitize($s) {
    if (is_string($s)) {
      $s = trim($s);
      $s = str_replace(array("\r", 'ş', 'Ş', 'ţ', 'Ţ'), array('', 'ș', 'Ș', 'ț', 'Ț'), $s);
    }
    return $s;
  }

  static function isUppercase($s) {
    return mb_strtoupper($s) == $s;
  }

  static function isCapitalized($s) {
    $first = mb_substr($s, 0, 1);
    return mb_strtoupper($first) == $first;
  }

  static function capitalize($s) {
    return mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
  }

  // convert Arabic numbers to Roman numerals
  // Thanks to http://www.go4expert.com/forums/showthread.php?t=4948
  function arabicToRoman($num) {
    // Make sure that we only use the integer portion of the value
    $n = intval($num);
    $result = '';
 
    // Declare a lookup array that we will use to traverse the number:
    $lookup = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
                    'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
                    'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
 
    foreach ($lookup as $roman => $value) 
      {
        // Determine the number of matches
        $matches = intval($n / $value);
        // Store that many characters
        $result .= str_repeat($roman, $matches);
        // Substract that from the number
        $n = $n % $value;
      }
 
    // The Roman numeral should be built, return it
    return $result;
  }

  /** Splits
   *    abc<a href="...">def</a>ghi<a href="...">jkl</a>mno
   *  into
   *    abc, <a href="...">def</a>, ghi, <a href="...">jkl</a>, mno
   **/
  static function splitATags($s) {
    return preg_split("/(<a .*<\\/a>)/U", $s, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
  }
}

?>
