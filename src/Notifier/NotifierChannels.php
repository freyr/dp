<?php

namespace Freyr\DP\Notifier;

abstract class NotifierChannels implements Notifier
{
    public function purchaseConfirmed(User $user, Basket $basket)
    {
        if ($user->useText()) {
            $formatter = new TextFormatter();
        } else {
            $formatter = new HtmlFormatter();
        }
        $message = $formatter->format($user, $basket);
        $this->send($message);
    }

    abstract protected function send(string $message):void;
}
