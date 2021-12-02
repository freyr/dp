<?php

namespace Freyr\DP\Twig;

use Psr\Container\ContainerInterface;
use Slim\Views\Twig;

class TwigDirector
{

    public function __construct(private TwigBuilderInterface $builder, private Auth $auth)
    {
    }

    public function createWithContextOne(): TwigInterface
    {
        return $this->builder
            ->withFeatureFlag()
            ->withAuth($this->auth)
            ->withWeekDayNames()
            ->create();
    }

    public function createWithContextTwo(): TwigInterface
    {
        return $this->builder
            ->withAuth($container)
            ->withWeekDayNames()
            ->withDateTime()
            ->create();
    }

    public function createWithContextThree(ContainerInterface $container): TwigInterface
    {
        return $this->builder
            ->withAuth($container)
            ->withTheRest()
            ->withDebug()
            ->create();
    }
}
