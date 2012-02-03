<?php

require_once Config::get('general.smartyClass');

class SmartyWrap {
  private static $theSmarty = null;

  static function init() {
    self::$theSmarty = new Smarty();
    self::$theSmarty->template_dir = Util::$rootPath . '/templates';
    self::$theSmarty->compile_dir = Util::$rootPath . '/templates_c';
    self::assign('wwwRoot', Util::$wwwRoot);
  }

  static function assign($name, $value) {
    self::$theSmarty->assign($name, $value);
  }

  static function display($templateName) {
    self::$theSmarty->assign('templateName', "$templateName");
    self::$theSmarty->display('layout.tpl');
  }
}

?>
