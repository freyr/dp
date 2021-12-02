<?php

declare(strict_types=1);

use Dotenv\Store\StoreBuilder;
use Freyr\DP\Bus\CommandBus;
use Freyr\DP\Http\Controller\ImageController;
use Freyr\DP\Http\GuzzleLoggerDecorator;
use Freyr\DP\ImageProcessor\Application\Command\AddImageToCatalogCommandHandler;
use Freyr\DP\ImageProcessor\Application\Command\RegisterUserCommandHandler;
use Freyr\DP\ImageProcessor\Application\Query\DisplayImageById;

use Freyr\DP\ImageProcessor\Infrastructure\CatalogDbRepository;
use Freyr\DP\LegacyParser\Parser;
use Freyr\DP\Parser\LegacyParserFacade;
use Freyr\DP\Parser\SuperVideoParserFacade;
use Freyr\DP\Parser\VideoParser;
use Freyr\DP\SimpleLogger;
use Freyr\DP\SuperVideoParser;
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
    AddImageToCatalogCommandHandler::class => DI\create(AddImageToCatalogCommandHandler::class)->lazy(),
    ImageController::class => autowire(),
    StoreBuilder::class => autowire(),
    Client::class => DI\create(Client::class)->lazy(),
    ClientInterface::class => function (ContainerInterface $container): ClientInterface {
        return new GuzzleLoggerDecorator($container->get(Client::class), $container->get(SimpleLogger::class));
    },
    LegacyParserFacade::class => autowire(),
    VideoParser::class => function (ContainerInterface $container): VideoParser {
        return $container->get(SuperVideoParserFacade::class);
    },
    RegisterUserCommandHandler::class => autowire(),
    CommandBus::class => function (ContainerInterface $container) {
        $bus = new CommandBus();
        $bus->observe($container->get(RegisterUserCommandHandler::class));
    }
];
