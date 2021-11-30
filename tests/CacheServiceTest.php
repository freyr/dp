<?php

declare(strict_types=1);

use Freyr\DP\Cache\RedisService;
use PHPUnit\Framework\TestCase;

class CacheServiceTest extends TestCase
{

    /**
     * @test
     */
    public function shouldCorrectlySetCacheByKey()
    {
        $redis = $this->getMockBuilder(Redis::class)->disableOriginalConstructor()->getMock();
        $redis->expects(self::once())->method('set');
        $service = new RedisService($redis);
        $service->set('test', ['a' => 1]);
    }
}
