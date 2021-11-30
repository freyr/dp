<?php

namespace Freyr\DP\Cache;

class CacheFactory
{
    private static array $instances = [];
    public function __construct(private string $param)
    {
    }

    public function create(string $ip, int $port): Cache
    {
        if ($this->param === 'feature') {
            $key = $ip . $port;
            if (!array_key_exists($key, self::$instances)) {
                $redis = new \Redis();
                $redis->connect($ip, $port);
                $instance = new RedisService($redis);
                self::$instances[$key] = $instance;
            }

            return self::$instances[$key];
        } else {
            return new MemcachedService('memcache');
        }
    }
}
