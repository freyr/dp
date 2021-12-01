<?php

namespace Freyr\DP\Checkout;

use Freyr\DP\Payment\PaymentProcessor;
use Freyr\DP\Payment\Transaction;

class CheckoutController
{
    public function __construct(private PaymentProcessor $paymentProcessor)
    {
    }

    public function checkout()
    {
        $transaction = new Transaction();
        $this->paymentProcessor->transfer($transaction);
    }
}
