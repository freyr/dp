<?php

declare(strict_types=1);

use Freyr\DP\Routing\Application;
use Freyr\DP\Routing\Routers\RouterCollection;
use Freyr\DP\Routing\Routers\RouterFactory;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Psr7\Uri;
use Slim\ResponseEmitter;

require_once __DIR__ . '/../../vendor/autoload.php';


$factory = new RouterFactory();
$app = new Application($factory->create());

// spoof request
$request = ServerRequestCreatorFactory::create()->createServerRequestFromGlobals()
    ->withUri(new Uri('https','example.com',80,'/adv'));

(new ResponseEmitter())->emit($app->handle($request));
