<?php

$actVersions = Model::factory('ActVersion')->find_many();

foreach ($actVersions as $av) {
  print("Saving act version {$av->id}\n");
  $av->contents = StringUtil::sanitize($av->contents); // Force dirty field
  $av->save();
}

?>
