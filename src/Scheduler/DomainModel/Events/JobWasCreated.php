<?php

declare(strict_types=1);

namespace Freyr\DP\Scheduler\DomainModel\Events;

use Freyr\DP\EventBus\Event;

class JobWasCreated extends Event
{
    public function __construct(
        int $id,
        private string $command
    ) {
        parent::__construct($id);
    }

    public static function fromArray(array $data): JobWasCreated
    {
        return new self(
            $data['id'],
            $data['command']
        );
    }

    protected function payload(): array
    {
        return [
            'command' => $this->command
        ];
    }

    public function getCommand(): string
    {
        return $this->command;
    }
}
