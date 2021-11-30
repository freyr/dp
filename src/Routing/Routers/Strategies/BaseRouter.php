<?php

declare(strict_types=1);

namespace Freyr\DP\Routing\Routers\Strategies;

use Freyr\DP\Routing\Routers\Route;
use Psr\Http\Message\ServerRequestInterface;
use UnhandledMatchError;

abstract class BaseRouter implements RouterInterface
{
    public function __construct(protected ?RouterInterface $nextRouter = null)
    {
    }

    public function matchRoute(ServerRequestInterface $request): Route
    {
        try {
            $route = $this->doMatchRoute($request);
        } catch (UnhandledMatchError $exception) {
            $route = $this->nextRouter?->matchRoute($request);
            if ($route === null) {
                throw $exception;
            }
        }

        return $route;
    }

    abstract protected function doMatchRoute(ServerRequestInterface $request);
}
