<?php

declare(strict_types=1);

namespace Freyr\DP\Scheduler\DomainModel\Events;

use DateTime;
use Freyr\DP\EventBus\Event;

class JobWasScheduled extends Event
{
    protected static string $name = 'job.scheduled';

    public function __construct(
        int $id,
        private DateTime $timeOfSchedule,
        private int $schedulerId,
        private string $target,
    ) {
        parent::__construct($id);
    }

    public static function fromArray(array $data): JobWasScheduled
    {
        return new self(
            $data['id'],
            $data['timeOfSchedule'],
            $data['schedulerId'],
            $data['target'],
        );
    }

    protected function payload(): array
    {
        return [
            'timeOfSchedule' => $this->timeOfSchedule,
            'schedulerId' => $this->schedulerId,
            'target' => $this->target,
        ];
    }

    public function getTimeOfSchedule(): DateTime
    {
        return $this->timeOfSchedule;
    }

    public function getSchedulerId(): int
    {
        return $this->schedulerId;
    }

    public function getTarget(): string
    {
        return $this->target;
    }
}
