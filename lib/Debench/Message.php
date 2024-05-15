<?php

declare(strict_types=1);

/**
 * @package Debench, Message
 * @link http://github.com/myaaghubi/debench Github
 * @author Mohammad Yaaghubi <m.yaaghubi.abc@gmail.com>
 * @copyright Copyright (c) 2024, Mohammad Yaaghubi
 * @license MIT License
 */

namespace DEBENCH;

class Message
{
    /**
     * Message Constructor
     * 
     * @param  string $message
     * @param  MessageLevel $level
     * @param  string $path
     * @param  int $lineNumber
     * @return void
     */
    public function __construct(
        private string $message,
        private MessageLevel $level,
        private string $path,
        private int $lineNumber
    ) {
    }


    /**
     * Get the message
     * 
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }


    /**
     * Set the message
     * 
     * @param  string $message
     * @return void
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }


    /**
     * Get the level
     * 
     * @return MessageLevel
     */
    public function getLevel(): MessageLevel
    {
        return $this->level;
    }


    /**
     * Set the level
     * 
     * @param  MessageLevel $level
     * @return void
     */
    public function setLevel(MessageLevel $level): void
    {
        $this->level = $level;
    }


    /**
     * Get the path
     * 
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }


    /**
     * Set the path
     * 
     * @param  string $path
     * @return void
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }


    /**
     * Get the line number
     * 
     * @return int
     */
    public function getLineNumber(): int
    {
        return $this->lineNumber;
    }


    /**
     * Set the line number
     * 
     * @param  int $lineNumber
     * @return void
     */
    public function setLineNumber(int $lineNumber): void
    {
        $this->lineNumber = $lineNumber;
    }
}
