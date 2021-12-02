<?php

namespace Freyr\DP\Twig;

use Psr\Container\ContainerInterface;

interface TwigBuilderInterface
{
    public function withDateTime(): TwigBuilderInterface;
    public function withDebug(): TwigBuilderInterface;
    public function withAuth(Auth $auth): TwigBuilderInterface;
    public function withFlush(ContainerInterface $container): TwigBuilderInterface;
    public function withFeatureFlag(): TwigBuilderInterface;
    public function withWeekDayNames(): TwigBuilderInterface;
    public function withTheRest(): TwigBuilderInterface;
}
