<?php

namespace Freyr\DP\Notifier;

class TextFormatter implements NotifierFormatter
{
    public function format(User $user, Basket $basket): string
    {
        return ' ';
    }
}
