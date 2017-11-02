<?php

require __DIR__ . '/../config/config.php';
require VENDOR_DIR . '/autoload.php';

$app = new \Athorrent\Application\WebApplication();
$app->run();
