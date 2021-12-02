<?php

namespace Freyr\DP\Refactor\BefusedRequest;

class MessageCreator
{
    public function __construct(private TextFormatter $formatter)
    {
    }
    public function persist(string $message): void
    {
        $message = $this->formatter->formatMessage($message);
        // persystencja
    }
}
