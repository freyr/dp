<?php

namespace Freyr\DP\Notifier;

interface NotifierFormatter
{
    public function format(User $user, Basket $basket): string;
}
