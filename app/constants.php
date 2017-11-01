<?php

define('ROOT_DIR', dirname(__DIR__));

define('APP_DIR', ROOT_DIR . DIRECTORY_SEPARATOR . 'app');
define('BIN_DIR', ROOT_DIR . DIRECTORY_SEPARATOR . 'bin');
define('RESOURCES_DIR', ROOT_DIR . DIRECTORY_SEPARATOR . 'resources');
define('SRC_DIR', ROOT_DIR . DIRECTORY_SEPARATOR . 'src');
define('VAR_DIR', ROOT_DIR . DIRECTORY_SEPARATOR . 'var');
define('VENDOR_DIR', ROOT_DIR . DIRECTORY_SEPARATOR . 'vendor');
define('WEB_DIR', ROOT_DIR . DIRECTORY_SEPARATOR . 'web');

define('LOCALES_DIR', RESOURCES_DIR . DIRECTORY_SEPARATOR . 'locales');
define('TEMPLATES_DIR', RESOURCES_DIR . DIRECTORY_SEPARATOR . 'templates');

define('CACHE_DIR', VAR_DIR . DIRECTORY_SEPARATOR . 'cache');
define('TORRENTS_DIR', VAR_DIR . DIRECTORY_SEPARATOR . 'torrents');

if (function_exists('apcu_exists') && !DEBUG) {
    define('CACHE_DRIVER', 'Apcu');
} else {
    define('CACHE_DRIVER', 'Memstatic');
}
