<?php

namespace Freyr\DP\Routing\Routers;

use Freyr\DP\Routing\Routers\Strategies\RouterInterface;

class RouterCollection implements \Iterator
{

    private array $items = [];

    public function add(RouterInterface $router) {
        $this->items[] = $router;
    }

    public function current()
    {
        return current($this->items);
    }

    public function next()
    {
        next($this->items);
        return current($this->items);
    }

    public function key()
    {
        return key($this->items);
    }

    public function valid()
    {
        return current($this->items) === false;
    }

    public function rewind()
    {
        reset($this->items);
    }
}
