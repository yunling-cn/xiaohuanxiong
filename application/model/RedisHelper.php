<?php


namespace app\model;


class RedisHelper
{
    public static function GetInstance()
    {
        bind('redis','\Redis');
        $redis = app('redis');
        $redis->connect(config('cache.host'), config('cache.port'));
        if (!empty(config('cache.password'))){
            $redis->auth(config('cache.password'));
        }
        return $redis;
    }
}