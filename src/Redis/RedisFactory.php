<?php

namespace Freyr\DP\Redis;

use Redis;

class RedisFactory
{
    private function __construct()
    {
    }

    public static function withIpAndPort(string $ip, int $port): Redis
    {
        $redis = new Redis();
        $redis->connect($ip, $port);
        return $redis;
    }
}
