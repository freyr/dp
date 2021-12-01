<?php

declare(strict_types=1);

namespace Freyr\DP\ImageProcessor\DomainModel;

class Image implements ImageInterface
{
    public function __construct(private string $id, private string $name)
    {
        sleep(1);
    }

    public function display(): string
    {
        return 'Image: ' . $this->id;
    }

    public function show(): string
    {
        return $this->name;
    }

    public function show2(): string
    {
        return '4';
    }
}
