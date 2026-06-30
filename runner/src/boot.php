<?php

require(Config\Path::RNR . '/core/autoloader.php');

use Rnr\Core\Autoloader;
use Rnr\Http\Request;
use Rnr\Http\Response;

Autoloader::addMap([
    'Rnr\\' => Config\Path::RNR,
    'App\\' => Config\Path::APP
]);

$request = new Request();

$router = new Rnr\Routing\Router($request);
$router ->addStatic('GET', 'register', 'user:register')
        ->addStatic('GET', 'logout', 'user:logout');

echo(Response::JSON($request->getPath())->send());