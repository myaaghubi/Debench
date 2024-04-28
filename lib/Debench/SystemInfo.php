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
        return PHP_SAPI;
    }


    /**
     * Is in the cli mode
     * 
     * @return bool
     */
    public static function isCLI(): bool
    {
        return strtolower(self::getSystemAPI()) == 'cli';
    }


    /**
     * Get OPCache status
     * 
     * @return string
     */
    public static function getOPCacheStatus(): string
    {
        if (!function_exists('opcache_get_status') || (!extension_loaded("opcache") && !extension_loaded("Zend OPcache"))) {
            return 'Off (Not Loaded)';
        }

        if (!is_array(opcache_get_status()) || !isset(opcache_get_status()['opcache_enabled'])) {
            return 'Off';
        }

        if (opcache_get_status()['opcache_enabled'] != 1) {
            return 'Off';
        }

        return 'On';
    }
}
