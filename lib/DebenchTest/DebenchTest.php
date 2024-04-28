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
        $this->debench = null;
        self::deleteDir(__DIR__ . '/' . $this->theme);
    }


    public function testCheckUI(): void
    {
        $theme = __DIR__ . '/' . $this->theme;

        $this->assertFileExists($theme);


        $themeFilesCount = glob($theme . '/debench/*');
        $uiFilesCount = glob(__DIR__ . '/../Debench/ui/*');

        $this->assertEquals(count($themeFilesCount), count($uiFilesCount), "The number of files in the theme folder does not match with the ui dir!");
    }

    public function testGetCheckPoints(): void
    {
        $this->assertIsArray($this->debench->getCheckPoints());

        // we have one initial checkpoint, happens inside the constructor
        $this->assertCount(1, $this->debench->getCheckPoints());
    }

    public function testNewPoint(string $tag = ''): void
    {
        $this->assertContainsOnlyInstancesOf(CheckPoint::class, $this->debench->getCheckPoints());

        $this->debench->newPoint("a new point");
        $this->assertArrayHasKey("a new point" . "#2", $this->debench->getCheckPoints());

        $this->debench::point("a new point");

        // we have one initial checkpoint, happens inside of the class
        $this->assertCount(3, $this->debench->getCheckPoints());
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

    private static function deleteDir(string $dir): void
    {
        $glob = glob($dir);
        foreach ($glob as $g) {
            if (!is_dir($g)) {
                unlink($g);
            } else {
                self::deleteDir("$g/*");
                rmdir($g);
            }
        }
    }
}
