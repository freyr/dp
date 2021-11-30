<?php

namespace Freyr\DP\Routing\Controllers;

use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Response;

class RouteController
{
    public function show(): ResponseInterface
    {
        return new Response(202, null, (new StreamFactory())->createStream('Hello World'));
    }
}
