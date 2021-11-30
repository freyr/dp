<?php

namespace Freyr\DP\Db;

interface DbInterface
{
    public function select(string $sql, array $params, int $ttl): array;
}
