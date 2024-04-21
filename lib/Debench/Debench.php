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
    private bool $minimal;
    private array $checkPoints;

    private int $initPointMS;
    private int $endPointMS;
    private int $lastCheckPointInMS;
    private int $lastCheckPointNumber;

    private static ?Debench $instance = null;

    /**
     * Debench constructor
     *
     * @param  bool $enable
     * @param  string $ui
     * @param  string $path
     * @return void
     */
    public function __construct(private bool $enable = true, private string $ui = 'theme', private string $path = '')
    {
        if (!$this->enable) {
            return;
        }

        $this->minimal = false;
        $this->checkPoints = [];
        $this->lastCheckPointInMS = 0;
        $this->lastCheckPointNumber = 0;

        $this->newPoint('debench');

        $this->ui = rtrim($ui, '/');

        if (empty($path)) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $this->path = dirname(($backtrace[0])['file']);
        }

        // check for UI
        $this->checkUI();

        register_shutdown_function(function () {
            if (!$this->enable) {
                return;
            }
            $this->calculateExecutionTime();
            print $this->makeOutput();
        });

        self::$instance = $this;
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
    public function newPoint(string $tag = ''): void
    {
        if (!$this->enable) {
            return;
        }

        $currentTime = $this->getCurrentTime();
        if (empty($this->initPointMS)) {
            $this->initPointMS = $currentTime;
        }

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
     * Set Debench to only minimal mode
     *
     * @param  bool $minimalMode
     * @return void
     */
    public function setMinimal(bool $minimalMode): void
    {
        $this->minimal = $minimalMode;
    }


    /**
     * Is Debench enable
     *
     * @return bool
     */
    public function isEnable(): bool
    {
        return $this->enable;
    }


    /**
     * Set Debench enable
     *
     * @param  bool $enable
     * @return void
     */
    public function setEnable(bool $enable): void
    {
        $this->enable = $enable;
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
    public function getRamUsage(bool $formatted = false): int|string
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
    public function getRamUsagePeak(bool $formatted = false): int|string
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

        // ------- the minimal widget
        if ($this->minimal) {
            return Template::render($this->path . '/' . $this->ui . '/debench/widget.minimal.htm', [
                'base' => $this->ui,
                'ramUsage' => $this->getRamUsage(true),
                'includedFilesCount' => $this->getLoadedFilesCount(),
                'fullExecTime' => $eTime
            ]);
        }

        // ------- infoLog
        $infoLog = Template::render($this->path . '/' . $this->ui . '/debench/widget.log.info.htm', [
            "phpVersion" => SystemInfo::getPHPVersion(),
            "opcache" => SystemInfo::getOPCacheStatus()?'On':'Off',
            "systemAPI" => SystemInfo::getSystemAPI(),
        ]);

        // ------- timeLog
        $timeLog = '';
        foreach ($this->checkPoints as $key => $cp) {
            $timeLog .= Template::render($this->path . '/' . $this->ui . '/debench/widget.log.checkpoint.htm', [
                "name" => $this->getTagName($key),
                "path" => $cp->getPath(),
                "fileName" => basename($cp->getPath()),
                "lineNumber" => $cp->getLineNumber(),
                "timestamp" => $cp->getTimestamp(),
                "memory" => Utils::toFormattedBytes($cp->getMemory()),
                "percent" => round($cp->getTimestamp() / ($eTime > 1 ? $eTime : 1) * 100),
            ]);
        }

        // ------- logRequest
        $logRequest = '';
        foreach ($_REQUEST as $key => $value) {
            $logRequest .= Template::render($this->path . '/' . $this->ui . '/debench/widget.log.request.htm', [
                "key" => $key,
                "value" => $value
            ]);
        }

        if (!$_REQUEST) {
            $logRequest = 'No <i>$_REQUEST</i> Yet!';
        }

        // ------- logSession
        if (session_status() != PHP_SESSION_ACTIVE) {
            session_start();

        $logSession = '';
        foreach ($_SESSION as $key => $value) {
            $logSession .= Template::render($this->path . '/' . $this->ui . '/debench/widget.log.request.htm', [
                "key" => $key,
                "value" => $value
            ]);
        }

        if (!$_SESSION) {
            $logSession = 'No <i>$_SESSION</i> Yet!';
        }

        // ------- the main widget
        return Template::render($this->path . '/' . $this->ui . '/debench/widget.htm', [
            'base' => $this->ui,
            'ramUsagePeak' => $this->getRamUsagePeak(true),
            'ramUsage' => $this->getRamUsage(true),
            'includedFilesCount' => $this->getLoadedFilesCount(),
            'preloadTime' => $this->initPointMS - $this->getRequestTime(),
            'request' => count($_REQUEST ?? []),
            'requestLog' => $logRequest,
            'session' => count($_SESSION ?? []),
            'sessionLog' => $logSession,
            'infoLog' => $infoLog,
            'timeLog' => $timeLog,
            'fullExecTime' => $eTime
        ]);
    }


    /**
     * Add a new checkpoint
     * 
     * @param  string $tag
     * @return object
     */
    public static function point(string $tag = ''): void
    {
        self::getInstance()->newPoint($tag);
    }


    /**
     * Gets the instance
     * 
     * @param  bool $enable
     * @param  string $ui
     * @return Debench
     */
    public static function getInstance($enable = true, string $ui = 'theme'): Debench
    {
        if (self::$instance === null) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $path = dirname(($backtrace[0])['file']);

            self::$instance = new self($enable, $ui, $path);
        }

        return self::$instance;
    }


    /**
     * Prevent from being unserialized
     * 
     * @return void
     */
    public function __wakeup(): void
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}