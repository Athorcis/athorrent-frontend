<?php

define('ROOT', realpath(__DIR__ . DIRECTORY_SEPARATOR . '..'));

define('APP', ROOT . DIRECTORY_SEPARATOR . 'app');
define('BIN', ROOT . DIRECTORY_SEPARATOR . 'bin');
define('VENDOR', ROOT . DIRECTORY_SEPARATOR . 'vendor');
define('WEB', ROOT . DIRECTORY_SEPARATOR . 'web');
define('TMP', ROOT . DIRECTORY_SEPARATOR . 'tmp');

define('LOCALES', APP . DIRECTORY_SEPARATOR . 'locales');

define('CACHE', TMP . DIRECTORY_SEPARATOR . 'cache');
define('TORRENTS', TMP . DIRECTORY_SEPARATOR . 'torrents');

if (function_exists('apcu_exists') && !DEBUG) {
    define('CACHE_TYPE', '\Athorrent\Utils\Cache\ApcCache');
} else {
    define('CACHE_TYPE', '\Athorrent\Utils\Cache\DummyCache');
}

?>
