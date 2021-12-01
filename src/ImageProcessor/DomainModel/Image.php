<?php

declare(strict_types=1);

namespace Freyr\DP\ImageProcessor\DomainModel;

class Image implements ImageInterface, ImagePathInterface
{
    public function __construct(private string $id, private string $name)
    {
        sleep(1);
    }

    public function getPath(): string
    {
        return 'path';
    }

    public function getFileName(): string
    {
        return 'noname';
    }

    public function getExtension(): string
    {
        return '.jpg';
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
