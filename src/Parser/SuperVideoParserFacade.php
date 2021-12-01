<?php

namespace Freyr\DP\Parser;

use Freyr\DP\LegacyParser\Parser;
use Freyr\DP\SuperVideoParser;

class SuperVideoParserFacade implements VideoParser
{
    private $initialized = false;
    private ?SuperVideoParser $parser = null;

    public function cut(string $input, int $numberOfSecFromBeginning): string
    {
        $this->initParser();
        $this->parser->change($numberOfSecFromBeginning);
    }

    private function initParser(): void
    {
        if ($this->initialized === false) {
            $this->parser = new SuperVideoParser();
            $this->initialized = true;
        }
    }
}
