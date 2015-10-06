<?php

define('ROOT', realpath(__DIR__ . DIRECTORY_SEPARATOR . '..'));
define('APP', ROOT . DIRECTORY_SEPARATOR . 'app');

require APP . '/config.php';

if (DEBUG) {
    ini_set('display_errors', true);
    ini_set('error_reporting', E_ALL);
}

require APP . '/vendor/autoload.php';
require APP . '/bootstrap.php';

$app = initializeApplication();
$app->run();

?>
