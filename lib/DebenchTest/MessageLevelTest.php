<?php

declare(strict_types=1);

namespace DEBENCH;

use PHPUnit\Framework\TestCase;

class MessageLevelTest extends TestCase
{
    public function testCases(): void
    {
        $this->assertSame([
            MessageLevel::INFO,
            MessageLevel::WARNING,
            MessageLevel::ERROR,
            MessageLevel::DUMP
        ], MessageLevel::cases());
    }

    public function testName(): void
    {
        $this->assertIsString(MessageLevel::INFO->name());
    }
}
