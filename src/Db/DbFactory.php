<?php

namespace Freyr\DP\Db;

class DbFactory
{
    public  static function create(): DbInterface
    {
        if (getenv('USE_CACHE')) {
            return new CachedDb();
        } else {
            return new Db();
        }
    }
}
