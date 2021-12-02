<?php

declare(strict_types=1);

namespace Freyr\DP\Notifier;

class HtmlFormatter implements NotifierFormatter
{
    public function format(User $user, Basket $basket): string
    {
        return '';
    }
}
