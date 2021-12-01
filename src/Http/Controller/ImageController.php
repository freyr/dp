<?php

namespace Freyr\DP\Http\Controller;

use Freyr\DP\ImageProcessor\Application\Command\AddImageToCatalog;
use Freyr\DP\ImageProcessor\Application\Query\DisplayImageById;
use Psr\Container\ContainerInterface;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class ImageController
{

    public function __construct(private DisplayImageById $displayImageById, private AddImageToCatalog $addImageToCatalog)
    {
    }

    public function showImage(Request $request, Response $response, string $id): Response
    {
        $image = $this->displayImageById->execute($id);
        $response->getBody()->write(json_encode('fast', JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)->withHeader('Content-type', 'application/json');
    }

    public function addCatalog(Request $request, Response $response, string $name): Response
    {
        $this->addImageToCatalog->execute($name);
        //
        $response->getBody()->write(json_encode('slow add catalog', JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)->withHeader('Content-type', 'application/json');
    }
}
