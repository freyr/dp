<?php

declare(strict_types=1);

use Freyr\DP\Routing\Application;
use Freyr\DP\Routing\Routers\SimpleRouter;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Psr7\Uri;
use Slim\ResponseEmitter;

require_once __DIR__ . '/../../vendor/autoload.php';

$app = new Application(new SimpleRouter());

// spoof request
$request = ServerRequestCreatorFactory::create()->createServerRequestFromGlobals()
    ->withUri(new Uri('https','example.com',80,'/'));

(new ResponseEmitter())->emit($app->handle($request));
