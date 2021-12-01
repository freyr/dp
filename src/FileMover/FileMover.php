<?php

namespace Freyr\DP\FileMover;

use Freyr\DP\ImageProcessor\DomainModel\FileInterface;

class FileMover
{
    public function move(FileInterface $source, string $target): void
    {
        $source->getFullPath();
    }
}
