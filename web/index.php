<?php

require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/constants.php';

require VENDOR . '/autoload.php';

$app = new \Athorrent\Application\WebApplication();
$app->run();
