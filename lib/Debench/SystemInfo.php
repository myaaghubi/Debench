<?php

declare(strict_types=1);

/**
 * @package Debench, SystemInfo
 * @link http://github.com/myaaghubi/debench Github
 * @author Mohammad Yaaghubi <m.yaaghubi.abc@gmail.com>
 * @copyright Copyright (c) 2024, Mohammad Yaaghubi
 * @license MIT License
 */

namespace DEBENCH;

class SystemInfo
{
    /**
     * Get the php version
     * 
     * @return string
     */
    public static function getPHPVersion(): string
    {
        return PHP_VERSION;
    }


    /**
     * Get the system api
     * 
     * @return string
     */
    public static function getSystemAPI(): string
    {
        return php_sapi_name();
    }


    /**
     * Get OPCache status
     * 
     * @return string
     */
    public static function getOPCacheStatus(): bool
    {
        return function_exists('opcache_get_status') && is_array(opcache_get_status()) && opcache_get_status()['opcache_enabled'] == true;
    }
}