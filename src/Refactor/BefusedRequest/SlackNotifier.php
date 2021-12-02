<?php

namespace Freyr\DP\Refactor\BefusedRequest;

class SlackNotifier
{
    public function __construct(private TextFormatter $formatter)
    {
    }

    public function sendMessage(ClientMessage $message): void
    {
        $this->formatter->formatMessage('');
    }
}
