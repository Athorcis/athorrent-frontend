<?php

require '../app/constants.php';

require APP . '/vendor/autoload.php';
require APP . '/bootstrap.php';

$app = initializeApplication();
$app->run();

?>
