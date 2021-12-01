<?php

namespace Freyr\DP\Parser;

use Freyr\DP\LegacyParser\Parser;

class LegacyParserFacade implements VideoParser
{


    private $initialized = false;
    private ?Parser $parser = null;

    public function cut(string $input, int $numberOfSecFromBeginning): string
    {
        $this->initParser();
        $this->parser->parseWithSingle('');
        $this->parser->parseWithSingle();
        $this->parser->parseWithSingle3();
        $this->parser->parseWithSingle4();
        $output = $this->parser->parseWithSingle5();

        return $output;
    }

    private function initParser(): Parser
    {
        if ($this->initialized === false) {
            $this->parser = new Parser();
            $this->initialized = true;
        }

    }
}
