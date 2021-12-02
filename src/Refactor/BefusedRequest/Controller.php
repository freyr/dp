<?php

declare(strict_types=1);

namespace Freyr\DP\Refactor\BefusedRequest;

class Controller
{
    public function __construct(private MessageCreator $creator)
    {
    }

    public function show()
    {
        $this->creator->sendMessage(new ClientMessage(''));
    }
}
