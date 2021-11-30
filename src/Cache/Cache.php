<?php

namespace Freyr\DP\Cache;

interface Cache
{
    public function set(string $key, string|array $data): void;

    public function get(string $key): string|array;

    public function has(string $key): bool;
}
