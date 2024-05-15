<?php

declare(strict_types=1);

/**
 * @package Debench, MessageLevel
 * @link http://github.com/myaaghubi/debench Github
 * @author Mohammad Yaaghubi <m.yaaghubi.abc@gmail.com>
 * @copyright Copyright (c) 2024, Mohammad Yaaghubi
 * @license MIT License
 */

namespace DEBENCH;

enum MessageLevel
{
    case INFO;
    case WARNING;
    case ERROR;
    case DUMP;

    public function color(): string
    {
        return match($this) {
            MessageLevel::INFO => '#aabbcc', // nothing
            MessageLevel::WARNING => '#fff000', // nothing
            MessageLevel::ERROR => '#ff0000', // nothing
            MessageLevel::DUMP => '#aabbcc', // nothing
        };
    }
}
