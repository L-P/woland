<?php

require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(-1);

$confPath = posix_getpwuid(posix_getuid())['dir'] . '/.config/woland.json';
$app = new Woland\Application(json_decode(file_get_contents($confPath), true));
$app(Zend\Diactoros\ServerRequestFactory::fromGlobals());
