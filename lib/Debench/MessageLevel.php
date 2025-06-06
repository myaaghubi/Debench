<?php

declare(strict_types=1);

/**
 * @package Debench, MessageLevel
 * @link http://github.com/myaaghubi/debench Github
 * @author Mohammad Yaaghubi <m.yaaghubi.abc@gmail.com>
 * @copyright Copyright (c) 2025, Mohammad Yaaghubi
 * @license MIT License
 */

namespace DEBENCH;

enum MessageLevel
{
    case INFO;
    case WARNING;
    case ERROR;
    case DUMP;

    public function name(): string
    {
        return match ($this) {
            MessageLevel::INFO => 'Info',
            MessageLevel::WARNING => 'Warning',
            MessageLevel::ERROR => 'Error',
            MessageLevel::DUMP => 'Dump',
        };
    }
}
