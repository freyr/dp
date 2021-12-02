<?php

namespace Freyr\DP\Refactor;

use Freyr\DP\SimpleLogger;

class Refactor
{

    public function __construct(private SimpleLogger $logger)
    {
    }

    public function test()
    {
        $this->logger->log([]);
    }
}
