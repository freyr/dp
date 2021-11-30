<?php

namespace Freyr\DP\Payment\Repository;

use Freyr\DP\Payment\Domain\User;
use Freyr\DP\Payment\Domain\UserPersistence;

class UserRepository implements UserPersistence, UserRepositoryInterface
{
    public function getById(int $userId): User
    {
        $sql = '';
        $data = [];
        return new User($userId, $data['current_amount'], $data['status']);
    }

    public function store($userId, $amount, $status)
    {
        $sql = 'update users set amount = :amount, status = :status where user_id = :userId';
    }
}
