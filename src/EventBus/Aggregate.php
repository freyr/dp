<?php

declare(strict_types=1);

namespace Freyr\DP\EventBus;

abstract class Aggregate
{
    public function __construct(protected int $id, private EventBus $bus)
    {
    }

    protected function recordThat(Event $event): void
    {
        $this->bus->dispatch($event);
    }

    abstract protected function apply(Event $event): void;
}
