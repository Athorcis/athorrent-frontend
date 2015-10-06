<?php

function getMinifiedJs($relativePath) {
    $absolutePath = __DIR__ . DIRECTORY_SEPARATOR . $relativePath;
    $path = implode(DIRECTORY_SEPARATOR, array(__DIR__, '..', 'tmp', 'cache', 'js', md5($relativePath) . '.js'));

    if (!is_file($path) || filemtime($path) < filemtime($absolutePath)) {
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        exec('uglifyjs -cmo ' . $path . ' ' . $absolutePath, $output);
    }

    return $path;
}

if (isset($_SERVER['REDIRECT_URL'])) {

    $relativePath = preg_replace('/^\\/(.+)\\.min\\.js$/', '$1.js',  $_SERVER['REDIRECT_URL']);
    $absolutePath = realpath(__DIR__ . DIRECTORY_SEPARATOR . $relativePath);

    if (strpos($absolutePath, __DIR__ . DIRECTORY_SEPARATOR) === false) {
        return;
    }

    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
        $timestamp = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);

        if (is_int($timestamp) && $timestamp > filemtime($absolutePath)) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
            return;
        }
    }

    $path = getMinifiedJs($relativePath);

    header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
    header('Content-Type: application/javascript');
    header('Expires: ' . date('r', time() + 3600 * 24 * 60));
    header('Last-Modified: ' . date('r', filemtime($path)));

    echo file_get_contents($path);
}

?>
