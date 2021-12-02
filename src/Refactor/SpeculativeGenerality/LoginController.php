<?php

namespace Freyr\DP\Refactor\SpeculativeGenerality;

class LoginController
{

    public function __construct(private UserService $service)
    {
    }

    public function login()
    {
        $this->service->autohorize();
    }
}
