<?php

declare(strict_types=1);
namespace Freyr\DP\Redis;

use Exception;
use Redis;

class CacheService
{
    public function __construct(private Redis $redis)
    {

    }

    public function set(string $key, string|array $data): void
    {
        $serializedData = json_encode($data);
        $this->redis->set($key, $serializedData);
    }

    /**
     * @throws Exception
     */
    public function get(string $key): string|array
    {
        $serializedData = $this->redis->get($key);
        if ($serializedData === false) {
            throw new Exception('no data with key:' . $key);
        }

        return json_decode($serializedData);
    }

}
