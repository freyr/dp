<?php

declare(strict_types=1);

namespace Freyr\DP\Bus;

class CommandBus
{
    /** @var CommandHandler[][] */
    private array $commandHandlers;

    public function __construct()
    {
    }

    public function dispatch(Command $command): void
    {
        foreach ($this->commandHandlers[get_class($command)] as $commandHandler) {
            $commandHandler->execute($command);
        }
    }

    public function observe(CommandHandler $commandHandler)
    {
        $this->commandHandlers[$commandHandler->getCommand()][] = $commandHandler;
    }
}
