#!/usr/bin/env php
<?php

if (count($argv) < 3) {
    echo 'usage php create-admin.php username password';
    exit(1);
}

$username = $argv[1];
$password = $argv[2];

require __DIR__ . '/../config/config.php';
require VENDOR_DIR . '/autoload.php';

$app = new \Athorrent\Application\WebApplication();
$app['user_manager']->createUser($username, $password, 'ROLE_ADMIN');
