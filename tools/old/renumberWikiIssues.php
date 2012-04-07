<?php

require_once __DIR__ . '/../../lib/Util.php';

MediaWikiParser::botLogin();
$members = MediaWikiParser::fetchCategory('Monitorul_Oficial');
printf("Renumbering issues for %d pages.\n", count($members));
foreach ($members as $pageTitle) {
  $page = MediaWikiParser::fetchPage($pageTitle);
  $pageOrig = $page;

  $matches = array(); 
  preg_match_all('/\\[\\[\\s*issue::(?<issue>[^\\]]+)\\]\\]/i', $page, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
  foreach (array_reverse($matches) as $match) {
    $position = $match[0][1];
    $issue = $match['issue'][0];
    $originalIssue = $issue;
    $originalLen = strlen($match[0][0]);

    $issue = trim($issue);

    // Zero-pad to four digits (non-numeric characters may follow).
    $len = strlen($issue);
    $i = 0;
    while ($i < $len && ctype_digit($issue[$i])) {
      $i++;
    }
    $issue = str_repeat('0', 4 - $i) . $issue;

    // Insert a space before 'bis'
    if (strlen($issue) == 7 && StringUtil::endsWith($issue, 'bis')) {
      $issue = substr($issue, 0, 4) . ' ' . substr($issue, 4);
    }

    if ($issue !== $originalIssue) {
      print "Replacing {$match[0][0]} with [[issue::$issue]] in $pageTitle\n";
      $page = substr($page, 0, $position) . "[[issue::$issue]]" . substr($page, $position + $originalLen);
    }
  }

  if ($page != $pageOrig) {
    print "Saving $pageTitle\n";
    MediaWikiParser::botSavePage($pageTitle, $page, 'convertesc issue:: la patru cifre.');
  }
}

?>
