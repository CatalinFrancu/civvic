<?php

require_once '../lib/Util.php';
Util::requireAdmin();

$id = Util::getRequestParameter('id');
$deleteId = Util::getRequestParameter('deleteId');
$name = Util::getRequestParameter('name');
$artName = Util::getRequestParameter('artName');
$hasNumbers = Util::getCheckboxValue('hasNumbers');
$regexps = Util::getRequestParameter('regexps');
$prefixes = Util::getRequestParameter('prefixes');
$sectionNames = Util::getRequestParameter('sectionNames');
$submitButton = Util::getRequestParameter('submitButton');

if ($deleteId) {
  $actType = ActType::get_by_id($deleteId);
  if ($actType) {
    if ($actType->delete()) {
      FlashMessage::add('Tipul de act a fost șters.', 'info');
      Util::redirect('tipuri-acte');
    } else {
      Util::redirect("editare-tip-act?id={$actType->id}");
    }
  } else {
    FlashMessage::add('Tipul de act cerut nu există.', 'warning');
    Util::redirect('tipuri-acte');
  }
}

if ($id) {
  $actType = ActType::get_by_id($id);
} else {
  $actType = Model::factory('ActType')->create();
}

if ($submitButton) {
  $actType->name = $name;
  $actType->artName = $artName;
  $actType->hasNumbers = $hasNumbers;
  $actType->regexps = trim($regexps);
  $actType->prefixes = trim($prefixes);
  $actType->sectionNames = trim($sectionNames);
  if ($actType->validate()) {
    $actType->save();
    FlashMessage::add('Datele au fost salvate.', 'info');
    Util::redirect('tipuri-acte');
  }
}

SmartyWrap::assign('actType', $actType);
SmartyWrap::assign('pageTitle', $actType->id ? "Tip de act: $actType->name" : 'Tip de act');
SmartyWrap::display('editare-tip-act.tpl');

?>
