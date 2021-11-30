<?php

namespace Freyr\DP\Authorization;

use Freyr\DP\Authorization\AuthorizeService;
use Psr\Http\Message\MessageInterface;

class SSOService implements AuthorizeService
{
    public function authorize(MessageInterface $message): bool
    {
    }

}
