<?php

namespace Freyr\DP\Authorization;

use Psr\Http\Message\MessageInterface;

interface AuthorizeService
{
    public function authorize(MessageInterface $message): bool;
}
