<?php

declare(strict_types=1);

/**
 * @package Debench
 * @link http://github.com/myaaghubi/debench Github
 * @author Mohammad Yaaghubi <m.yaaghubi.abc@gmail.com>
 * @copyright Copyright (c) 2024, Mohammad Yaaghubi
 * @license MIT License
 */

namespace DEBENCH;

class Debench
{
    private array $hype;

    private mixed $checkPoints;
    private int $ramUsageMax;

    private int $lastCheckPointInMS;
    private int $lastCheckPointNumber;

    /**
     * Debench Constructor
     *
     * @return void
     */
    public function __construct(private bool $active = true, private string $ui = 'theme')
    {
        if (!$this->active) {
            return;
        }

        $this->hype = [];

        $this->checkPoints = [];
        $this->ramUsageMax = 0;
        $this->lastCheckPointInMS = 0;
        $this->lastCheckPointNumber = 0;

        $this->newPoint('debench init');

        $this->hype['ui'] = rtrim($ui, '/');

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $this->hype['base'] = dirname(($backtrace[0])['file']);

        register_shutdown_function(function () {
            // to calculate some stuff
            $this->calculateExecutionTime();
            print $this->makeOutput();
        });
    }


    /**
     * Add a new checkpoint
     * 
     * @param  string $tag
     * @return void
     */
    public function newPoint(string $tag = ''): void
    {
        if (!$this->active) {
            return;
        }

        if (empty($tag)) {
            $tag = 'point ' . ($this->lastCheckPointNumber + 1);
        }

        // just trying to separate duplicate tags from each other
        $tag .= '#' . ($this->lastCheckPointNumber + 1);

        $currentTime = $this->getCurrentTime();
        $ramUsage = $this->getRamUsagePeak();

        if ($this->lastCheckPointInMS == 0) {
            $currentTime = $this->getRequestTime();
        }


        // generates a backtrace
        $backtrace = debug_backtrace();
        // shift an element off the beginning of array
        $caller = array_shift($backtrace);

        // get the file address that checkPoint() called from
        $file = $caller['file'];
        // get the line number that checkPoint() called from
        $line = $caller['line'];

        // specify calls from self class
        if (strrpos($caller['file'], __FILE__) !== false) {
            $line = 0;
            $file = '';
        }

        $this->checkPoints[$tag] = new CheckPoint($currentTime, $ramUsage, $file, $line);

        $this->ramUsageMax = max($ramUsage, $this->ramUsageMax);

        $this->lastCheckPointInMS = $currentTime;
        $this->lastCheckPointNumber += 1;
    }


    /**
     * Calculate elapsed time for each checkpoint
     *
     * @return void
     */
    private function calculateExecutionTime(): void
    {
        // may the below loop take some time
        $currentTime = $this->getCurrentTime();

        $prevKey = '';
        $prevCP = null;
        foreach ($this->checkPoints as $key => $cp) {
            if (!empty($prevKey) && $prevCP != null) {
                $this->checkPoints[$prevKey]->setTimestamp($cp->getTimeStamp() - $prevCP->getTimeStamp());
            }

            $prevKey = $key;
            $prevCP = $cp;
        }

        $this->checkPoints[$prevKey]->setTimestamp($currentTime - $prevCP->getTimeStamp());
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
    private function getLastCheckPointInMS(): int
    {
        return $this->lastCheckPointInMS;
    }


    /**
     * Get the last checkpoint number
     *
     * @return int
     */
    private function getLastCheckPointNumber(): int
    {
        return $this->lastCheckPointNumber;
    }


    /**
     * Get checkpoints array
     *
     * @return array<string,object>
     */
    private function getCheckPoints(): mixed
    {
        return $this->checkPoints;
    }

    /**
     * Get the max value of ram usage happened till now
     *
     * @return int
     */
    public function getRamUsageMax(): int
    {
        return $this->ramUsageMax;
    }


    /**
     * Get the real ram usage
     *
     * @return int
     */
    public function getRamUsagePeak(): int
    {
        // true => memory_real_usage
        return memory_get_peak_usage(true);
    }


    /**
     * Get the elapsed time from beginning till now in milliseconds
     *
     * @return int
     */
    public function getExecutionTime(): int
    {
        return $this->getCurrentTime() - $this->getRequestTime();
    }


    /**
     * Get the request time in milliseconds
     *
     * @return int
     */
    public function getRequestTime(): int
    {
        return intval($_SERVER["REQUEST_TIME_FLOAT"] * 1000);
    }


    /**
     * Get the current time in milliseconds
     *
     * @return int
     */
    public function getCurrentTime(): int
    {
        $microtime = microtime(true) * 1000;
        return intval($microtime);
    }


    /**
     * format bytes with KB, MB, etc.
     *
     * @param  int $size
     * @return string
     */
    private function getFormattedBytes(int $size = 0): string
    {
        if ($size == 0) {
            return '0 B';
        }

        $base = log($size, 1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');

        return round(pow(1024, $base - floor($base))) . ' ' . $suffixes[floor($base)];
    }


    /**
     * Get the count of all loaded files in project 
     *
     * @return int
     */
    public function getLoadedFilesCount(): int
    {
        return count(get_required_files());
    }


    /**
     * Get the right tag name to show, just remove the '#' with checkpoint number
     *
     * @param  string $tag
     * @return string
     */
    private function getTagName(string $tag = ''): string
    {
        // return substr($tag, 0, strrpos($tag, '#'));
        return "#" . substr($tag, 0, strrpos($tag, '#'));
    }


    /**
     * Get formatted log
     *
     * @return string
     */
    public function makeOutput(): string
    {
        $fullTime = $this->getExecutionTime() < 1 ? 1 : $this->getExecutionTime();

        $log = '';
        foreach ($this->checkPoints as $key => $cp) {
            $log .= Template::render($this->hype['base'] . '/' . $this->hype['ui'] . '/debench/widget.log.htm', [
                "name" => $this->getTagName($key),
                "path" => $cp->getPath(),
                "lineNumber" => $cp->getLineNumber(),
                "timestamp" => $cp->getTimestamp(),
                "memory" => $this->getFormattedBytes($cp->getMemory()),
                "percent" => round($cp->getTimestamp() / $fullTime * 100),
            ]);
        }

        return Template::render($this->hype['base'] . '/' . $this->hype['ui'] . '/debench/widget.htm', [
            'base' => $this->hype['ui'],
            'ramUsageMax' => $this->getFormattedBytes($this->ramUsageMax),
            'includedFilesCount' => $this->getLoadedFilesCount(),
            'checkPoints' => $this->getLastCheckPointNumber(),
            'log' => $log,
            'fullExecTime' => $fullTime
        ]);
    }
}
