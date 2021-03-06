<?php

namespace Freyr\DP\Routing;

use Freyr\DP\Routing\Routers\Strategies\RouterInterface;
use Freyr\DP\Routing\Routers\Strategies\SimpleRouter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Application
{

    public function __construct(private RouterInterface $router)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $route = $this->router->matchRoute($request);
        $controllerClassName = $route->getControllerClass();
        $controller = new $controllerClassName();
        return call_user_func_array(
            [$controller, $route->getControllerMethod()],
            []
        );
    }
}
