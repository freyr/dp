<?php

namespace Freyr\DP\ImageProcessor\DomainModel;

interface ImageInterface
{
    public function display(): string;

    public function show(): string;
    public function show2(): string;
}
