<?php

require_once '../lib/Util.php';
Util::requireAdmin();

$id = Util::getRequestParameter('id');
$deleteId = Util::getRequestParameter('deleteId');
$modifyingActId = Util::getRequestParameter('modifyingActId');
$status = Util::getRequestParameter('status');
$monitorId = Util::getRequestParameter('monitorId');
$contents = Util::getRequestParameter('contents');
$allVersions = Util::getRequestParameter('allVersions');
$submitButton = Util::getRequestParameter('submitButton');
$previewButton = Util::getRequestParameter('previewButton');

if ($deleteId) {
  $av = ActVersion::get_by_id($deleteId);
  if ($av) {
    if ($av->delete()) {
      FlashMessage::add('Versiunea a fost ștearsă.', 'info');
      Util::redirect("editare-act?id={$av->actId}");
    } else {
      Util::redirect("editare-versiune-act?id={$av->id}");
    }
  } else {
    FlashMessage::add('Versiunea cerută nu există.', 'warning');
    Util::redirect("editare-act?id={$av->actId}");
  }
}

$av = ActVersion::get_by_id($id);
$avs = null;
$act = Act::get_by_id($av->actId);

if ($submitButton || $previewButton) {
  $originalContents = $av->contents;
  $av->modifyingActId = $modifyingActId;
  if ($status != $av->status) {
    $av->status = $status;
  }
  $av->monitorId = $monitorId;
  $av->contents = $contents;

  if ($allVersions) {
    $diff = SimpleDiff::lineDiff($originalContents, $contents);
    $insertions = false;
    foreach ($diff as $lineNo => $change) {
      if (!count($change['d'])) {
        $insertions = true;
      }
    }
    if ($insertions) {
      FlashMessage::add('Inserarea în toate versiunile nu este permisă, ci numai modificarea sau ștergerea.');
    } else {
      $avs = Model::factory('ActVersion')->where('actId', $act->id)->where_not_equal('id', $av->id)->order_by_asc('versionNumber')->find_many();
      foreach ($diff as $lineNo => $change) {
        $text1 = implode("\n", $change['d']) . "\n";
        $text2 = implode("\n", $change['i']) . (count($change['i']) ? "\n" : '');
        $text1Preview = (mb_strlen($text1) < 40)
          ? $text1
          : mb_substr($text1, 0, 40) . '...';
        foreach ($avs as $actVersion) {
          $count = substr_count($actVersion->contents, $text1);
          if ($count > 1) {
            FlashMessage::add("Textul '{$text1Preview}' apare de mai multe ori în versiunea {$actVersion->versionNumber}. Nu am făcut înlocuirea.",
                              'warning');
          } else if (!$count) {
            FlashMessage::add("Textul '{$text1Preview}' nu apare în versiunea {$actVersion->versionNumber}.", 'warning');
          } else {
            $actVersion->contents = str_replace($text1, $text2, $actVersion->contents);
          }
        }
      }
    }
  }
}

if ($previewButton) {
  $previousAv = Model::factory('ActVersion')->where('actId', $av->actId)->where('versionNumber', $av->versionNumber - 1)->find_one();
  $av->annotate($previousAv);
  $av->htmlContents = MediaWikiParser::wikiToHtml($av);
  $av->validate();

  if ($avs) {
    foreach ($avs as $i => $actVersion) {
      if ($actVersion->versionNumber == $av->versionNumber + 1) {
        $previousAv = $av;
      } else if ($i) {
        $previousAv = $avs[$i - 1];
      } else {
        $previousAv = null;
      }
      $actVersion->annotate($previousAv);
      $actVersion->htmlContents = MediaWikiParser::wikiToHtml($actVersion);
    }
  }
}

if ($submitButton) {
  // Don't save if there are errors with the multiversion edit.
  if ($av->validate() && (!$allVersions || $avs)) {
    if ($avs) {
      foreach ($avs as $actVersion) {
        $actVersion->save();
      }
    }
    $av->save();
    FlashMessage::add('Datele au fost salvate.', 'info');
    Util::redirect("editare-act?id={$av->actId}");
  }
}

SmartyWrap::assign('av', $av);
SmartyWrap::assign('avs', $avs);
SmartyWrap::assign('act', $act);
SmartyWrap::assign('modifyingAct', Act::get_by_id($av->modifyingActId));
SmartyWrap::assign('actStatuses', Act::$statuses);
SmartyWrap::assign('actTypes', ActType::mapById());
SmartyWrap::assign('preview', $previewButton);
SmartyWrap::assign('monitors', Model::factory('Monitor')->order_by_asc('year')->order_by_asc('number')->find_many());
SmartyWrap::assign('numVersions', Model::factory('ActVersion')->where('actId', $act->id)->count());
SmartyWrap::assign('allVersions', $allVersions);
SmartyWrap::assign('pageTitle', "Versiune: $av->versionNumber");
SmartyWrap::display('editare-versiune-act.tpl');

?>
