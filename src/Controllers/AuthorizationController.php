<?php

namespace Freyr\DP\Controllers;

use Freyr\DP\Authorization\AuthorizeServiceFactory;
use Psr\Http\Message\MessageInterface;

class AuthorizationController
{
    public function login(MessageInterface $message)
    {
        $authorizeService = AuthorizeServiceFactory::create($message);
        $result = $authorizeService->authorize($message);
    }
}
