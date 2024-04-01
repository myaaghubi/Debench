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

    private array $checkPoints;
    private int $ramUsageMax;

    private int $initPointMS;
    private int $endPointMS;
    private int $lastCheckPointInMS;
    private int $lastCheckPointNumber;

    /**
     * Debench constructor
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

        $initCP = $this->newPoint('debench init');
        $this->initPointMS = $initCP->getTimestamp();

        $this->hype['ui'] = rtrim($ui, '/');

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $this->hype['base'] = dirname(($backtrace[0])['file']);

        // check for UI
        $this->checkUI();

        register_shutdown_function(function () {
            // to calculate some stuff
            $this->calculateExecutionTime();
            print $this->makeOutput();
        });
    }


    /**
     * Copy the template from ui dir into your webroot dir if
     * it doesn't exist
     *
     * @return void
     */
    public function checkUI(): void
    {
        $currentPath = __DIR__;
        $basePath = $this->hype['base'];

        $uiPath = $basePath . '/' . $this->hype['ui'];
        $uiPathFull = $uiPath . '/debench';

        // for assets
        if (!is_dir($uiPath)) {
            if (!is_dir($basePath) || !is_writable($basePath)) {
                throw new \Exception("Directory not exists or not writable! `$basePath` ", 500);
            }

            @mkdir($uiPath);
        }

        // for assets
        if (!is_dir($uiPathFull)) {
            Utils::copyDir($currentPath . '/ui', $uiPathFull);
        }
    }


    /**
     * Add a new checkpoint
     * 
     * @param  string $tag
     * @return object
     */
    public function newPoint(string $tag = ''): object
    {
        $currentTime = $this->getCurrentTime();
        $ramUsage = $this->getRamUsagePeak();

        if (empty($tag)) {
            $tag = 'point ' . ($this->lastCheckPointNumber + 1);
        }

        // just trying to separate duplicate tags from each other
        $tag .= '#' . ($this->lastCheckPointNumber + 1);


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

        $checkPoint = new CheckPoint($currentTime, $ramUsage, $file, $line);
        $this->checkPoints[$tag] = $checkPoint;

        $this->ramUsageMax = max($ramUsage, $this->ramUsageMax);

        $this->lastCheckPointInMS = $currentTime;
        $this->lastCheckPointNumber += 1;

        return $checkPoint; 
    }


    /**
     * Calculate elapsed time for each checkpoint
     *
     * @return void
     */
    private function calculateExecutionTime(): void
    {
        // may the below loop take some time
        $this->endPointMS = $this->getCurrentTime();

        $prevKey = '';
        $prevCP = null;
        foreach ($this->checkPoints as $key => $cp) {
            if (!empty($prevKey) && $prevCP != null) {
                $this->checkPoints[$prevKey]->setTimestamp($cp->getTimestamp() - $prevCP->getTimestamp());
            }

            $prevKey = $key;
            $prevCP = $cp;
        }

        $this->checkPoints[$prevKey]->setTimestamp($this->endPointMS - $prevCP->getTimestamp());
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
    private function getCheckPoints(): array
    {
        if (!$this->checkPoints) {
            return [];
        }
        return $this->checkPoints;
    }


    /**
     * Get the max value of ram usage happened till now
     *
     * @param  bool $formatted
     * @return int|string
     */
    public function getRamUsageMax(bool $formatted=false): int|string
    {
        if ($formatted)
            return $this->getFormattedBytes($this->ramUsageMax);

        return $this->ramUsageMax;
    }


    /**
     * Get the real ram usage
     *
     * @param  bool $formatted
     * @return int|string
     */
    public function getRamUsagePeak(bool $formatted=false): int|string
    {
        // true => memory_real_usage
        $peak = memory_get_peak_usage(true);

        if ($formatted)
            return $this->getFormattedBytes($peak);

        return $peak;
    }


    /**
     * Get the elapsed time from beginning till now in milliseconds
     *
     * @return int
     */
    public function getExecutionTime(): int
    {
        // what about loads before Debench such as composer !?
        // return $this->getCurrentTime() - $this->getRequestTime();
        return $this->endPointMS - $this->initPointMS;
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
     * Get the count of all loaded files 
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
     * Make formatted output
     *
     * @return string
     */
    private function makeOutput(): string
    {
        $eTime = $this->getExecutionTime();

        $log = '';
        foreach ($this->checkPoints as $key => $cp) {
            $log .= Template::render($this->hype['base'] . '/' . $this->hype['ui'] . '/debench/widget.log.htm', [
                "name" => $this->getTagName($key),
                "path" => $cp->getPath(),
                "lineNumber" => $cp->getLineNumber(),
                "timestamp" => $cp->getTimestamp(),
                "memory" => $this->getFormattedBytes($cp->getMemory()),
                "percent" => round($cp->getTimestamp() / ($eTime>1?$eTime:1) * 100),
            ]);
        }

        return Template::render($this->hype['base'] . '/' . $this->hype['ui'] . '/debench/widget.htm', [
            'base' => $this->hype['ui'],
            'ramUsageMax' => $this->getRamUsageMax(true),
            'includedFilesCount' => $this->getLoadedFilesCount(),
            'checkPoints' => $this->getLastCheckPointNumber(),
            'log' => $log,
            'fullExecTime' => $eTime
        ]);
    }
}