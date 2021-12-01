<?php

namespace Freyr\DP\ImageProcessor\DomainModel;

interface ImagePathInterface
{
    public function getPath(): string;
    public function getFileName(): string;
    public function getExtension(): string;
}
