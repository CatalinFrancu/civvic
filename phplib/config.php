<?php

mb_internal_encoding("UTF-8");

define('CONF_ADODB_ACTIVE_RECORD_CLASS', 'adodb/adodb-active-record.inc.php');
define('CONF_ADODB_CLASS', 'adodb/adodb.inc.php');
define('CONF_DATABASE_TOOLS', 'mysql://root@localhost/civvic_tools');
define('CONF_TESSDATA_PREFIX', '/home/cata/Desktop/tesseract-3.0/');
define('CONF_TESSERACT_BINARY', '/home/cata/Desktop/tesseract-3.0/api/tesseract');
define('CONF_TMP_DIR', '/tmp/');
define('CONF_WORDLIST_FREQUENT', 'tesseract-ro-training/frequent.txt.gz');
define('CONF_WORDLIST_REGULAR', 'tesseract-ro-training/regular.txt.gz');

?>
