<?php

namespace Freyr\DP\Http\Controller;

use Slim\Psr7\Request;
use Slim\Psr7\Response;

class ImageController
{
    public function showImage(Request $request, Response $response, string $id): Response
    {
        $result['path'] = $request->getUri()->getPath();
        $response->getBody()->write(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)->withHeader('Content-type', 'application/json');
    }
}
