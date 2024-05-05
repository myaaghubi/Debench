<?php

declare(strict_types=1);

namespace DEBENCH;

use PHPUnit\Framework\TestCase;

class CheckPointTest extends TestCase
{
    protected CheckPoint $checkPoint;
    protected array $params;
    protected function setUp(): void
    {
        $this->params = [
            'timestamp' => 100,
            'memory' => 20,
            'path' => 'test\path\not\real',
            'lineNumber' => 12
        ];
        $this->checkPoint = new CheckPoint(
            $this->params['timestamp'], 
            $this->params['memory'], 
            $this->params['path'], 
            $this->params['lineNumber']
        );
    }

    protected function tearDown(): void
    {
    }

    public function testConstruct(): void
    {
        $this->assertEquals($this->params['timestamp'], $this->checkPoint->getTimestamp());
        $this->assertEquals($this->params['memory'], $this->checkPoint->getMemory());
        $this->assertEquals($this->params['path'], $this->checkPoint->getPath());
        $this->assertEquals($this->params['lineNumber'], $this->checkPoint->getLineNumber());
    }

    public function testSetTimestamp(): void
    {
        $this->checkPoint->setTimestamp(20);
        $this->assertEquals(20, $this->checkPoint->getTimestamp());
    }

    public function testSetMemory(): void
    {
        $this->checkPoint->setMemory(10);
        $this->assertEquals(10, $this->checkPoint->getMemory());
    }

    public function testSetPath(): void
    {
        $this->checkPoint->setPath('another path');
        $this->assertEquals('another path', $this->checkPoint->getPath());
    }

    public function testSetLineNumber(): void
    {
        $this->checkPoint->setLineNumber(5);
        $this->assertEquals(5, $this->checkPoint->getLineNumber());
    }
}
