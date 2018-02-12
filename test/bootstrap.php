<?php

error_reporting(E_ALL);

define('TL_MODE', 'FE');
define('UNIT_TESTING', true);
define('UNIT_TESTING_FILES', __DIR__ . '/files');

require __DIR__ . '/../../../../system/initialize.php';

$GLOBALS['TL_LANGUAGE'] = 'de';

\System::loadLanguageFile('default');