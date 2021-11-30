<?php

namespace Freyr\DP\Routing\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Response;

class RouteController
{
    public function show(): ResponseInterface
    {
        $factory = new StreamFactory();

        return new Response(202, null, $factory->createStream('test'));
    }
}
