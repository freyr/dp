<?php

namespace Freyr\DP\Twig;

use Carbon\Carbon;
use DI\Container;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;
use Twig\Extension\CoreExtension;
use Twig\Extension\DebugExtension;
use Twig\TwigFunction;

class TwigBuilder implements TwigBuilderInterface
{
    private TwigInterface $twig;

    public function __construct()
    {
        $this->initialize();
    }

    public function withDateTime(): TwigBuilder
    {
        /** @var CoreExtension $coreExtension */
        $coreExtension = $this->twig->getEnvironment()->getExtension(CoreExtension::class);
        $coreExtension->setTimezone('Europe/Warsaw');
        $coreExtension->setDateFormat('Y-m-d H:i:s', '%d days');
    }

    public function withDebug(): TwigBuilder
    {
        $this->twig->addExtension(new DebugExtension());
    }

    public function withAuth(Auth $auth): TwigBuilder
    {
        $this->twig->getEnvironment()->addGlobal('auth', $auth);
    }

    public function withFlush(ContainerInterface $container): TwigBuilder
    {
        $this->twig->getEnvironment()->addGlobal('flash', $container->get(Messages::class));
    }

    public function withFeatureFlag(): TwigBuilder
    {
        $this->twig->getEnvironment()->addFunction(
            new TwigFunction('ff', static function (): string {
                return FeatureFlag::show();
            })
        );
        return $this;
    }

    public function withWeekDayNames(): TwigBuilder
    {
        $this->twig->getEnvironment()->addFunction(
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
    }

    public function withTheRest(): TwigBuilder
    {
        $this->twig->getEnvironment()->addFunction(
            new TwigFunction(
                'path_for',
                static function (string $routeName, array $options = []) use ($container) {
                    return $container->get(RouteParserInterface::class)->urlFor($routeName, $options);
                }
            )
        );
        $this->twig->getEnvironment()->addFunction(
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
        $this->twig->getEnvironment()->addFunction(
            new TwigFunction(
                'time_diff',
                static function (Carbon $firstDate, ?Carbon $secondDate): string {
                    return $firstDate->diffAsCarbonInterval($secondDate)->format('%H:%I:%S');
                }
            )
        );
        $this->twig->getEnvironment()->addFunction(
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
    }

    public function create(): TwigInterface
    {
        $twig = clone $this->twig;
        $this->initialize();

        return $twig;
    }

    private function initialize(): void
    {
        $debug = getenv('DEBUG') && getenv('DEBUG') === 'true';
        $this->twig = new Twig([
            __DIR__ . '/../resources/views',
            [
                'cache' => $debug ? false : __DIR__ . '/../var/cache/twig',
                'debug' => $debug,
            ]
        ]);
    }
}
