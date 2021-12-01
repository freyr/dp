<?php

namespace Freyr\DP\ImageProcessor\Application\Command;

use Freyr\DP\ImageProcessor\Infrastructure\CatalogDbRepository;
use Slim\Logger;

class AddImageToCatalog
{
    public function __construct(CatalogDbRepository $repository, Logger $logger)
    {
        sleep(2);
    }

    public function execute(): void
    {

    }
}
