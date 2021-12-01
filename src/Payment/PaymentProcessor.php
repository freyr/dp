<?php

namespace Freyr\DP\Payment;

interface PaymentProcessor
{
    public function transfer(Transaction $transaction): void;
}
