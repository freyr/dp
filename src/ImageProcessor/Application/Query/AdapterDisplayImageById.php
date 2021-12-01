<?php

namespace Freyr\DP\ImageProcessor\Application\Query;

use Freyr\DP\ImageProcessor\DomainModel\FileInterface;
use Freyr\DP\ImageProcessor\DomainModel\ImageForFileInterfaceAdapter;
use Freyr\DP\ImageProcessor\DomainModel\ImageInterface;
use Freyr\DP\ImageProcessor\DomainModel\ImagePathInterface;
use Freyr\DP\ImageProcessor\Infrastructure\ImageProcessorDbReadModel;
use GuzzleHttp\ClientInterface;
use Slim\Psr7\Uri;

class AdapterDisplayImageById
{

    public function __construct(private ImageProcessorDbReadModel $imageProcessorDbReadModel, private ClientInterface $client)
    {
    }

    public function execute(string $id): FileInterface
    {
        $this->client->get(new Uri('http', 'example.com', '80', '/'));
        $image = $this->imageProcessorDbReadModel->get($id);
        return new ImageForFileInterfaceAdapter($image);
    }
}
