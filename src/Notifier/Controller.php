<?php

namespace Freyr\DP\Notifier;

class Controller
{
    public function __construct(private Notifier $notifier)
    {
    }

    public function confirm()
    {
        $this->notifier->purchaseConfirmed(new User(), new Basket());
    }
}
