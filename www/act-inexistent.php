<?php

require_once '../lib/Util.php';

$data = Util::getRequestParameter('data');

$parts = explode(':', $data);

if (count($parts) != 3) {
  FlashMessage::add('Actul cerut nu există în baza noastră de date.');
  Util::redirect('index');
}
list($actTypeId, $number, $year) = $parts;
$actType = ActType::get_by_id($actTypeId);

$references = Model::factory('ActReference')->select('act_reference.*')
  ->join('act_version', 'act_reference.actVersionId = act_version.id')
  ->join('act', 'act_version.actId = act.id')
  ->where('act_reference.actTypeId', $actTypeId)->where('act_reference.number', $number)
  ->where('act_reference.year', $year)->where_null('referredActId')
  ->group_by('actVersionId')->order_by_asc('act.issueDate')->order_by_asc('act.year')->order_by_asc('act.number')->find_many();
$actVersions = array();
$acts = array();
$modifyingActs = array();

foreach ($references as $ref) {
  $av = ActVersion::get_by_id($ref->actVersionId);
  $actVersions[] = $av;
  $acts[] = Act::get_by_id($av->actId);
  $modifyingActs[] = Act::get_by_id($av->modifyingActId);
}

SmartyWrap::assign('actVersions', $actVersions);
SmartyWrap::assign('acts', $acts);
SmartyWrap::assign('modifyingActs', $modifyingActs);
SmartyWrap::assign('actType', $actType);
SmartyWrap::assign('number', $number);
SmartyWrap::assign('year', $year);
SmartyWrap::assign('pageTitle', "{$actType->artName} {$number} / {$year}");
SmartyWrap::display('act-inexistent.tpl');

?>
