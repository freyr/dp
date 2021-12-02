<?php

declare(strict_types=1);

namespace Freyr\DP\Notifier;

interface Notifier
{
    public function purchaseConfirmed(User $user, Basket $basket);
}
