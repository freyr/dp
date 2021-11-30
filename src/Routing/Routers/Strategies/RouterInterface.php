<?php

namespace Freyr\DP\Routing\Routers\Strategies;

use Freyr\DP\Routing\Routers\Route;
use Psr\Http\Message\ServerRequestInterface;

interface RouterInterface
{
    public function matchRoute(ServerRequestInterface $request): Route;
}
