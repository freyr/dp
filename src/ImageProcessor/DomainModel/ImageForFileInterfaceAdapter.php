<?php

declare(strict_types=1);

namespace Freyr\DP\ImageProcessor\DomainModel;

class ImageForFileInterfaceAdapter implements FileInterface
{

    public function __construct(private ImagePathInterface $image)
    {
    }

    public function getFullPath(): string
    {
        return $this->image->getPath() . $this->image->getFileName() . $this->image->getExtension();
    }
}
