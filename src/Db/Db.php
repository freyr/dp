<?php

namespace Freyr\DP\Db;

class Db implements DbInterface
{

    public function select(string $sql, array $params, ?int $ttl = 100): array
    {
        return [];
    }
}
