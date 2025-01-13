<?php

namespace Addeos\LaravelTimezone;

use Illuminate\Support\Facades\Cache;

class Helper
{
    public static function getLocalTimeZone()
    {
        $ip = file_get_contents("http://ipecho.net/plain");
        $cacheKey = 'timezone_' . $ip;
        $cacheValue = Cache::get($cacheKey);
        if (!empty($cacheValue)) {
            return $cacheValue;
        }
        $url = 'http://ip-api.com/json/' . $ip;
        $tz = file_get_contents($url);
        $tz = json_decode($tz, true)['timezone'];
        Cache::put($cacheKey, $tz);
        return $tz;
    }
}