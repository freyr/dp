<?php

namespace Freyr\DP\Payment\Domain;

interface UserPersistence
{
    public function store($userId, $amount, $status);
}
