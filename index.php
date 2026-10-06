<?php
declare(strict_types=1);

define('APP_ROOT', __DIR__);

require APP_ROOT . '/vendor/Core/autoload.php';
require APP_ROOT . '/config/database.php';

Session::start();

if (!Session::has('_csrf')) {
    Session::set('_csrf', bin2hex(random_bytes(32)));
}

$routes = require APP_ROOT . '/config/routes.php';
$request = new Request();
$router  = new Router();

foreach ($routes as $method => $map) {
    foreach ($map as $path => $target) {
        [$controller, $action] = $target;
        $router->add($method, $path, $controller, $action);
    }
}

$router->dispatch($request);
