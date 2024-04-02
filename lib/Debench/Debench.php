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
    private array $checkPoints;
    private string $path;

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

        $this->checkPoints = [];
        $this->lastCheckPointInMS = 0;
        $this->lastCheckPointNumber = 0;

        $initCP = $this->newPoint('debench');
        $this->initPointMS = $initCP->getTimestamp();

        $this->ui = rtrim($ui, '/');

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $this->path = dirname(($backtrace[0])['file']);

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
        $basePath = $this->path;

        $uiPath = $basePath . '/' . $this->ui;
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
        $ramUsage = $this->getRamUsage();

        if (empty($tag)) {
            $tag = 'point ' . ($this->lastCheckPointNumber + 1);
        }

        // to avoid duplicate tags(keys)
        $tag .= '#' . ($this->lastCheckPointNumber + 1);
        
        $dbc = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $dbcIndex = 0;

        // specify calls from self class
        if (strrpos(($dbc[$dbcIndex])['file'], __FILE__) !== false) {
            $dbcIndex = 1;
        }
        
        $file = ($dbc[$dbcIndex])['file'];
        $line = ($dbc[$dbcIndex])['line'];

        $checkPoint = new CheckPoint($currentTime, $ramUsage, $file, $line);
        $this->checkPoints[$tag] = $checkPoint;

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
     * Get the ram usage
     *
     * @param  bool $formatted
     * @return int|string
     */
    public function getRamUsage(bool $formatted=false): int|string
    {
        // true => memory_real_usage
        $peak = memory_get_usage();

        if ($formatted)
            return Utils::toFormattedBytes($peak);

        return $peak;
    }


    /**
     * Get the real ram usage (peak)
     *
     * @param  bool $formatted
     * @return int|string
     */
    public function getRamUsagePeak(bool $formatted=false): int|string
    {
        // true => memory_real_usage
        $peak = memory_get_peak_usage(true);

        if ($formatted)
            return Utils::toFormattedBytes($peak);

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
            $log .= Template::render($this->path . '/' . $this->ui . '/debench/widget.log.htm', [
                "name" => $this->getTagName($key),
                "path" => $cp->getPath(),
                "lineNumber" => $cp->getLineNumber(),
                "timestamp" => $cp->getTimestamp(),
                "memory" => Utils::toFormattedBytes($cp->getMemory()),
                "percent" => round($cp->getTimestamp() / ($eTime>1?$eTime:1) * 100),
            ]);
        }

        return Template::render($this->path . '/' . $this->ui . '/debench/widget.htm', [
            'base' => $this->ui,
            'ramUsagePeak' => $this->getRamUsagePeak(true),
            'ramUsage' => $this->getRamUsage(true),
            'includedFilesCount' => $this->getLoadedFilesCount(),
            'checkPoints' => $this->getLastCheckPointNumber(),
            'preloadTime' => $this->initPointMS - $this->getRequestTime(),
            'log' => $log,
            'fullExecTime' => $eTime
        ]);
    }
}