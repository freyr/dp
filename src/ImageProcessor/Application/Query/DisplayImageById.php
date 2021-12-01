<?php

namespace Freyr\DP\ImageProcessor\Application\Query;

use Freyr\DP\ImageProcessor\DomainModel\ImageInterface;
use Freyr\DP\ImageProcessor\DomainModel\ImagePathInterface;
use Freyr\DP\ImageProcessor\Infrastructure\ImageProcessorDbReadModel;
use GuzzleHttp\ClientInterface;
use Slim\Psr7\Uri;

class DisplayImageById
{

    public function __construct(private ImageProcessorDbReadModel $imageProcessorDbReadModel, private ClientInterface $client)
    {
    }

    public function execute(string $id): ImageInterface|ImagePathInterface
    {
        $this->client->get(new Uri('http', 'example.com', '80', '/'));
        return $this->imageProcessorDbReadModel->get($id);
    }
}
