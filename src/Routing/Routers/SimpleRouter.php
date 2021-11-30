<?php

namespace Freyr\DP\Routing\Routers;

use Freyr\DP\Routing\Controllers\RouteController;
use Psr\Http\Message\ServerRequestInterface;

class SimpleRouter
{
    public function matchRoute(ServerRequestInterface $request): Route
    {
        $route = match ($request->getUri()->getPath()) {
            '/' => new Route('test', RouteController::class, 'show'),
        };

        return $route;
    }
}
