<?php

namespace Freyr\DP\Controllers;

use Freyr\DP\Authorization\AuthorizeServiceFactory;
use Freyr\DP\Cache\CacheFactory;
use Freyr\DP\Db\DbFactory;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\UploadedFileInterface;

class FeatureController
{
    public function getFeature(CacheFactory $cacheFactory)
    {
        $cache = $cacheFactory->create('192.168.1.2', 2342);
        $key = '';
        if ($cache->has($key)) {
            $data = $cache->get($key);
        } else {
            $data = '';
            $cache->set($key, $data);
        }
        return $data;
    }

    public function login(MessageInterface $message)
    {
        $authorizeService = AuthorizeServiceFactory::create($message);
        $result = $authorizeService->authorize($message);
    }

    public function upload(UploadedFileInterface $request)
    {
        $db = DbFactory::create();
        $db->select('',[], null);
    }
}
