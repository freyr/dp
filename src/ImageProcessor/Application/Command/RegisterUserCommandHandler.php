<?php

namespace Freyr\DP\ImageProcessor\Application\Command;

use Freyr\DP\Bus\Command;
use Freyr\DP\Bus\CommandHandler;
use Freyr\DP\Bus\RegisterUserCommand;

class RegisterUserCommandHandler extends CommandHandler
{
    public function execute(Command $command): void
    {
        /** @var RegisterUserCommand $command */

    }


    public function getCommand(): string
    {
        return RegisterUserCommand::class;
    }
}
