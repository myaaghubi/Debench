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


        register_shutdown_function(function () {
            // to calculate some stuff
            $this->calculateExecutionTime();
        });
    }


    /**
     * Calculate elapsed time for each checkpoint
     *
     * @return void
     */
    public function calculateExecutionTime(): void
    {
        // may the below loop take some time
        $currentTime = $this->getCurrentTime();

        $prevKey = '';
        $prevCP = null;
        foreach ($this->checkPoints as $key => $cp) {
            if (!empty($prevKey) && $prevCP != null) {
                $this->checkPoints[$prevKey]->time = $cp->time - $prevCP->time;
            }

            $prevKey = $key;
            $prevCP = $cp;
        }

        $this->checkPoints[$prevKey]->time = $currentTime - $prevCP->time;
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
    public function getFormattedBytes($size = 0): string
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
    public function getTagName($tag = ''): string
    {
        // return substr($tag, 0, strrpos($tag, '#'));
        return "#".substr($tag, 0, strrpos($tag, '#'));
    }
}
