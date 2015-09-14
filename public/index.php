<?php

require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(-1);

$app = new Woland\Application([
    'public' => __DIR__,
]);
$app(Zend\Diactoros\ServerRequestFactory::fromGlobals());
