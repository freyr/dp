<?php

declare(strict_types=1);

use Dotenv\Store\StoreBuilder;
use Freyr\DP\Http\Controller\ImageController;
use Freyr\DP\Http\GuzzleLoggerDecorator;
use Freyr\DP\ImageProcessor\Application\Command\AddImageToCatalog;
use Freyr\DP\ImageProcessor\Application\Query\DisplayImageById;

use Freyr\DP\ImageProcessor\Infrastructure\CatalogDbRepository;
use Freyr\DP\SimpleLogger;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Container\ContainerInterface;
use Slim\Logger;

use function DI\autowire;

return [
    Logger::class => autowire(),
    SimpleLogger::class => autowire(),
    DisplayImageById::class => autowire(),
    CatalogDbRepository::class => autowire(),
    AddImageToCatalog::class => DI\create(AddImageToCatalog::class)->lazy(),
    ImageController::class => autowire(),
    StoreBuilder::class => autowire(),
    Client::class => DI\create(Client::class)->lazy(),
    ClientInterface::class => function(ContainerInterface $container): ClientInterface {
        return new GuzzleLoggerDecorator($container->get(Client::class), $container->get(SimpleLogger::class));
    }
];
