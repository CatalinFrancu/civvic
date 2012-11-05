<?php

require_once '../lib/Util.php';
Util::requireAdmin();

SmartyWrap::addCss('jqgrid');
SmartyWrap::addJs('jqgrid');
SmartyWrap::addJs('authors');
SmartyWrap::assign('pageTitle', 'Autori');
SmartyWrap::display('autori.tpl');

?>
