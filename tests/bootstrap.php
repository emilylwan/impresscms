<?php

$_SERVER['REMOTE_ADDR'] = '0.0.0.0';

define('ICMS_PUBLIC_PATH', dirname(__DIR__) . '/htdocs');

require_once dirname(__DIR__) . '/mainfile.php';

error_reporting(E_ALL & E_STRICT & ~E_DEPRECATED);