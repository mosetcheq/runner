<?php

require(Config\Path::RNR . '/core/autoloader.php');

use Rnr\Core\Autoloader;
use Rnr\Http\Request;
use Rnr\Http\Response;
use Rnr\Core\Router;
use Rnr\Core\DI;
use Rnr\Core\Pipeline;

// základní mapping autoloaderu pro framework a aplikaci
Autoloader::addMap([
    'Rnr\\' => Config\Path::RNR,
    'App\\' => Config\Path::APP . 'src/'
]);

// inicializace DI kontejneru
$request = new Request();
DI::setInstance(new DI());

// kompilace / načtení rout
if (!file_exists(\Config\Path::CACHE . 'routes.php')) {
    Router::compileRoutes();
}
$routes = require \Config\Path::CACHE . 'routes.php';

$pipeline = new Pipeline($request, new Router($request, $routes));
$response = $pipeline->run();
$response->send();
exit;

$router = new Router($request, $routes);
$status = $router->dispatch();

print_r($status);
exit;
echo(Response::JSON($request->getPath())->send());