<?php

namespace Freyr\DP\Cache;

use Exception;
use Redis;

class Cache
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
