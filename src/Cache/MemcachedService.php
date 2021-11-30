<?php

declare(strict_types=1);
namespace Freyr\DP\Cache;

use Exception;

class MemcachedService implements Cache
{
    public function __construct(private $memcached)
    {

    }

    public function has(string $key): bool
    {
        // TODO: Implement hat() method.
    }


    public function set(string $key, string|array $data): void
    {
        $serializedData = json_encode($data);
        //
    }

    /**
     * @throws Exception
     */
    public function get(string $key): string|array
    {
        $serializedData = '';
        if ($serializedData === false) {
            throw new Exception('no data with key:' . $key);
        }

        return json_decode($serializedData);
    }

}
