<?php

require_once '../lib/Util.php';
Util::requireAdmin();
ini_set('max_execution_time', '3600');

$number = Util::getRequestParameter('number');
$year = Util::getRequestParameter('year');
$previewedNumber = Util::getRequestParameter('previewedNumber');
$previewedYear = Util::getRequestParameter('previewedYear');
$submitButton = Util::getRequestParameter('submitButton');

if ($submitButton) {
  $data = MediaWikiParser::importMonitor($number, $year);
  if ($data) {
    $monitor = $data['monitor'];
    $acts = $data['acts'];
    $actVersions = $data['actVersions'];
    $actAuthorMatrix = $data['actAuthors'];

    if ($previewedNumber == $number && $previewedYear == $year) {
      $monitor->save();
      foreach ($acts as $i => $act) {
        $act->monitorId = $monitor->id();
        if (!$act->number) {
          $act->number = Act::getNextFnSlot();
        }
        $act->save();
        $av = $actVersions[$i];
        $av->actId = $av->modifyingActId = $act->id;
        $av->save();

        $rank = 1;
        foreach ($actAuthorMatrix[$i] as $aa) {
          $aa->actid = $act->id;
          $aa->rank = $rank++;
          $aa->save();
        }
      }
      MediaWikiParser::maybeProtectMonitor($number, $year);
      FlashMessage::add('Monitorul a fost importat.', 'info');
      Util::redirect("monitor?id={$monitor->id}");
    }

    $authorMatrix = array();
    foreach ($actAuthorMatrix as $actAuthors) {
      $authors = array();
      foreach ($actAuthors as $aa) {
        $authors[] = Author::get_by_id($aa->authorId);
      }
      $authorMatrix[] = $authors;
    }

    $actTypes = array();
    foreach ($acts as $act) {
      $actTypes[] = ActType::get_by_id($act->actTypeId);
    }

    foreach ($actVersions as $av) {
      $av->annotate(null);
      $av->htmlContents = MediaWikiParser::wikiToHtml($av);
    }

    SmartyWrap::assign('monitor', $monitor);
    SmartyWrap::assign('acts', $acts);
    SmartyWrap::assign('actTypes', $actTypes);
    SmartyWrap::assign('actVersions', $actVersions);
    SmartyWrap::assign('actAuthorMatrix', $actAuthorMatrix);
    SmartyWrap::assign('authorMatrix', $authorMatrix);
    FlashMessage::add("Această pagină este o previzualizare. Dacă totul arată bine, apăsați din nou butonul 'Importă'.", 'warning');
  }
}

SmartyWrap::assign('number', $number);
SmartyWrap::assign('year', $year);
SmartyWrap::assign('pageTitle', 'Importare Monitor Oficial');
SmartyWrap::display('importare-monitor.tpl');

?>
