<?php

namespace Freyr\DP\Twig;

use Carbon\Carbon;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;
use Twig\Extension\CoreExtension;
use Twig\Extension\DebugExtension;
use Twig\TwigFunction;

class TwigFactory
{
    public static function create(ContainerInterface $container): Twig
    {
        $debug = getenv('DEBUG') && getenv('DEBUG') === 'true';

        $twig = new Twig(
            __DIR__ . '/../resources/views',
            [
                'cache' => $debug ? false : __DIR__ . '/../var/cache/twig',
                'debug' => $debug,
            ]
        );

        /** @var CoreExtension $coreExtension */
        $coreExtension = $twig->getEnvironment()->getExtension(CoreExtension::class);
        $coreExtension->setTimezone('Europe/Warsaw');
        $coreExtension->setDateFormat('Y-m-d H:i:s', '%d days');

        if ($debug) {
            $twig->addExtension(new DebugExtension());
        }

        $twig->getEnvironment()->addGlobal('auth', $container->get(Auth::class));
        $twig->getEnvironment()->addGlobal('flash', $container->get(Messages::class));
        $twig->getEnvironment()->addFunction(
            new TwigFunction('ff', static function (): string {
                return FeatureFlag::show();
            })
        );
        $twig->getEnvironment()->addFunction(
            new TwigFunction(
                'beautify_weekdays',
                static function (string $weekdays): string {
                    $week = [
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                        7 => 'Sunday',
                    ];

                    $result = [];

                    foreach (json_decode($weekdays, true) as $day) {
                        $result[] = $week[$day];
                    }

                    return implode(', ', $result);
                }
            )
        );
        $twig->getEnvironment()->addFunction(
            new TwigFunction(
                'path_for',
                static function (string $routeName, array $options = []) use ($container) {
                    return $container->get(RouteParserInterface::class)->urlFor($routeName, $options);
                }
            )
        );
        $twig->getEnvironment()->addFunction(
            new TwigFunction(
                'asset',
                static function (string $path) {
                    return sprintf(
                        '%s/%s',
                        rtrim(getenv('ATS_PANEL_HOST'), '/'),
                        ltrim($path, '/')
                    );
                }
            )
        );
        $twig->getEnvironment()->addFunction(
            new TwigFunction(
                'time_diff',
                static function (Carbon $firstDate, ?Carbon $secondDate): string {
                    return $firstDate->diffAsCarbonInterval($secondDate)->format('%H:%I:%S');
                }
            )
        );
        $twig->getEnvironment()->addFunction(
            new TwigFunction(
                'json_decode',
                static function (?string $json): array {
                    if ($json !== null) {
                        return json_decode($json, true);
                    }

                    return [];
                }
            )
        );

        return $twig;
    }
}
