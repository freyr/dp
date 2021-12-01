<?php
declare(strict_types=1);

use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Slim\Interfaces\RouteParserInterface;

require __DIR__.'/../vendor/autoload.php';
Dotenv::createUnsafeImmutable(__DIR__.'/../', '.env')->safeLoad();

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(require __DIR__ . '/../config/dependency-injection/services.php');
/** @noinspection PhpUnhandledExceptionInspection */
$container = $containerBuilder->build();
$app = Bridge::create($container);
$container->set(RouteParserInterface::class, $app->getRouteCollector()->getRouteParser());
