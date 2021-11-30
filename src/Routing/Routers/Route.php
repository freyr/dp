<?php

namespace Freyr\DP\Routing\Routers;

class Route
{

    public function __construct(private string $name, private string $controllerClass, private string $controllerMethod)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getControllerClass(): string
    {
        return $this->controllerClass;
    }

    public function getControllerMethod(): string
    {
        return $this->controllerMethod;
    }
}
