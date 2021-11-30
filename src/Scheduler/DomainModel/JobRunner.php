<?php

namespace Freyr\DP\Scheduler\DomainModel;

interface JobRunner
{
    public function run(string $target, string $command): void;
}
