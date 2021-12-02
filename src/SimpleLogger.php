<?php

namespace Freyr\DP;

use Slim\Logger;

class SimpleLogger
{
    public function __construct(private Logger $logger)
    {
    }

    public function log()
    {
        $this->logger->debug();
    }
}
