<?php declare(strict_types=1);

/**
 * @package Debench
 * @link http://github.com/myaaghubi/debench Github
 * @author Mohammad Yaaghubi <m.yaaghubi.abc@gmail.com>
 * @copyright Copyright (c) 2024, Mohammad Yaaghubi
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3
 */

class Debench
{
    private string $base;

    private mixed $checkPoints;
    private int $ramUsageMax;
    
    private int $lastCheckPointInMS;
    private int $lastCheckPointNumber;

    /**
     * Debench Constructor
     *
     * @return void
     */
    function __construct(private bool $active=true)
    {
        if (!$this->active) {
            return;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $this->base = dirname(($backtrace[0])['file']);

        // CheckPoints[]
        $this->checkPoints = [];
        $this->ramUsageMax = 0;
        $this->lastCheckPointInMS = 0;
        $this->lastCheckPointNumber = 0;
    }


    /**
     * Is Debench enable
     *
     * @return bool
     */
    public function isEnable(): bool
    {
        return $this->active;
    }


    /**
     * Get the last checkpoint in milliseconds
     *
     * @return int
     */
    public function getLastCheckPointInMS(): int
    {
        return $this->lastCheckPointInMS;
    }


    /**
     * Get the last checkpoint number
     *
     * @return int
     */
    public function getLastCheckPointNumber(): int
    {
        return $this->lastCheckPointNumber;
    }


    /**
     * Get checkpoints array
     *
     * @return array<string,object>
     */
    public function getCheckPoints(): mixed
    {
        return $this->checkPoints;
    }
}
