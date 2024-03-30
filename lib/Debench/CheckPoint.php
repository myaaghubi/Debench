<?php

declare(strict_types=1);

/**
 * @package Debench, CheckPoint
 * @link http://github.com/myaaghubi/debench Github
 * @author Mohammad Yaaghubi <m.yaaghubi.abc@gmail.com>
 * @copyright Copyright (c) 2024, Mohammad Yaaghubi
 * @license MIT License
 */

namespace DEBENCH;

class CheckPoint
{
    /**
     * CheckPoint Constructor
     *
     * @return void
     */
    public function __construct(
        private int $timestamp,
        private int $memory,
        private string $path,
        private int $lineNumber
    ) {
    }


    /**
     * Get the timestamp
     * 
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }


    /**
     * Set the timestamp
     * 
     * @param  int $timestamp
     * @return void
     */
    public function setTimestamp(int $timestamp): void
    {
        $this->timestamp = $timestamp;
    }


    /**
     * Get the memory
     * 
     * @return int
     */
    public function getMemory(): int
    {
        return $this->memory;
    }


    /**
     * Set the memory
     * 
     * @param  int $memory
     * @return void
     */
    public function setMemory(int $memory): void
    {
        $this->memory = $memory;
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