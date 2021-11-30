<?php

namespace Freyr\DP\EventBus;

use JsonSerializable;

abstract class Event implements JsonSerializable
{
    public function __construct(private int $id)
    {
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'name' => static::$name,
            'payload' => $this->payload()
        ];
    }

    public function getId(): int
    {
        return $this->id;
    }

    abstract protected function payload(): array;
}
