<?php

namespace Freyr\DP\Db;

class CachedDb extends Db
{

    public function select(string $sql, array $params, ?int $ttl = 100): array
    {
        $data = [];
        $key = md5($sql);
        $isMiss = false;
        if ($isMiss) {
            $data = parent::select($sql, $params);
            // zapis do cache
        }

        return $data;
    }
}
