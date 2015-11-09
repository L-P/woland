<?php

use Slim\App;
use Woland\Controller;

require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(-1);

if (php_sapi_name() === 'cli-server') {
    // HACK: get routes to work when using the PHP dev server.
    $_SERVER['SCRIPT_NAME'] = '/index.php';

    set_time_limit(1);
}

$settings = get_settings();
$app = new App(compact('settings'));
$controller = new Controller($app);

// Disable full trace in prod.
if ($settings['debug'] === false) {
    unset($app->getContainer()['errorHandler']);
}

// Setup Twig.
$app->getContainer()['view'] = get_twig_creator($settings);

// Setup routes.
$app->get('/_/{type}/{asset}', [$controller, 'serveAsset']);
$app->get('/[{favorite}/{path:.*}]', [$controller, 'servePath']);

$app->run();
