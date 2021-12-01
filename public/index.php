<?php

declare(strict_types=1);

/** @var App $app */
/* @var ContainerInterface $container */


use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Psr7\Uri;
use Slim\ResponseEmitter;
use Symfony\Component\Finder\Finder;

require __DIR__.'/../app/app.php';

/** @var \Symfony\Component\Finder\SplFileInfo $routes */
foreach (Finder::create()->in(__DIR__.'/../config/routes')->name('*.php') as $routes) {
    require_once $routes->getRealPath();
}

$app->addRoutingMiddleware();

$request = ServerRequestCreatorFactory::create()->createServerRequestFromGlobals()
    ->withUri(new Uri('https','example.com',80,'/image/show/3'));
//    ->withUri(new Uri('https','example.com',80,'/image/catalog/test_name_catalog'));
$response = $app->handle($request);
$responseEmitter = new ResponseEmitter();

$responseEmitter->emit($response);
