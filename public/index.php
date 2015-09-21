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

// Load config.
$home = posix_getpwuid(posix_getuid())['dir'];
$confPath = "$home/.config/woland.json";
$settings = json_decode(file_get_contents($confPath), true);

$app = new App(compact('settings'));
$controller = new Controller($app);

// Setup Twig.
$app->getContainer()['view'] = function ($c) use ($settings) {
    $view = new \Slim\Views\Twig(
        dirname(__DIR__) . '/src/templates',
        ['cache' => $settings['cache'] . '/templates']
    );

    $view->addExtension(new \Slim\Views\TwigExtension(
        $c['router'],
        $c['request']->getUri()
    ));

    $view->addExtension(new \Woland\TwigExtension());
    $view->getEnvironment()->getExtension('core')
        ->setDateFormat('Y-m-d H:i:s P')
    ;

    return $view;
};

// Setup routes.
$app->get('/_/{type}/{asset}', [$controller, 'serveAsset']);
$app->get('/[{favorite}/{path:.*}]', [$controller, 'servePath']);

$app->run();
