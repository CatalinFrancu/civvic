<?php

MediaWikiParser::init();

class MediaWikiParser {
  private static $url;
  private static $botName;
  private static $botPassword;

  static function init() {
    self::$url = Config::get('general.mediaWikiParser');
    self::$botName = Config::get('general.mediaWikiBotName');
    self::$botPassword = Config::get('general.mediaWikiBotPassword');
  }

  static function wikiToHtml($actVersion, &$actReferences = null, &$monitorReferences = null) {
    $text = self::insertChangeDetails($actVersion);
    $text = self::ensureReferences($text);
    $text = self::removeMonitorLinks($text);
    $text = self::nowikiManualLinks($text);
    $text = self::nowikiMathTags($text);
    $text = self::parse($text);
    $text = self::rewikiManualLinks($text);
    $text = self::deleteEmptyTables($text);
    $text = self::texToMathML($text);

    // Automatic and manual links to acts
    $months = implode('|', StringUtil::$months);
    $monthRegexps = array("(?P<monthName>{$months})",
                          "(?P<monthArabic>0?1|0?2|0?3|0?4|0?5|0?6|0?7|0?8|0?9|10|11|12)",
                          "(?P<monthRoman>I|II|III|IV|V|VI|VII|VIII|IX|X|XI|XII)");
    $actTypes = Model::factory('ActType')->raw_query('select * from act_type order by length(name) desc', null)->find_many();
    foreach ($actTypes as $at) {
      $regexps = explode("\n", $at->regexps);
      foreach ($regexps as $regexp) {
        if ($regexp) {
          // Add regexp for manual matches of the form ((regexp|display_text))
          $regexp = "(\\(\\()?" . $regexp . "(\\s*\\|(?P<displayText>[^|]+)\\)\\))?";
          // Assert that there isn't a link already and that the text doesn't immediately follow a dash
          $regexp = "/(?<!-){$regexp}(?!<\\/a)/i";
          // Replace the NUMBER and DATE with number and date regexps
          $regexp = str_replace('NUMBER', '(?P<number>[-0-9A-Za-z.]+)', $regexp);
          $regexp = str_replace('DATE', sprintf("((?P<day>\\d{1,2})(\\s+|\\.)(%s)(\\s+|\\.))?(?P<year>\\d{4})", implode('|', $monthRegexps)), $regexp);

          $matches = array();
          preg_match_all($regexp, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
          foreach (array_reverse($matches) as $match) {
            $linkText = array_key_exists('displayText', $match) ? $match['displayText'][0] : $match[0][0];
            $position = $match[0][1];
            $number = $match['number'][0];
            $year = $match['year'][0];
            $day = array_key_exists('day', $match) ? $match['day'][0] : 0;
            if (array_key_exists('monthName', $match) && $match['monthName'][0]) {
              $month = 1 + array_search(strtolower($match['monthName'][0]), StringUtil::$months);
            } else if (array_key_exists('monthArabic', $match) && $match['monthArabic'][0]) {
              $month = (int)$match['monthArabic'][0];
            } else if (array_key_exists('monthRoman', $match) && $match['monthRoman'][0]) {
              $month = 1 + array_search(strtolower($match['monthRoman'][0]), StringUtil::$monthsRoman);
            } else {
              $month = 0;
            }

            if ($position && $text[$position - 1] == '@') {
              // Automatic link is disallowed explicitly
              $text = substr($text, 0, $position - 1) . substr($text, $position);
            } else {
              $act = Act::get_by_id($actVersion->actId);

              $ref = Model::factory('ActReference')->create();
              $ref->actTypeId = $at->id;
              $ref->number = $number;
              $ref->year = $year;
              $ref->issueDate = ($day && $month) ? sprintf("%d-%02d-%02d", $year, $month, $day) : null;

              $referredAct = Act::getReferredAct($ref, $act ? $act->estimateIssueDate() : null);
              if ($referredAct) {
                $ref->referredActId = $referredAct->id;
              }

              // Self-referring acts do occur, see ID = 1040 or 1360
              if (!$act || $ref->referredActId != $act->id) {
                $link = Act::getLink($referredAct, $ref, $linkText);
                $text = substr($text, 0, $position) . $link . substr($text, $position + strlen($match[0][0]));
                if ($actReferences !== null) {
                  $actReferences[] = $ref;
                }
              }
            }
          }
        }
      }
    }

    // Manual links using the explicit act ID, e.g. ((1390|legea nr. 1/1990))
    $regexp = "/\\(\\(\\s*(?P<actId>[0-9]+)\\s*\\|(?P<displayText>[^|]+)\\)\\)/i";
    $matches = array();
    preg_match_all($regexp, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    foreach (array_reverse($matches) as $match) {
      $linkText = $match['displayText'][0];
      $position = $match[0][1];

      $referredAct = Act::get_by_id($match['actId'][0]);
      if ($referredAct) {
        $ref = Model::factory('ActReference')->create();
        $ref->actTypeId = $referredAct->actTypeId;
        $ref->number = $referredAct->number;
        $ref->year = $referredAct->year;
        $ref->issueDate = $referredAct->issueDate;
        $ref->referredActId = $referredAct->id;

        $link = Act::getLink($referredAct, $ref, $linkText);
        $text = substr($text, 0, $position) . $link . substr($text, $position + strlen($match[0][0]));
        if ($actReferences !== null) {
          $actReferences[] = $ref;
        }
      }
    }

    // Automatic links to monitors
    $date = sprintf("\\s*(din|\\/)\\s*((?P<day>\\d{1,2})(\\s+|\\.)(%s)(\\s+|\\.))?(?P<year>\\d{4})", implode('|', $monthRegexps));
    $regexp = "/Monitorul(ui)?\\s+Oficial(\\s+al\\s+României)?((\\s|,)+partea\\s+(1|I)(\\s|,)+)?(\\s+nr\\.?)?\\s*(?P<number>[-0-9A-Za-z.]+){$date}/i";
    $matches = array();
    preg_match_all($regexp, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    foreach (array_reverse($matches) as $match) {
      $linkText = $match[0][0];
      $position = $match[0][1];
      $number = $match['number'][0];
      $year = $match['year'][0];

      $link = Monitor::getLink($number, $year, $linkText);
      $text = substr($text, 0, $position) . $link . substr($text, $position + strlen($linkText));
      if ($monitorReferences !== null) {
        $ref = Model::factory('MonitorReference')->create();
        $ref->number = $number;
        $ref->year = $year;
        $monitorReferences[] = $ref;
      }
    }
    return $text;
  }

  static function getMediaWikiVersion() {
    $xmlString = Util::makePostRequest(self::$url, array('action' => 'expandtemplates', 'text' => "{{CURRENTVERSION}}", 'format' => 'xml'));
    $xml = simplexml_load_string($xmlString);
    return (string)$xml->expandtemplates;
  }

  static function fetchPage($pageTitle) {
    $params = array('action' => 'query', 'titles' => $pageTitle, 'prop' => 'revisions', 'rvprop' => 'content', 'format' => 'xml');
    $xmlString = Util::makePostRequest(self::$url, $params);
    $xml = simplexml_load_string($xmlString);
    $page = $xml->query->pages->page[0];
    $pageId = (string)$page['pageid'];
    if (!$page['pageid']) {
      return false;
    }
    return (string)$page->revisions->rev[0];
  }

  /** Returns an array of page titles. If you need more than 500, call botLogin() before. **/
  static function fetchCategory($cat) {
    $results = array();
    $params = array('action' => 'query', 'list' => 'categorymembers', 'cmtitle' => "Categorie:$cat", 'cmlimit' => 'max', 'format' => 'xml');
    $xmlString = Util::makePostRequest(self::$url, $params, true);
    $xml = simplexml_load_string($xmlString);
    foreach ($xml->query->categorymembers->cm as $cm) {
      $results[] = (string)$cm['title'];
    }
    return $results;
  }

  static function maybeProtectMonitor($number, $year) {
    if (self::$botName && self::$botPassword) {
      $pageTitle = "Monitorul_Oficial_{$number}/{$year}";
      MediaWikiParser::botLogin();
      MediaWikiParser::botProtectMonitor($pageTitle);
      MediaWikiParser::botInsertMigrationWarning($pageTitle);
    }
  }

  static function botLogin() {
    $xmlString = Util::makePostRequest(self::$url . "?action=login&format=xml",
                                       array('lgname' => self::$botName,
                                             'lgpassword' => self::$botPassword),
                                       true);
    $xml = simplexml_load_string($xmlString);
    // $result = (string)$xml->login['result'];
    $token = (string)$xml->login['token'];

    Util::makePostRequest(self::$url . "?action=login&format=xml",
                          array('lgname' => self::$botName,
                                'lgpassword' => self::$botPassword,
                                'lgtoken' => $token),
                          true);
  }

  static function botProtectMonitor($pageTitle) {
    $xmlString = Util::makePostRequest(self::$url,
                                       array('action' => 'query',
                                             'format' => 'xml',
                                             'prop' => 'info',
                                             'intoken' => 'protect',
                                             'titles' => $pageTitle),
                                       true);
    $xml = simplexml_load_string($xmlString);
    $protectToken = (string)$xml->query->pages->page[0]['protecttoken'];

    Util::makePostRequest(self::$url,
                          array('action' => 'protect',
                                'format' => 'xml',
                                'title' => $pageTitle,
                                'protections' => 'edit=sysop',
                                'expiry' => 'never',
                                'reason' => 'Migrat la civvic.ro',
                                'token' => $protectToken),
                          true);
  }

  static function botInsertMigrationWarning($pageTitle) {
    $contents = self::fetchPage($pageTitle);
    if (strpos($contents, '{{MigrationWarning}}') !== false) {
      return; // Page already contains warning
    }
    $xmlString = Util::makePostRequest(self::$url,
                                       array('action' => 'query',
                                             'format' => 'xml',
                                             'prop' => 'info',
                                             'intoken' => 'edit',
                                             'titles' => $pageTitle),
                                       true);
    $xml = simplexml_load_string($xmlString);
    $editToken = (string)$xml->query->pages->page[0]['edittoken'];

    Util::makePostRequest(self::$url,
                          array('action' => 'edit',
                                'format' => 'xml',
                                'title' => $pageTitle,
                                'section' => '0',
                                'prependtext' => "{{MigrationWarning}}\n",
                                'summary' => 'Migrat la civvic.ro',
                                'token' => $editToken),
                          true);
  }

  static function botSavePage($pageTitle, $contents, $summary) {
    $xmlString = Util::makePostRequest(self::$url,
                                       array('action' => 'query',
                                             'format' => 'xml',
                                             'prop' => 'info',
                                             'intoken' => 'edit',
                                             'titles' => $pageTitle),
                                       true);
    $xml = simplexml_load_string($xmlString);
    $editToken = (string)$xml->query->pages->page[0]['edittoken'];

    Util::makePostRequest(self::$url,
                          array('action' => 'edit',
                                'format' => 'xml',
                                'title' => $pageTitle,
                                'text' => $contents,
                                'summary' => $summary,
                                'token' => $editToken),
                          true);
  }

  private static function ensureReferences($text) {
    if (preg_match("/<\\s*ref\\s+name*\\s*=/", $text)) {
      FlashMessage::add('Sistemul nu admite referințe cu &lt;ref name="..."&gt; decât în cadrul aceluiași act. ' .
                        'Dacă aveți referințe cu &lt;ref name="..."&gt; care trimit la alt act, ' .
                        'vă rugăm să copiați explicit textul referinței.', 'warning');
    }
    if (preg_match("/<\\s*ref[^>]*>/", $text) && !preg_match("/<\\s*references\\s*\\/>/", $text)) {
      $text .= "\n<references/>";
      FlashMessage::add('Dacă folosiți &lt;ref&gt; pentru a indica referințe, nu uitați să adăugați eticheta &lt;references/&gt; la sfârșit.', 'warning');
    }
    return $text;
  }

  private static function removeMonitorLinks($text) {
    $count = 0;
    $text = preg_replace("/\\[\\[Monitorul[_ ]Oficial[^|]+\\|([^\\]]+)\\]\\]/", '$1', $text, -1, $count);
    if ($count) {
      FlashMessage::add("Am eliminat {$count} legături wiki către alte monitoare.", 'warning');
    }
    return $text;
  }

  private static function nowikiMathTags($text) {
    return str_replace(array('<math>', '</math>'), array('<nowiki><math>', '</math></nowiki>'), $text);
  }

  private static function nowikiManualLinks($text) {
    return preg_replace("/(\\(\\([^|]+)\\|([^()|]+\\)\\))/", "$1<nowiki>|</nowiki>$2", $text);
  }

  private static function rewikiManualLinks($text) {
    return preg_replace("/(\\(\\([^|]+)<nowiki>\\|<\\/nowiki>([^()|]+\\)\\))/", "$1|$2", $text);
  }

  private static function texToMathML($text) {
    $matches = array();
    preg_match_all("/&lt;math&gt;(?<expr>.*)&lt;\\/math&gt;/U", $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    foreach (array_reverse($matches) as $match) {
      $position = $match[0][1];
      $length = strlen($match[0][0]);
      $expr = trim($match['expr'][0]);
      file_put_contents('/tmp/civvic-math.txt', $expr);
      exec("blahtexml --mathml < /tmp/civvic-math.txt > /tmp/civvic-mathml.txt");
      $xml = simplexml_load_file('/tmp/civvic-mathml.txt');
      $mathml = "";
      foreach ($xml->mathml->markup->children() as $child) {
        $mathml .= $child->asXml();
      }
      $text = substr($text, 0, $position) . "<math>$mathml</math>" . substr($text, $position + $length);
    }
    return $text;
  }

  private static function insertChangeDetails($actVersion) {
    $ann = json_decode($actVersion->annotated, true);
    $output = array();
    $version = 'a1';
    $n = count($ann['lines']);

    $modifyingActs = Act::getModifyingActs($actVersion->actId);
    $modifyingActs[$actVersion->versionNumber] = Act::get_by_id($actVersion->modifyingActId); // In case it hasn't yet been saved.

    $tableLevel = 0; // We cannot insert a div in the middle of a table

    for ($i = 0; $i < $n; $i++) {
      if ($ann['history'][$i] != $version) {
        // Close the previous section, if needed
        if ($tableLevel) {
          $output[] = '|}';
        }
        if ($version != 'a1') {
          $output[] = '</div>';
          switch (substr($version, 0, 1)) {
          case 'a': $keyword = 'Adăugat'; break;
          case 'm': $keyword = 'Modificat'; break;
          default: $keyword = 'Abrogat';
          }
          $act = $modifyingActs[substr($version, 1)];
          $actText = $act ? $act->getDisplayId() : 'un act necunoscut';
          $output[] = sprintf("<div class=\"actChangeDetails\">%s de %s</div>", $keyword, $actText);
        }
        $version = $ann['history'][$i];
        if ($version != 'a1') {
          switch (substr($version, 0, 1)) {
          case 'a': $divClass = 'addedSection'; break;
          case 'm': $divClass = 'modifiedSection'; break;
          default: $divClass = 'deletedSection';
          }
          $output[] = "<div class=\"actChange {$divClass}\">";
        }
        if ($tableLevel) {
          $output[] = '{|';
        }
      }
      $line = $ann['lines'][$i];
      $output[] = $line;
      if (StringUtil::startsWith($line, '{|')) {
        $tableLevel++;
      } else if (StringUtil::startsWith($line, '|}')) {
        $tableLevel--;
      }
    }

    if ($version != 'a1') {
      if ($tableLevel) {
        $output[] = '|}';
      }
      $output[] = '</div>';
      switch (substr($version, 0, 1)) {
      case 'a': $keyword = 'Adăugat'; break;
      case 'm': $keyword = 'Modificat'; break;
      default: $keyword = 'Abrogat';
      }
      $act = $modifyingActs[substr($version, 1)];
      $actText = $act ? $act->getDisplayId() : 'un act necunoscut';
      $output[] = sprintf("<div class=\"actChangeDetails\">%s de %s</div>", $keyword, $actText);
    }
    return implode("\n", $output);
  }

  static function parse($text) {
    $text = "__NOTOC__\n" . $text;
    $xmlString = Util::makePostRequest(self::$url, array('action' => 'parse', 'text' => $text, 'format' => 'xml'));
    $xml = simplexml_load_string($xmlString);
    return (string)$xml->parse->text;
  }

  static function getAllImages() {
    $xmlString = Util::makePostRequest(self::$url, array('action' => 'query', 'list' => 'allimages', 'ailimit' => 1000, 'format' => 'xml'));
    $xml = simplexml_load_string($xmlString);
    return $xml->query->allimages;
  }

  static function deleteEmptyTables($text) {
    return preg_replace("/<table>\\s*<tr>\\s*<td>\\s*<\\/td>\\s*<\\/tr>\\s*<\\/table>/i", '', $text);
  }

  // Returns an array consisting of a monitor and a collection of acts and their versions.
  // Returns false and sets flash messages on all errors.
  static function importMonitor($number, $year) {
    // Check that we don't already have this monitor
    $monitor = Monitor::get_by_number_year($number, $year);
    if ($monitor) {
      FlashMessage::add("Monitorul {$number}/{$year} a fost deja importat (sau există în sistem din alt motiv).");
      return false;
    }

    // Fetch the contents
    $contents = self::fetchPage("Monitorul_Oficial_{$number}/{$year}");
    if ($contents === false) {
      FlashMessage::add("Monitorul {$number}/{$year} nu există.");
      return false;
    }
    $contents = StringUtil::sanitize($contents);
    $contents = self::sanitizeMonitor($contents, $year);

    // Extract the publication date
    $regexp = sprintf("/Anul\\s+[IVXLCDM]+,?\\s+Nr\\.\\s+\\[\\[issue::\s*0*(?P<number>[-0-9A-Za-z.]+)\\]\\]\\s+-\\s+(Partea\\s+I\\s+-\\s+)?" .
                      "(Luni|Marți|Miercuri|Joi|Vineri|Sâmbătă|Duminică),?\\s*(?P<day>\\d{1,2})\\s+(?P<month>%s)\\s+" .
                      "\\[\\[year::\s*(?P<year>\\d{4})\\]\\]/i", implode('|', StringUtil::$months));
    preg_match($regexp, $contents, $matches);
    if (!count($matches)) {
      FlashMessage::add('Nu pot extrage data din primele linii ale monitorului.');
    }
    if ($matches['number'] != $number) {
      FlashMessage::add(sprintf("Numărul din monitor (%s) nu coincide cu numărul din URL (%s).", $matches['number'], $number));
      return false;
    }
    if ($matches['year'] != $year) {
      FlashMessage::add(sprintf("Anul din monitor (%s) nu coincide cu anul din URL (%s).", $matches['year'], $year));
      return false;
    }
    $month = 1 + array_search($matches['month'], StringUtil::$months);

    // Build the monitor
    $monitor = Model::factory('Monitor')->create();
    $monitor->number = $number;
    $monitor->year = $year;
    $monitor->issueDate = sprintf("%4d-%02d-%02d", $year, $month, $matches['day']);
    $data['monitor'] = $monitor;
    $data['acts'] = array();
    $data['actVersions'] = array();
    $data['actAuthors'] = array();

    // Split the contents into lines and locate the == and === headers
    $lines = explode("\n", $contents);
    $headers23 = array();
    foreach ($lines as $i => $line) {
      if (StringUtil::startsWith($line, '==') && !StringUtil::startsWith($line, '====')) {
        $headers23[] = $i;
      }
    }
    $headers23[] = count($lines);

    $actTypes = Model::factory('ActType')->raw_query('select * from act_type order by length(name) desc', null)->find_many();
    $sectionActTypeId = 0;

    foreach ($headers23 as $i => $lineNo) {
      $line = ($lineNo < count($lines)) ? $lines[$lineNo] : '';

      if (StringUtil::startsWith($line, '==') && !StringUtil::startsWith($line, '===')) {
        // See if this section title points to an act type
        $matches = array();
        preg_match("/^\\s*==(?P<title>.+)==\\s*$/", $line, $matches);
        if (!array_key_exists('title', $matches)) {
          FlashMessage::add("Nu pot extrage titlul secțiunii din linia '$line'.");
          return false;
        }
        $title = trim($matches['title']);
        $sectionActTypeId = 0;
        $i = 0;
        do {
          foreach (explode("\n", $actTypes[$i]->sectionNames) as $sectionName) {
            if ($title == $sectionName) {
              $sectionActTypeId = $actTypes[$i]->id;
            }
          }
          $i++;
        } while ($i < count($actTypes) && !$sectionActTypeId);
      }

      if ($i < count($headers23) - 1 && StringUtil::startsWith($line, '===')) {
        $chunk = array_slice($lines, $lineNo, $headers23[$i + 1] - $lineNo);
        $act = Model::factory('Act')->create();
        $act->year = $monitor->year;
        $actAuthors = array();

        // Extract the title from the first line
        $matches = array();
        preg_match("/^\\s*===(?P<title>.+)===\\s*$/", $chunk[0], $matches);
        if (!array_key_exists('title', $matches)) {
          FlashMessage::add("Nu pot extrage titlul actului din linia '{$chunk[0]}'.");
          return false;
        }
        $act->name = trim($matches['title']);

        // Extract the act type from the title
        if ($sectionActTypeId) {
          $act->actTypeId = $sectionActTypeId;
        } else {
          $i = 0;
          do {
            foreach (explode("\n", $actTypes[$i]->prefixes) as $prefix) {
              if (StringUtil::startsWith($act->name, $prefix . ' ')) {
                $act->actTypeId = $actTypes[$i]->id;
              }
            }
            $i++;
          } while ($i < count($actTypes) && !$act->actTypeId);

          if (!$act->actTypeId) {
            FlashMessage::add("Nu pot extrage tipul de act din titlul '{$act->name}'. Voi folosi implicit tipul 'Diverse'.", 'warning');
            $diverse = ActType::get_by_name('Diverse');
            $act->actTypeId = $diverse->id;
          }
        }

        // Locate the signature line
        $signIndex = count($chunk);
        do {
          $signIndex--;
          $signLine = trim($chunk[$signIndex]);
          $found = StringUtil::startsWith($signLine, '{{') && StringUtil::endsWith($signLine, '}}');
        } while ($signIndex > 0 && !$found);
        if ($found) {
          $signData = self::parseSignatureLine($signLine);
          if (!$signData) {
            return false;
          }
          $actAuthors = array();
          foreach ($signData['authorIds'] as $i => $authorId) {
            $aa = Model::factory('ActAuthor')->create();
            $aa->authorId = $authorId;
            $aa->signatureType = $signData['signatureTypes'][$i];
            $aa->note = $signData['notes'][$i];
            $actAuthors[] = $aa;
          }
          $act->placeId = $signData['placeId'];
          $act->issueDate = $signData['issueDate'];
          $act->number = $signData['number'];

          if ($act->issueDate) {
            $issueDateYear = substr($act->issueDate, 0, 4);
            if ($issueDateYear != $act->year) {
              $act->year = $issueDateYear;
              FlashMessage::add(sprintf("%s a fost emis în %s, dar publicat în %s. Asigurați-vă că am ales bine anul actului.",
                                        $act->getDisplayId(), $issueDateYear, $monitor->year), 'warning');
            }
          }

          if ($act->year && $act->number) {
            $other = Act::get_by_actTypeId_year_number($act->actTypeId, $act->year, $act->number);
            if ($other) {
              FlashMessage::add(sprintf("Actul '%s' există deja.", $act->getDisplayId()), 'warning');
            }
            $refs = ActReference::get_all_by_actTypeId_year_number($act->actTypeId, $act->year, $act->number);
            if (count($refs)) {
              FlashMessage::add(sprintf("Actul '%s' este menționat în alte acte.", $act->getDisplayId()), 'warning');
            }
          }

          array_splice($chunk, $signIndex, 1);
        } else {
          FlashMessage::add("Nu pot găsi linia cu semnătura în actul '{$act->name}'.", 'warning');
        }

        // Create the act version
        $av = ActVersion::createVersionOne($act);
        $av->contents = trim(implode("\n", array_slice($chunk, 1)));
        $data['acts'][] = $act;
        $data['actVersions'][] = $av;
        $data['actAuthors'][] = $actAuthors;
      }
    }

    return $data;
  }

  static function parseSignatureLine($line) {
    // Some signatures use named parameters, others use unnamed parameters
    $parts = explode('|', substr($line, 2, -2));
    foreach ($parts as $part) {
      $nv = preg_split('/=/', $part, 2);
      if (count($nv) == 2) {
        $parts[trim($nv[0])] = trim($nv[1]);
      }
    }

    switch ($parts[0]) {
    case 'Sem-p-Pm':
      $result = self::parseSignatureParts($line, $parts,
                                          array(array('name' => array('%s', array('nume')),
                                                      'position' => array('%s Prim-ministru', array('pt')))),
                                          'oras', 'dataAct', 'nrAct', array('pt', 'nume', 'func', 'dataAct', 'nrAct'),
                                          array('oras' => 'București'), array('func'));
      break;
    case 'SemnPcfsn':
      $result = self::parseSignatureParts($line, $parts,
                                          array(array('name' => array('%s', array(1)),
                                                      'position' => array('Președintele Consiliului Frontului Salvării Naționale', array()))),
                                          2, 3, 4, range(1, 4));
      break;
    case 'SemnPm':
      $result = self::parseSignatureParts($line, $parts,
                                          array(array('name' => array('%s', array(1)),
                                                      'position' => array('Prim-ministru', array()))),
                                          2, 3, 4, range(1, 4));
      break;
    case 'SemnPr':
      $result = self::parseSignatureParts($line, $parts,
                                          array(array('name' => array('%s', array(1)),
                                                      'position' => array('Președintele României', array()))),
                                          2, 3, 4, range(1, 4));
      break;
    case 'SemnCfsn':
      $result = self::parseSignatureParts($line, $parts,
                                          array(array('name' => array('', array()),
                                                      'institution' => array('Consiliul Frontului Salvării Naționale', array()))),
                                          1, 2, 3, range(1, 3));
      break;
    case 'SemnPcpun':
      $result = self::parseSignatureParts($line, $parts,
                                          array(array('name' => array('%s', array(1)),
                                                      'position' => array('Președintele Consiliului Provizoriu de Uniune Națională', array()))),
                                          2, 3, 4, range(1, 4));
      break;
    case 'Autor':
      $result = self::parseSignatureParts($line, $parts,
                                          array(array('name' => array('%s', array(2)),
                                                      'position' => array('%s', array(1)))),
                                          3, 4, 5, range(1, 5));
      break;
    case 'SemnPad':
      $result = self::parseSignatureParts($line, $parts,
                                          array(array('name' => array('%s', array(1)),
                                                      'position' => array('Președintele Adunării Deputaților', array()))),
                                          2, 3, 4, range(1, 4));
      break;
    case 'SemnPs':
      $result = self::parseSignatureParts($line, $parts,
                                          array(array('concat(title, " ", name)' => array('%s', array(1)),
                                                      'position' => array('Președintele Senatului', array()))),
                                          2, 3, 4, range(1, 4));
      break;
    case 'SemnPsPad':
      $result = self::parseSignatureParts($line, $parts,
                                          array(array('concat(title, " ", name)' => array('%s', array(1)),
                                                      'position' => array('Președintele Senatului', array())),
                                                array('name' => array('%s', array(2)),
                                                      'position' => array('Președintele Adunării Deputaților', array()))),
                                          3, 4, 5, range(1, 5));
      break;
    case 'SemnPsPad-fd':
      $result = self::parseSignatureParts($line, $parts,
                                          array(array('concat(title, " ", name)' => array('%s', array(1)),
                                                      'position' => array('Președintele Senatului', array())),
                                                array('name' => array('%s', array(2)),
                                                      'position' => array('Președintele Adunării Deputaților', array()))),
                                          null, null, null, range(1, 2));
      break;
    case 'SemnLege':
      $result = self::parseSignatureParts($line, $parts,
                                          array(array('concat(title, " ", name)' => array('%s', array('presSenat')),
                                                      'position' => array('Președintele Senatului', array())),
                                                array('name' => array('%s', array('presAd')),
                                                      'position' => array('Președintele Adunării Deputaților', array())),
                                                array('name' => array('%s', array('presRom')),
                                                      'position' => array('Președintele României', array()))),
                                          'oras', 'dataPres', 'nrLege',
                                          array('presSenat', 'presAd', 'presRom', 'dataPres', 'nrLege'),
                                          array('oras' => 'București'));
      if ($result) {
	$result['notes'][0] = sprintf("Această lege a fost adoptată de Senat în ședința din %s.", $parts['dataSenat']);
	$result['notes'][1] = sprintf("Această lege a fost adoptată de Adunarea Deputaților în ședința din %s.", $parts['dataAd']);
	$result['notes'][2] = sprintf("În temeiul art. 82 lit. m) din Decretul-lege nr. 92/1990 pentru alegerea parlamentului și a Președintelui " .
				      "României, promulgăm %s și dispunem publicarea sa în Monitorul Oficial al României.", $parts['numeLege']);
      }
      break;
    case 'SemnLege92':
      $result = self::parseSignatureParts($line, $parts,
                                          array(array('concat(title, " ", name)' => array('%s', array('presSenat')),
                                                      'position' => array('Președintele Senatului', array())),
                                                array('name' => array('%s', array('presCd')),
                                                      'position' => array('Președintele Camerei Deputaților', array()))),
                                          'oras', 'dataAct', 'nrAct',
                                          array('presSenat', 'presCd', 'dataAct', 'nrAct'),
                                          array('oras' => 'București'));
      if ($result) {
	$result['notes'][0] = sprintf("Această lege a fost adoptată de Senat în ședința din %s.", $parts['dataSenat']);
	$result['notes'][1] = sprintf("Această lege a fost adoptată de Camera Deputaților în ședința din %s.", $parts['dataAd']);
      }
      break;
    case 'SemnDecret':
      $result = self::parseSignatureParts($line, $parts,
                                          array(array('name' => array('%s', array('presRom')),
                                                      'position' => array('Președintele României', array())),
                                                array('name' => array('%s', array('primMin')),
                                                      'position' => array('Prim-ministru', array()))),
                                          'oras', 'dataSem', 'nrDec',
                                          array('presRom', 'primMin', 'dataSem', 'nrDec'),
                                          array('oras' => 'București'));
      if ($result) {
	$result['signatureTypes'][1] = ActAuthor::$COUNTERSIGNED;
	$result['notes'][1] = "În temeiul art. 82 alin. 2 din Decretul-lege nr. 92/1990 pentru alegerea parlamentului și a Președintelui României, " .
	  "contrasemnăm acest decret.";
      }
      break;
    default:
      FlashMessage::add(sprintf("Nu știu să interpretez semnături de tipul {{%s}}.", $parts[0]));
      return false;
    }
    return $result;
  }

  private static function parseSignatureParts($line, $parts, $authorSpecs, $placeNameField, $issueDateField, $actNumberField, $requiredFields,
                                              $defaultValues = array(), $nullValues = array()) {
    foreach ($requiredFields as $arg) {
      if (!array_key_exists($arg, $parts)) {
        FlashMessage::add("Semnătura '{$line}' nu include parametrul '{$arg}'.");
        return false;
      }
    }
    foreach ($defaultValues as $key => $value) {
      if (!array_key_exists($key, $parts)) {
        $parts[$key] = $value;
      }
    }
    foreach ($nullValues as $key) {
      if (array_key_exists($key, $parts)) {
        FlashMessage::add(sprintf("Nu știu să gestionez argumentul '%s' în semnătura '%s'.", $key, $line));
        return false;
      }
    }
    $data = array();

    $data['authorIds'] = array();
    foreach ($authorSpecs as $authorSpec) {
      $author = Model::factory('Author');
      $authorDetails = array();
      foreach ($authorSpec as $expr => $format) {
        $args = array();
        foreach ($format[1] as $key) {
          $args[] = $parts[$key];
        }
        $s = vsprintf($format[0], $args);
        $author = $author->where_raw("$expr = '$s'");
        $authorDetails[] = $s;
      }
      $author = $author->find_one();
      if (!$author) {
        FlashMessage::add(sprintf("Trebuie definit autorul '%s', ", implode(', ', $authorDetails)));
        return false;
      }
      $data['authorIds'][] = $author->id;
    }

    if ($placeNameField) {
      $place = Place::get_by_name($parts[$placeNameField]);
      if (!$place) {
        FlashMessage::add(sprintf("Trebuie definit locul '%s'.", $parts[$placeNameField]));
        return false;
      }
      $data['placeId'] = $place->id;
    } else {
      $data['placeId'] = null;
    }

    if ($issueDateField) {
      $issueDate = StringUtil::parseRomanianDate($parts[$issueDateField]);
      if (!$issueDate) {
        FlashMessage::add(sprintf("Data '%s' este incorectă.", $parts[$issueDateField]));
        return false;
      }
      $data['issueDate'] = $issueDate;
    } else {
      $data['issueDate'] = null;
    }

    if ($actNumberField) {
      $data['number'] = $parts[$actNumberField];
    } else {
      $data['number'] = '';
      FlashMessage::add("Semnăturile de tip {{{$parts[0]}}} nu includ numărul actului. Voi atribui automat un număr de tip FN.", 'warning');      
    }
    $data['signatureTypes'] = array_fill(0, count($authorSpecs), ActAuthor::$SIGNED);
    $data['notes'] = array_fill(0, count($authorSpecs), '');
    return $data;
  }

  /* Sanitizes monitor-specific text. For general purpose sanitization, use StringUtil::sanitize(). */
  static function sanitizeMonitor($text, $year) {
    // Replace single endashes (U+2013) and emdashes (U+2014) with dashes.
    $text = preg_replace("/(?<!–)–(?!–)/", '-', $text);
    $text = preg_replace("/(?<!—)—(?!—)/", '-', $text);

    // Replace [[Fișier... links with {{Imagine... templates
    $text = preg_replace("/\\[\\[(fișier|fişier|file):([^.]+)\\.(png|jpg|jpeg|gif)\\]\\]/i", "{{Imagine|$year/$2}}", $text);

    return $text;
  }

}

?>
