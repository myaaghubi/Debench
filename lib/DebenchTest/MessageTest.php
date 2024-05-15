<?php

declare(strict_types=1);

namespace DEBENCH;

use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    protected Message $message;
    protected array $params;
    protected function setUp(): void
    {
        $this->params = [
            'message' => 'teeeest',
            'level' => MessageLevel::INFO,
            'path' => 'test\path\not\real',
            'lineNumber' => 12
        ];
        $this->message = new Message(
            $this->params['message'],
            $this->params['level'],
            $this->params['path'],
            $this->params['lineNumber']
        );
    }

    protected function tearDown(): void
    {
    }

    public function testConstruct(): void
    {
        $this->assertEquals($this->params['message'], $this->message->getMessage());
        $this->assertEquals($this->params['level'], $this->message->getLevel());
        $this->assertEquals($this->params['path'], $this->message->getPath());
        $this->assertEquals($this->params['lineNumber'], $this->message->getLineNumber());
    }

    public function testSetLevel(): void
    {
        $this->message->setLevel(MessageLevel::ERROR);
        $this->assertEquals(MessageLevel::ERROR, $this->message->getLevel());
    }

    public function testSetMemory(): void
    {
        $this->message->setMessage('a test');
        $this->assertEquals('a test', $this->message->getMessage());
    }

    public function testSetPath(): void
    {
        $this->message->setPath('another path');
        $this->assertEquals('another path', $this->message->getPath());
    }

    public function testSetLineNumber(): void
    {
        $this->message->setLineNumber(5);
        $this->assertEquals(5, $this->message->getLineNumber());
    }
}
