<?php

declare(strict_types=1);

namespace Freyr\DP\ImageProcessor\Infrastructure;

use Freyr\DP\ImageProcessor\DomainModel\ImageInterface;
use Freyr\DP\ImageProcessor\DomainModel\ImagePathInterface;
use Freyr\DP\ImageProcessor\DomainModel\ImageProxy;

class ImageProcessorDbReadModel
{
    public function get(string $id): ImageInterface|ImagePathInterface
    {
        return new ImageProxy($id, 'name');
    }
}
