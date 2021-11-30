<?php

namespace Freyr\DP\Payment\Domain;

class User
{

    public function __construct(private UserPersistence $repository, private int $userId, private int $amount, private string $status)
    {
    }

    public function spent(int $amount): void
    {
        if ($this->status === 'suspended') {
            return;
        }

        if ($amount > $this->amount) {
            return;
        }

        $this->amount -= $amount;
    }

    public function flush(): void
    {
        $this->repository->store($this->userId, $this->amount, $this->status);
    }


}
