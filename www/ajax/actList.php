<?php

require_once '../../lib/Util.php';

$type = Util::getRequestParameter('type');
$year = Util::getRequestParameter('year');

$actType = ActType::get_by_name($type);
$acts = Model::factory('Act')
  ->raw_query("select * from act where actTypeId = ? and year = ? order by cast(number as unsigned)", array($actType->id, $year))
  ->find_many();

$results = array();
foreach ($acts as $a) {
  $results[] = array($a->id, $a->number . '. ' . StringUtil::shortenString($a->name, 50));
}

print json_encode($results);


?>
