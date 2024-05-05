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

    public function testGetOPCacheStatus(): void
    {
        $var = SystemInfo::getOPCacheStatus();
        $this->assertIsString($var);

        $this->assertContains($var, ['On', 'Off', 'Off (Not Loaded)']);
    }
}
