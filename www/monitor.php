<?php

require_once '../lib/Util.php';

$id = Util::getRequestParameter('id');

$monitor = Monitor::get_by_id($id);
$acts = Model::factory('Act')->raw_query("select * from act where monitorId = ? order by actTypeId, cast(number as unsigned)", array($id))
  ->find_many();

$republishedActs = Model::factory('Act')->join('act_version', 'act.id = actId')->select('act.*')->where('status', ACT_STATUS_REPUBLISHED)
  ->where('act_version.monitorId', $id)->find_many();

SmartyWrap::assign('monitor', $monitor);
SmartyWrap::assign('acts', $acts);
SmartyWrap::assign('republishedActs', $republishedActs);
SmartyWrap::assign('pageTitle', "Monitorul Oficial nr. {$monitor->number} / {$monitor->year}");
SmartyWrap::display('monitor.tpl');

?>
