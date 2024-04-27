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
    private static bool $enable;
    private static string $ui;
    private static string $path;

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
    public function __construct(bool $enable = null, string $ui = null, string $path = null)
    {
        self::$enable = $enable ?? true;
        self::$ui = $ui ? rtrim($ui, '/') : 'theme';
        self::$path = $path ?? '';

        if (!self::$enable) {
            return;
        }

        $this->minimal = false;
        $this->checkPoints = [];
        $this->lastCheckPointInMS = 0;
        $this->lastCheckPointNumber = 0;

        $this->newPoint('debench');

        if (empty($path)) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            self::$path = dirname(($backtrace[0])['file']);
        }

        // check for UI
        $this->checkUI();


        if (PHP_SAPI !== 'cli' && session_status() != PHP_SESSION_ACTIVE) {
            @session_start();
        }

        register_shutdown_function(function () {
            if (!self::$enable) {
                return;
            }

            if (Utils::isInTestMode()) {
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
        $basePath = self::$path;

        $uiPath = $basePath . '/' . self::$ui;
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
        if (!self::$enable) {
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

        $dbc = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $dbcIndex = 0;

        // specify calls from self class
        while (count($dbc) > $dbcIndex && strrpos(($dbc[$dbcIndex])['file'], __FILE__) !== false) {
            $dbcIndex += 1;
        }

        $file = ($dbc[$dbcIndex])['file'];
        $line = ($dbc[$dbcIndex])['line'];

        if (strrpos($file, __FILE__) !== false) {
            $file = '-';
            $line = '-';
        }

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
     * Is Debench in minimal mode
     *
     * @return bool
     */
    public function isMinimal(): bool
    {
        return $this->minimal;
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
        return self::$enable;
    }


    /**
     * Set Debench enable
     *
     * @param  bool $enable
     * @return void
     */
    public function setEnable(bool $enable): void
    {
        self::$enable = $enable;
    }


    /**
     * Set Debench enable
     *
     * @param  bool $enable
     * @return void
     */
    public static function enable(bool $enable): void
    {
        self::$enable = $enable;
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
    public function getCheckPoints(): array
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
            return Template::render(self::$path . '/' . self::$ui . '/debench/widget.minimal.htm', [
                'base' => self::$ui,
                'ramUsage' => $this->getRamUsage(true),
                'includedFilesCount' => $this->getLoadedFilesCount(),
                'fullExecTime' => $eTime
            ]);
        }

        // ------- infoLog
        $infoLog = Template::render(self::$path . '/' . self::$ui . '/debench/widget.log.info.htm', [
            "phpVersion" => SystemInfo::getPHPVersion(),
            "opcache" => SystemInfo::getOPCacheStatus() ? 'On' : 'Off',
            "systemAPI" => SystemInfo::getSystemAPI(),
        ]);

        // ------- timeLog
        $timeLog = '';
        foreach ($this->checkPoints as $key => $cp) {
            $timeLog .= Template::render(self::$path . '/' . self::$ui . '/debench/widget.log.checkpoint.htm', [
                "name" => $this->getTagName($key),
                "path" => $cp->getPath(),
                "fileName" => basename($cp->getPath()),
                "lineNumber" => $cp->getLineNumber(),
                "timestamp" => $cp->getTimestamp(),
                "memory" => Utils::toFormattedBytes($cp->getMemory()),
                "percent" => round($cp->getTimestamp() / ($eTime > 1 ? $eTime : 1) * 100),
            ]);
        }


        // ------- logPost
        $logPost = $this->makeOutputLoop(self::$path . '/' . self::$ui . '/debench/widget.log.request.post.htm', $_POST, false);

        // ------- logGet
        $logGet = $this->makeOutputLoop(self::$path . '/' . self::$ui . '/debench/widget.log.request.get.htm', $_GET, false);

        // ------- logCookie
        $logCookie = $this->makeOutputLoop(self::$path . '/' . self::$ui . '/debench/widget.log.request.cookie.htm', $_COOKIE, false);

        if (empty($logPost . $logGet . $logCookie)) {
            $logPost = '<b>Nothing</b> Yet!';
        }

        // ------- logSession
        $logSession = '<b>CLI</b> mode!';
        if (PHP_SAPI !== 'cli') {
            $logSession = $this->makeOutputLoop(self::$path . '/' . self::$ui . '/debench/widget.log.request.session.htm', $_SESSION);
        }


        // ------- the main widget
        return Template::render(self::$path . '/' . self::$ui . '/debench/widget.htm', [
            'base' => self::$ui,
            'ramUsagePeak' => $this->getRamUsagePeak(true),
            'ramUsage' => $this->getRamUsage(true),
            'includedFilesCount' => $this->getLoadedFilesCount(),
            'preloadTime' => $this->initPointMS - $this->getRequestTime(),
            'request' => count($_POST) + count($_GET) + count($_COOKIE),
            'logPost' => $logPost,
            'logGet' => $logGet,
            'logCookie' => $logCookie,
            'session' => count($_SESSION ?? []),
            'sessionLog' => $logSession,
            'infoLog' => $infoLog,
            'timeLog' => $timeLog,
            'fullExecTime' => $eTime
        ]);
    }


    /**
     * Make formatted output in loop, $key => $value
     *
     * @return string
     */
    private function makeOutputLoop(string $theme, array $data, string|false $message = ''): string
    {
        if (empty($data)) {
            if ($message === false) {
                return '';
            }
            return empty($message) || !is_string($message) ? '<b>Nothing</b> Yet!' : $message;
        }

        $output = '';

        foreach ($data as $key => $value) {
            $output .= Template::render($theme, [
                "key" => $key,
                "value" => $value
            ]);
        }

        return $output;
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
    public static function getInstance(bool $enable = null, string $ui = null): Debench
    {
        if (self::$instance === null) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
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
