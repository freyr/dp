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

class ExtendedTwigBuilder implements TwigBuilderInterface
{
    private TwigInterface $twig;

    public function __construct()
    {
        $this->initialize();
    }

    public function withDateTime(): TwigBuilderInterface
    {
        /** @var CoreExtension $coreExtension */
        $coreExtension = $this->twig->getEnvironment()->getExtension(CoreExtension::class);
        $coreExtension->setTimezone('Europe/Warsaw');
        $coreExtension->setDateFormat('Y-m-d H:i:s', '%d days');

        return $this;
    }

    public function withDebug(): TwigBuilderInterface
    {
        $this->twig->addExtension(new DebugExtension());
        return $this;

    }

    public function withAuth(Auth $auth): TwigBuilderInterface
    {
        $this->twig->getEnvironment()->addGlobal('auth', $auth);
        $this->twig->getEnvironment()->addGlobal('auth', $auth);
        $this->twig->getEnvironment()->addGlobal('auth', $auth);
        return $this;
    }

    public function withFlush(ContainerInterface $container): TwigBuilderInterface
    {
        $this->twig->getEnvironment()->addGlobal('flash', $container->get(Messages::class));

        return $this;
    }

    public function withFeatureFlag(): TwigBuilderInterface
    {
        $this->twig->getEnvironment()->addFunction(
            new TwigFunction('ff', static function (): string {
                return FeatureFlag::show();
            })
        );
        return $this;
    }

    public function withWeekDayNames(): TwigBuilderInterface
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

        return $this;
    }

    public function withTheRest(): TwigBuilderInterface
    {
        $this->twig->getEnvironment()->addFunction(

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
        return $this;
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
        $this->twig = new ExtendedTwig();
    }
}
