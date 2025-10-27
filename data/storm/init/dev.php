<?php

use IPS\Dispatcher\Front;

define('REPORT_EXCEPTIONS', true);
$_SERVER['SCRIPT_FILENAME'] = __FILE__;
require_once 'init.php';
Front::i()->run();
