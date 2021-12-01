<?php

namespace Freyr\DP;

class SimpleLogger
{
    public function log($data): void
    {
        echo json_encode($data);
    }
}
