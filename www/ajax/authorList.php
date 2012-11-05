<?php

require_once '../../lib/Util.php';
Util::requireAdmin();

$rows = Util::getRequestParameter('rows');
$page = Util::getRequestParameter('page');
$sidx = Util::getRequestParameter('sidx');
$sord = Util::getRequestParameter('sord');

$search = Util::getRequestParameter('_search');
$name = Util::getRequestParameter('name');
$title = Util::getRequestParameter('title');
$position = Util::getRequestParameter('position');
$institution = Util::getRequestParameter('institution');

// Count matching records
$authors = assembleBaseQuery($search, $name, $title, $position, $institution);
$total = $authors->count();
$maxPages = ceil($total / $rows);

// Fetch the requested page
$authors = assembleBaseQuery($search, $name, $title, $position, $institution);
$authors = $authors->offset(($page - 1) * $rows)->limit($rows);
if ($sord == 'asc') {
  $authors = $authors->order_by_asc($sidx);
} else {
  $authors = $authors->order_by_desc($sidx);
}
$authors = $authors->find_many();

header('Content-Type: text/xml; charset=UTF-8');
echo "<?xml version='1.0' encoding='utf-8'?>\n";
echo "<rows>";
echo "<page>$page</page>";
echo "<total>$maxPages</total>";
echo "<records>$total</records>";
foreach ($authors as $a) {
	echo "<row id='". $a->id . "'>";			
	echo "<cell><![CDATA[". $a->name . "]]></cell>";
	echo "<cell><![CDATA[". $a->title . "]]></cell>";
	echo "<cell><![CDATA[". $a->position . "]]></cell>";
	echo "<cell><![CDATA[". $a->institution . "]]></cell>";
	echo "</row>";
}
echo "</rows>";

/**************************************************************************/

/* Assemble a base query that can be used for (a) counting results or (b) running the query with sorting, limits and offsets. */
function assembleBaseQuery($search, $name, $title, $position, $institution) {
  $authors = Model::factory('Author');
  if ($search) {
    if ($name) {
      $authors = $authors->where_like('name', "%{$name}%");
    }
    if ($title) {
      $authors = $authors->where_like('title', "%{$title}%");
    }
    if ($position) {
      $authors = $authors->where_like('position', "%{$position}%");
    }
    if ($institution) {
      $authors = $authors->where_like('institution', "%{$institution}%");
    }
  }
  return $authors;
}

?>
