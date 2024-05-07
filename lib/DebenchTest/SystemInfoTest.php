<?php

declare(strict_types=1);

namespace DEBENCH;

use PHPUnit\Framework\TestCase;

class SystemInfoTest extends TestCase
{
    protected function tearDown(): void
    {
    }

    public function testGetPHPVersion(): void
    {
        $var = SystemInfo::getPHPVersion();
        $this->assertIsString($var);
    }

    public function testGetSystemAPI(): void
    {
        $var = SystemInfo::getSystemAPI();
        $this->assertIsString($var);
    }

    public function testIsCLI(): void
    {
        $var = SystemInfo::isCLI();
        $this->assertTrue($var);
    }

    public static bool $extension_loaded = false;
    public static bool $function_exists = false;
    public static bool $opcache_get_status = false;

    public function testGetOPCacheStatus(): void
    {
        self::$extension_loaded = false;
        self::$function_exists = false;
        self::$opcache_get_status = false;

        $this->assertEquals('Off (Not Loaded)', SystemInfo::getOPCacheStatus());

        self::$extension_loaded = true;
        $this->assertEquals('Off', SystemInfo::getOPCacheStatus());

        self::$function_exists = true;
        $this->assertEquals('Off', SystemInfo::getOPCacheStatus());

        self::$opcache_get_status = true;
        $this->assertEquals('On', SystemInfo::getOPCacheStatus());
    }
}

// for mocking
function opcache_get_status()
{
    return ['opcache_enabled' => SystemInfoTest::$opcache_get_status];
}

// for mocking
function function_exists()
{
    return SystemInfoTest::$function_exists;
}

// for mocking
function extension_loaded()
{
    return SystemInfoTest::$extension_loaded;
}
