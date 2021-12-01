<?php

namespace Freyr\DP\Parser;

interface VideoParser
{
    public function cut(string $input, int $numberOfSecFromBeginning): string;
}
