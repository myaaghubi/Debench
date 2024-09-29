<?php

declare(strict_types=1);

namespace DEBENCH;

use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    protected function tearDown(): void {}

    public function testMakeUI(): void
    {
        $templateRef = new \ReflectionClass(Template::class);
        $srcDir = dirname($templateRef->getFilename()) . '/ui';

        $destDir = dirname(__FILE__, 3) . '/copydest';

        Utils::deleteDir($destDir);
        $this->assertFileDoesNotExist($destDir, "This dir should not be exists.");

        Template::makeUI($destDir);
        $this->assertFileExists($destDir, "This dir should be exists.");


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
        Template::makeUI($destDir);
        Utils::deleteDir($destDir);
    }

    public function testRender(): void
    {
        $pathHtm = dirname(__FILE__, 2) . '/Debench/ui/widget.log.info.htm';
        $key = ["phpVersion", "x.x.x"];

        $html = Template::render($pathHtm, [$key[0] => $key[1]]);

        $this->assertIsString($html);

        $this->assertStringContainsString($key[1], $html);

        $this->assertMatchesRegularExpression("/{{@$key[0]}}/", file_get_contents($pathHtm));

        $this->expectException(\Exception::class);
        $html = Template::render('this\path\doesnt\exists', []);
    }
}