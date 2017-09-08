<?php

if (count($argv) < 3) {
    echo "usage php create-admin.php username password";
    exit(1);
}

$username = $argv[1];
$password = $argv[2];

require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/constants.php';

require VENDOR_DIR . '/autoload.php';

$app = new \Athorrent\Application\WebApplication();

$user = new \Athorrent\Entity\User(null, $username);
$user->setRawPassword($password);
$user->save();

$userRole = new \Athorrent\Entity\UserRole($user->getUserId(), 'ROLE_ADMIN');
$userRole->save();
