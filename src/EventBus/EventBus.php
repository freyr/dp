<?php

namespace Freyr\DP\EventBus;

interface EventBus
{
    public function dispatch(Event $event): void;
}
