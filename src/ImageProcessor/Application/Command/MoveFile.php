<?php

namespace Freyr\DP\ImageProcessor\Application\Command;

use Freyr\DP\FileMover\FileMover;
use Freyr\DP\ImageProcessor\DomainModel\FileInterface;

class MoveFile
{
    public function __construct(private FileMover $fileMover)
    {
    }

    public function execute(FileInterface $file, string $newPath): void
    {
        $this->fileMover->move($file, $newPath);
    }
}
