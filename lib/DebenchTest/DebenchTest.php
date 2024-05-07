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
        Debench::getInstance(false, $this->theme);
        $this->debench = new Debench(true, $this->theme);
    }

    protected function tearDown(): void
    {
        // Utils::deleteDir($this->debench->getPathUI());
        $this->debench = null;
    }

    protected static function callMethod(object $object, string $name, array $args = [])
    {
        $class = new \ReflectionClass($object);
        $method = $class->getMethod($name);
        return $method->invokeArgs($object, $args);
    }


    public function testConstruct(): void
    {
        $this->assertTrue($this->debench->isEnable());
        $this->assertEquals($this->theme, $this->debench->isEnable());
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
            $this->debench->getPathUI() . PHP_EOL .
                $srcDir . PHP_EOL .
                "The number of files does not match! src: $srcFilesCount, dest: $destFilesCount "
        );
    }

    public function testShutdownFunction(): void
    {
        $output = self::callMethod($this->debench, 'shutdownFunction');
        $this->assertTrue($output);

        $this->debench->setEnable(false);
        $output = self::callMethod($this->debench, 'shutdownFunction');
        $this->assertFalse($output);
    }

    public function testGetCheckPoints(): void
    {
        $this->assertIsArray($this->debench->getCheckPoints());

        // we have one Script and one Debench checkpoints, both happens inside the constructor
        $this->assertCount(2, $this->debench->getCheckPoints());
    }

    public function testMakeTag(): void
    {
        $verify = self::callMethod($this->debench, 'checkTag', ['tag1#']);
        $this->assertFalse($verify);


        $tag = self::callMethod($this->debench, 'makeTag', ['test', 1]);
        $verify = self::callMethod($this->debench, 'checkTag', [$tag]);
        $this->assertTrue($verify);


        $tag = self::callMethod($this->debench, 'makeTag', ['', 2]);
        $verify = self::callMethod($this->debench, 'checkTag', [$tag]);
        $this->assertTrue($verify);
    }

    public function testGetTagName(): void
    {
        $tag = self::callMethod($this->debench, 'makeTag', ['test', 2]);

        $tag = self::callMethod($this->debench, 'getTagName', [$tag]);
        $this->assertEquals('#test', $tag);
    }

    public function testAddCheckPoint(): void
    {
        $this->assertContainsOnlyInstancesOf(CheckPoint::class, $this->debench->getCheckPoints());


        $tag = self::callMethod($this->debench, 'makeTag', ['', 2]);
        self::callMethod($this->debench, 'addCheckPoint', [0, 0, '', 0, $tag]);

        // we have one Script and one Debench checkpoints, both happens inside the constructor
        $this->assertCount(3, $this->debench->getCheckPoints());


        $this->expectException(\Exception::class);
        self::callMethod($this->debench, 'addCheckPoint', [10, 0, '', 0, 'sdf#']);
    }

    public function testNewPoint(): void
    {
        $this->assertContainsOnlyInstancesOf(CheckPoint::class, $this->debench->getCheckPoints());

        $this->debench->newPoint("a new point");
        $this->assertArrayHasKey("a new point" . "#2", $this->debench->getCheckPoints());

        $this->debench::point("a new point");

        $this->debench->setEnable(false);

        $this->debench->newPoint("one more point");

        // we have one Script and one Debench checkpoints, both happens inside the constructor
        $this->assertCount(4, $this->debench->getCheckPoints());
    }

    public function testProcessCheckPoints(): void
    {
        self::callMethod($this->debench, 'processCheckPoints');

        // we have one Script and one Debench checkpoints, both happens inside the constructor
        $time = 0;
        foreach ($this->debench->getCheckPoints() as $checkPoint) {
            $time += $checkPoint->getTimestamp();
        }

        // a big diff between theme means checkpoints not processed
        $this->assertLessThan(5000, $time);
    }

    public function testSetMinimalOnly(): void
    {
        $this->debench->setMinimalOnly(true);
        $this->assertTrue($this->debench->isMinimalOnly());

        Debench::minimalOnly(false);
        $this->assertFalse($this->debench->isMinimalOnly());
    }

    public function testIsEnable(): void
    {
        $this->debench->setEnable(false);
        $this->assertFalse($this->debench->isEnable());

        Debench::enable(true);
        $this->assertTrue($this->debench->isEnable());
    }

    public function testGetLastCheckPointInMS(): void
    {
        self::callMethod($this->debench, 'setLastCheckPointInMS', [10001]);
        $ms = self::callMethod($this->debench, 'getLastCheckPointInMS');
        $this->assertEquals(10001, $ms);
    }

    public function testGetLastCheckPointNumber(): void
    {
        $number = self::callMethod($this->debench, 'getLastCheckPointNumber');
        $this->assertEquals(2, $number);

        $number = self::callMethod($this->debench, 'incrementLastCheckPointNumber', [false]);
        $this->assertEquals(3, $number);
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

    public function testGetExecutionTime(): void
    {
        $this->assertIsInt($this->debench->getExecutionTime());
    }

    public function testGetRequestTime(): void
    {
        $this->assertIsInt($this->debench->getRequestTime());
    }

    public function testGetScriptName(): void
    {
        $this->assertNotEmpty($this->debench->getScriptName());
    }

    public function testGetRequestMethod(): void
    {
        $this->assertNotEmpty($this->debench->getRequestMethod());
    }

    public function testGetResponseCode(): void
    {
        $this->assertIsInt($this->debench->getResponseCode());
    }

    public function testGetCurrentTime(): void
    {
        $this->assertIsInt($this->debench->getCurrentTime());
    }

    public function testGetLoadedFilesCount(): void
    {
        $this->assertGreaterThan(5, $this->debench->getLoadedFilesCount());
    }

    public function testAddException(): void
    {
        $this->debench->addException(new \Exception('oh no'));
        $exceptions = self::callMethod($this->debench, 'getExceptions');
        $this->assertEquals(1, count($exceptions));
    }

    public function testAddAsException(): void
    {
        $this->debench->addAsException(0, '', '', 0);

        $exceptions = self::callMethod($this->debench, 'getExceptions');
        $this->assertEquals(1, count($exceptions));
    }

    public function testMakeOutput(): void
    {
        $this->debench->setMinimalOnly(true);
        $outputMinimal = self::callMethod($this->debench, 'makeOutput');
        $this->assertStringNotContainsString($outputMinimal, '{{@');

        $this->debench->setMinimalOnly(false);
        self::callMethod($this->debench, 'startSession');
        $outputFull = self::callMethod($this->debench, 'makeOutput');

        self::callMethod($this->debench, 'addException', [new \Exception('oh no')]);
        $outputFull = self::callMethod($this->debench, 'makeOutput');
        $this->assertStringNotContainsString($outputFull, '{{@');


        $this->assertNotEquals($outputFull, $outputMinimal);
    }

    public function testMakeOutputLoop(): void
    {
        $pathHtm = dirname(__FILE__, 2) . '/Debench/ui/widget.log.request.post.htm';
        $key = ["key", "aaValue"];

        $message = 'nothing yet';
        $html = self::callMethod($this->debench, 'makeOutputLoop', [$pathHtm, [], $message]);
        $this->assertStringContainsString($html, $message);

        $html = self::callMethod($this->debench, 'makeOutputLoop', [$pathHtm, [$key[0] => $key[1]]]);
        $this->assertStringContainsString($key[1], $html);
    }

    public function testGetInstance(): void
    {
        $this->assertInstanceOf(Debench::class, Debench::getInstance());

        unset($this->debench);
        $this->assertInstanceOf(Debench::class, Debench::getInstance(true, $this->theme));
    }

    public function testWakeup(): void
    {
        $serialized = serialize($this->debench);
        
        $this->expectException(\Exception::class);
        $deserialized = unserialize($serialized);
    }
}
