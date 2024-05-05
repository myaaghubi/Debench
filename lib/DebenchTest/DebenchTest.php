<?php

declare(strict_types=1);

namespace DEBENCH;

use PHPUnit\Framework\TestCase;

class DebenchTest extends TestCase
{
    private ?Debench $debench;
    private string $theme;

    protected function setUp(): void
    {
        $this->theme = 'theme';
        $this->debench = new Debench(true, $this->theme);
    }

    protected function tearDown(): void
    {
        Utils::deleteDir($this->debench->getPathUI());
        $this->debench = null;
    }


    public function testTheTheme(): void
    {
        $this->assertFileExists($this->debench->getPathUI());

        $debenchRef = new \ReflectionClass($this->debench);
        $srcDir = dirname($debenchRef->getFilename()) . '/ui';

        $destFilesCount = count(glob($this->debench->getPathUI() . '/*'));
        $srcFilesCount = count(glob($srcDir . '/*'));

        $this->assertEquals(
            $srcFilesCount,
            $destFilesCount,
            $this->debench->getPathUI().PHP_EOL.
            $srcDir.PHP_EOL.
            "The number of files does not match! src: $srcFilesCount, dest: $destFilesCount "
        );
    }

    public function testGetCheckPoints(): void
    {
        $this->assertIsArray($this->debench->getCheckPoints());

        // we have one Script and one Debench checkpoints, both happens inside the constructor
        $this->assertCount(2, $this->debench->getCheckPoints());
    }

    public function testNewPoint(string $tag = ''): void
    {
        $this->assertContainsOnlyInstancesOf(CheckPoint::class, $this->debench->getCheckPoints());

        $this->debench->newPoint("a new point");
        $this->assertArrayHasKey("a new point" . "#2", $this->debench->getCheckPoints());

        $this->debench::point("a new point");

        // we have one Script and one Debench checkpoints, both happens inside the constructor
        $this->assertCount(4, $this->debench->getCheckPoints());
    }

    public function testSetMinimalOnly(): void
    {
        $this->debench->setMinimalOnly(true);

        $this->assertTrue($this->debench->isMinimalOnly());
    }

    public function testIsEnable(): void
    {
        $this->debench->setEnable(false);

        $this->assertFalse($this->debench->isEnable());
    }

    public function testGetRamUsage(): void
    {
        $this->assertIsString($this->debench->getRamUsage(true));

        $this->assertIsInt($this->debench->getRamUsage(false));
    }

    public function testGetRamUsagePeak(): void
    {
        $this->assertIsString($this->debench->getRamUsagePeak(true));

        $this->assertIsInt($this->debench->getRamUsagePeak(false));
    }

    public function testGetRequestTime(): void
    {
        $this->assertIsInt($this->debench->getRequestTime());
    }

    public function testGetCurrentTime(): void
    {
        $this->assertIsInt($this->debench->getCurrentTime());
    }

    public function getLoadedFilesCount(): int
    {
        $this->assertIsInt($this->debench->getCurrentTime());
    }

    public function testGetInstance(): void
    {
        $this->assertInstanceOf(Debench::class, Debench::getInstance());
    }
}
