<?php

namespace Freyr\DP\Authorization;

use Freyr\DP\Authorization\LoginPasswdService;
use Freyr\DP\Authorization\SSOService;
use Psr\Http\Message\MessageInterface;

class AuthorizeServiceFactory
{

    public static function create(MessageInterface $message)
    {
        if ($message->hasHeader('token_sso')) {
            return new SSOService();
        } else {
            return new LoginPasswdService();
        }
    }
}
