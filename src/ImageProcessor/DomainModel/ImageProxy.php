<?php

namespace Freyr\DP\ImageProcessor\DomainModel;

class ImageProxy implements ImageInterface
{
    private bool $initialized = false;
    private ?Image $originalImage = null;

    public function __construct(private string $id, private string $name)
    {
    }

    public function display(): string
    {
        $this->init();
        return $this->originalImage->display();
    }

    public function show(): string
    {
        $this->init();
        return $this->originalImage->show();
    }

    public function show2(): string
    {
        $this->init();
        return $this->originalImage->show2();
    }

    private function init(): void
    {
        if (!$this->initialized) {
            $this->originalImage = new Image($this->id, $this->name);
            $this->initialized = true;
        }
    }


}
