<?php

namespace Freyr\DP\Bus;

abstract class CommandHandler
{
    abstract public function execute(Command $command): void;

    abstract public function getCommand(): string;
}
