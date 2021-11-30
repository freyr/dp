<?php

namespace Freyr\DP\Authorization;

use Freyr\DP\Authorization\AuthorizeService;
use Psr\Http\Message\MessageInterface;

class LoginPasswdService implements AuthorizeService
{

    /**
     * @param string $db
     */
    public function __construct(string $db)
    {

    }

    public function authorize(MessageInterface $message): bool
    {
    }

}
