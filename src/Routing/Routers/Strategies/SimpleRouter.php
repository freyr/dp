<?php

namespace Freyr\DP\Routing\Routers\Strategies;

use Freyr\DP\Routing\Controllers\RouteController;
use Freyr\DP\Routing\Routers\Route;
use Psr\Http\Message\ServerRequestInterface;

class SimpleRouter extends BaseRouter
{
    protected function doMatchRoute(ServerRequestInterface $request): Route
    {
        return match ($request->getUri()->getPath()) {
            '/' => new Route('test', RouteController::class, 'show'),
        };
    }
}
