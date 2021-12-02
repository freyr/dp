<?php

namespace Freyr\DP\Http\Controller;

use Freyr\DP\Bus\CommandBus;
use Freyr\DP\Bus\RegisterUserCommand;
use Freyr\DP\ImageProcessor\Application\Command\AddImageToCatalogCommandHandler;
use Freyr\DP\ImageProcessor\Application\Command\MoveFile;
use Freyr\DP\ImageProcessor\Application\Query\AdapterDisplayImageById;
use Freyr\DP\ImageProcessor\Application\Query\DisplayImageById;
use Freyr\DP\Parser\VideoParser;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class ImageController
{

    public function __construct(
        private DisplayImageById $displayImageById,
        private AddImageToCatalogCommandHandler $addImageToCatalog,
        private AdapterDisplayImageById $adapterDisplayImageById,
        private MoveFile $moveFile,
        private VideoParser $videoParser,
        private CommandBus $bus
    )
    {
    }


    public function registerUser(Request $request, Response $response, string $id): Response
    {
        $params = $request->getBody()->getContents();
        $email = $params['email'];
        $name = $params['name'];
        $passwd = $params['passwd'];

        $command = new RegisterUserCommand($email, $name, $passwd);
        $this->bus->dispatch($command);
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

    public function moveFile(Request $request, Response $response, string $id, string $target): Response
    {
        $image = $this->adapterDisplayImageById->execute($id);
        $this->moveFile->execute($image, $target);

        $response->getBody()->write(json_encode('slow add catalog', JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)->withHeader('Content-type', 'application/json');
    }

    public function convertVideo(Request $request, Response $response, string $id, string $target): Response
    {
        $this->videoParser->cut();

        $response->getBody()->write(json_encode('slow add catalog', JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)->withHeader('Content-type', 'application/json');
    }
}
