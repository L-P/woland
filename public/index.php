<?php

use Slim\App;
use Woland\Controller;

require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(-1);

// HACK: get routes to work when using the PHP dev server.
if (php_sapi_name() === 'cli-server') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['REQUEST_URI'] = '/index.php/' . $_SERVER['REQUEST_URI'];
    $_SERVER['PHP_SELF']    = '/index.php/' . $_SERVER['PHP_SELF'];
}

$confPath = posix_getpwuid(posix_getuid())['dir'] . '/.config/woland.json';
$settings = json_decode(file_get_contents($confPath), true);

$app = new App(compact('settings'));
$controller = new Controller($app);

$app->get('/_/{type}/{asset}', [$controller, 'serveAsset']);
$app->get('/[{favorite}/{path:.*}]', [$controller, 'servePath']);

$app->run();
