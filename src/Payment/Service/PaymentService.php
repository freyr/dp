<?php

namespace Freyr\DP\Payment\Service;

use Freyr\DP\Payment\Repository\UserRepositoryInterface;

class PaymentService
{
    public function __construct(private UserRepositoryInterface $repository)
    {
    }

    public function registerPayment(int $amount, int $userId): void
    {
        $user = $this->repository->getById($userId);
        $user->spent($amount);
        $user->flush();
    }
}
