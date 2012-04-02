<?php

require_once __DIR__ . '/../lib/Util.php';

$count = Model::factory('ActVersion')->count();
print "Reconverting $count act versions\n";

$actVersions = Model::factory('ActVersion')->order_by_asc('id')->find_many();

foreach ($actVersions as $av) {
  print("Saving act version {$av->id}\n");
  $av->contents = StringUtil::sanitize($av->contents); // Force dirty field
  $oldHtml = $av->htmlContents;
  $av->save();
  if ($av->htmlContents != $oldHtml) {
    file_put_contents('/tmp/civvic_old.html', $oldHtml);
    file_put_contents('/tmp/civvic_new.html', $av->htmlContents);
    printf("Act http://civvic.ro/act?id=%d version %d has changed, diff follows\n", $av->actId, $av->versionNumber);
    $output = array();
    exec('diff /tmp/civvic_old.html /tmp/civvic_new.html', $output);
    print implode("\n", $output);
    print "\n\n";
  }
}

?>
