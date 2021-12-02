<?php

declare(strict_types=1);

namespace Freyr\DP\Refactor\SpeculativeGenerality;

class UserService
{
    public function __construct(private UserRepository $repository)
    {
    }

    public function autohorize()
    {
        $this->repository->getUser();
        $this->formatMessage('sdfsf');
    }


}
