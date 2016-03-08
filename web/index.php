<?php

require '../app/config.php';
require '../app/constants.php';

require VENDOR . '/autoload.php';
require APP . '/bootstrap.php';

$app = initializeApplication();
$app->run();

?>
