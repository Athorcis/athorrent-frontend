<?php

if (count($argv) < 3) {
    echo "usage php create-admin.php";
    exit(1);
}

$username = $argv[1];
$password = $argv[2];

require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/constants.php';

require VENDOR . '/autoload.php';

$app = new \Athorrent\Application\WebApplication();

$user = new User(null, $username);
$user->setRawPassword($password);
$user->save();
