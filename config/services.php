<?php

declare(strict_types=1);

use Freyr\DP\Cache\Cache;
use Freyr\DP\Redis\RedisFactory;

use function DI\autowire;

return [
    Redis::class => static function (): Redis {
        return RedisFactory::withIpAndPort(getenv('REDIS_IP'), (int) getenv('REDIS_PORT'));
    },

    Cache::class => autowire(),
];
