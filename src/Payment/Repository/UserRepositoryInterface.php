<?php

namespace Freyr\DP\Payment\Repository;

use Freyr\DP\Payment\Domain\User;

interface UserRepositoryInterface
{
    public function getById(int $userId): User;
}
