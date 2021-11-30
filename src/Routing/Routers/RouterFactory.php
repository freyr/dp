<?php

declare(strict_types=1);

namespace Freyr\DP\Routing\Routers;

use Freyr\DP\Routing\Routers\Strategies\AdvancedRouter;
use Freyr\DP\Routing\Routers\Strategies\RouterInterface;
use Freyr\DP\Routing\Routers\Strategies\SimpleRouter;

class RouterFactory
{

    public function __construct()
    {
    }

    public function create(): RouterInterface
    {
        if (getenv('USE_SIMPLE_FIRST')) {
            return new SimpleRouter();
        } else {
            return new SimpleRouter(new AdvancedRouter());
        }
    }
}
