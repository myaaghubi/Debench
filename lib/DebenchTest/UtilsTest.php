<?php

declare(strict_types=1);

namespace DEBENCH;

use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{
    protected function tearDown(): void
    {
    }

    public function testGetBacktrace(): void
    {
        $bt = Utils::getBacktrace();
        $this->assertIsArray($bt);
        $this->assertGreaterThan(1, count($bt));
        $this->assertStringNotContainsString(__DIR__, $bt[0]['file']);
    }

    public function testDeleteDir(): void
    {

        $destDir = __DIR__ . '/copydest';
        @mkdir($destDir . '/test/test', 0777, true);

        Utils::deleteDir($destDir);
        $this->assertFileDoesNotExist($destDir, "This dir should not be exists.");
    }

    public function testCopyDir(): void
    {
        $destDir = __DIR__ . '/copydest';

        Utils::deleteDir($destDir);
        $this->assertFileDoesNotExist($destDir, "This dir should not be exists.");

        $this->expectException(\Exception::class);
        Utils::copyDir('this/path/doesnt/exists', $destDir);


        $templateRef = new \ReflectionClass(Utils::class);
        $srcDir = dirname($templateRef->getFilename()) . '/ui';

        Utils::copyDir($srcDir, $destDir);
        $this->assertFileExists($destDir, "This dir $destDir should be exists.");


        $srcFilesCount = count(glob($srcDir . '/*'));
        $destFilesCount = count(glob($destDir . '/*'));

        $this->assertEquals(
            $srcFilesCount,
            $destFilesCount,
            $srcDir . PHP_EOL .
                $destDir . PHP_EOL .
                "The number of files does not match! src: $srcFilesCount, dest: $destFilesCount "
        );

        Utils::deleteDir($destDir);
    }

    public function testToFormattedBytes(): void
    {
        // [bytes:int, roundUnderMB:bool, expectedResult:string]
        $expects = [
            [0, false, "0 B"],
            [10, false, "10 B"],
            [1024 + 100, false, "1.1 KB"],
            [1024 + 100, true, "1 KB"],
            [1024 * 1024 + 200 * 1024, false, "1.2 MB"],
            [1024 * 1024 + 200 * 1024, true, "1.2 MB"],
        ];

        foreach ($expects as $item) {
            $fb = Utils::toFormattedBytes($item[0], $item[1]);
            $this->assertEquals($item[2], $fb);
        }
    }

    public function testIsInTestMode(): void
    {
        $phpunit = $_SERVER['argv'][0];
        // != phpunit
        $_SERVER['argv'][0] = '';
        $this->assertFalse(Utils::isInTestMode());

        $_SERVER['argv'][0] = $phpunit;
        $this->assertTrue(Utils::isInTestMode());

        @define('PHPUNIT_COMPOSER_INSTALL', 1);
        @define('__PHPUNIT_PHAR__', 1);
        $this->assertTrue(Utils::isInTestMode());
    }
}
