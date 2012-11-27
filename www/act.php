<?php

require_once '../lib/Util.php';
Util::hideRequestParameters('submitButton');

$id = Util::getRequestParameter('id');
$version = Util::getRequestParameter('version');

$act = Act::get_by_id($id);

if (!$act) {
  FlashMessage::add('Actul cerut nu există.');
  SmartyWrap::display('act.tpl');
  exit;
}

if ($version) {
  $shownAv = Model::factory('ActVersion')->where('actId', $id)->where('versionNumber', $version)->find_one();
} else {
  $shownAv = Model::factory('ActVersion')->where('actId', $id)->where('current', true)->find_one();
}
$actType = ActType::get_by_id($act->actTypeId);

$republicationMonitors = Model::factory('Monitor')
  ->join('act_version', 'act_version.monitorId = monitor.id')
  ->select('monitor.*')
  ->where('actId', $act->id)
  ->where('status', ACT_STATUS_REPUBLISHED)
  ->order_by_asc('versionNumber')
  ->find_many();

$referringActs = Model::factory('Act')
  ->select('act.*')
  ->distinct()
  ->join('act_version', 'act_version.actId = act.id')
  ->join('act_reference', 'act_reference.actVersionId = act_version.id')
  ->where('referredActId', $act->id)
  ->order_by_asc('year')
  ->order_by_asc('issueDate')
  ->find_many();

$collidingActs = Model::factory('act')->where('actTypeId', $act->actTypeId)->where('number', $act->number)->where('year', $act->year)
  ->where_not_equal('id', $act->id)->find_many();

SmartyWrap::assign('act', $act);
SmartyWrap::assign('shownAv', $shownAv);
SmartyWrap::assign('modifyingAct', Act::get_by_id($shownAv->modifyingActId));
SmartyWrap::assign('versions', $shownAv = Model::factory('ActVersion')->where('actId', $id)->order_by_asc('versionNumber')->find_many());
SmartyWrap::assign('actType', $actType);
SmartyWrap::assign('monitor', Monitor::get_by_id($act->monitorId));
SmartyWrap::assign('authors', Author::getForActId($act->id));
SmartyWrap::assign('actAuthors', Model::factory('ActAuthor')->where('actId', $act->id)->order_by_asc('rank')->find_many());
SmartyWrap::assign('republicationMonitors', $republicationMonitors);
SmartyWrap::assign('referringActs', $referringActs);
SmartyWrap::assign('collidingActs', $collidingActs);
SmartyWrap::assign('pageTitle', "{$actType->artName} {$act->number} / {$act->year}");
SmartyWrap::display('act.tpl');

?>
