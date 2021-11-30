<?php

namespace Freyr\DP\Routing\Routers\Strategies;

use Freyr\DP\Routing\Controllers\RouteController;
use Freyr\DP\Routing\Routers\Route;
use Psr\Http\Message\ServerRequestInterface;

class AdvancedRouter extends BaseRouter
{
    protected function doMatchRoute(ServerRequestInterface $request): Route
    {
        $route = match ($request->getUri()->getPath()) {
            '/adv' => new Route('test', RouteController::class, 'advancedShow'),
        };

        return $route;
    }

}
