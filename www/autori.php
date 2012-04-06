<?php

require_once '../lib/Util.php';
Util::requireAdmin();

SmartyWrap::addCss('jqgrid');
SmartyWrap::addJs('jqgrid');
SmartyWrap::assign('authors', Model::factory('Author')->order_by_asc('name')->order_by_asc('position')->find_many());
SmartyWrap::assign('pageTitle', 'Autori');
SmartyWrap::display('autori.tpl');

?>
