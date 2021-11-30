<?php

declare(strict_types=1);

namespace Freyr\DP\Scheduler\DomainModel;

use DateTime;
use DateTimeZone;
use Exception;
use Freyr\DP\EventBus\Aggregate;
use Freyr\DP\EventBus\Event;
use Freyr\DP\EventBus\EventBus;
use Freyr\DP\Scheduler\DomainModel\Events\JobWasCreated;
use Freyr\DP\Scheduler\DomainModel\Events\JobWasScheduled;

class Job extends Aggregate
{
    private ?DateTime $timeOfSchedule;
    private string $command;

    public function __construct(private JobRunner $jobRunner, int $id, string $command, EventBus $bus)
    {
        parent::__construct($id, $bus);
        $event = new JobWasCreated($id, $command);
        $this->recordThat($event);
    }

    protected function apply(Event $event): void
    {
        match (get_class($event)) {
            JobWasScheduled::class => $this->onJobWasScheduled($event),
            JobWasCreated::class => $this->onJobWasCreated($event),
        };
    }

    private function onJobWasScheduled(Event|JobWasScheduled $event): void
    {
        $this->timeOfSchedule = $event->getTimeOfSchedule();
    }

    private function onJobWasCreated(Event|JobWasCreated $event)
    {
        $this->command = $event->getCommand();
    }

    public function schedule(string $target, int $schedulerId): void
    {
        $scheduleTime = new DateTime('now', new DateTimeZone('UTC'));
        $this->jobRunner->run($target, $this->command);
        $event = new JobWasScheduled($this->id, $scheduleTime, $schedulerId, $target);
        $this->recordThat($event);
    }
}
