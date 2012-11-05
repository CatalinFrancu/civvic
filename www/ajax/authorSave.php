<?php

require_once '../../lib/Util.php';
Util::requireAdmin();

$oper = Util::getRequestParameter('oper');
$id = Util::getRequestParameter('id');
$name = Util::getRequestParameter('name');
$title = Util::getRequestParameter('title');
$position = Util::getRequestParameter('position');
$institution = Util::getRequestParameter('institution');

switch ($oper) {
case 'del':
  $author = Author::get_by_id($id);
  if (!$author) {
    print "Autorul cu ID={$id} nu există.";
  } else if (!$author->canDelete()) {
    print "Autorul {$author} nu poate fi șters, deoarece există acte care îl folosesc.";
  } else {
    $author->delete();
  }
  break;

case 'add':
case 'edit':
  $author = ($oper == 'edit') ? Author::get_by_id($id) : Model::factory('Author')->create();
  $author->name = $name;
  $author->title = $title;
  $author->position = $position;
  $author->institution = $institution;
  $errorMsg = $author->validate();
  if ($errorMsg) {
    print $errorMsg;
  } else {
    $author->save();
  }
  break;

default:
  print "Operație necunoscută. Vă rugăm contactați un programator.";
}

?>
