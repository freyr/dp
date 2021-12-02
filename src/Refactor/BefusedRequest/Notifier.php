<?php

namespace Freyr\DP\Refactor\BefusedRequest;

abstract class SlackNotifier
{

    abstract public function formatMessage(ClientMessage $message): string;
    abstract public function sendMessage(ClientMessage $message): void;
}
