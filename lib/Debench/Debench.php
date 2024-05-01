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
    private static string $pathCalled;
    private static string $pathUI;
    private static bool $minimalOnly;

    private array $checkPoints;
    private array $exceptions;

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
     * @return void
     */
    public function __construct(bool $enable = null, string $ui = null)
    {
        self::$enable = $enable ?? true;
        self::$ui = $ui ? rtrim($ui, '/') : 'theme';
        self::$pathCalled = dirname((Utils::getBacktrace()[0])['file']);
        self::$pathUI = self::$pathCalled . '/' . self::$ui . '/debench';

        if (!self::$enable) {
            return;
        }

        // make sure about the theme
        Template::makeUI(self::$pathUI);

        // Script initial point
        $this->addScriptPoint('Script');

        // Debench initial point
        $this->newPoint('Debench');

        set_exception_handler([$this, 'addException']);

        register_shutdown_function(function () {
            if (!self::$enable || Utils::isInTestMode()) {
                return;
            }

            $this->processCheckPoints();

            print $this->makeOutput();
        });

        self::$instance = $this;
    }


    /**
     * Run session_start()
     * 
     * @return void
     */
    private function startSession(): void
    {
        if (!SystemInfo::isCLI() && session_status() != PHP_SESSION_ACTIVE) {
            session_start();
        }
    }


    /**
     * Add a new item in checkpoint[]
     * 
     * @param  int $currentTime
     * @param  int $memory
     * @param  string $path
     * @param  int $lineNumber
     * @param  string $key
     * @param  bool $addAtFirst
     * @return void
     */
    private function addCheckPoint(int $currentTime, int $memory, string $path, int $lineNumber, string $key = '', bool $addAtFirst = false): void
    {
        if (empty($key)) {
            throw new \Exception("The `key` can't be empty!");
        }

        if (!$this->checkTag($key)) {
            throw new \Exception("The `key` is not in the right format!");
        }

        $checkPoint = new CheckPoint($currentTime, $memory, $path, $lineNumber);

        if (!isset($this->checkPoints)) {
            $this->checkPoints = [];
        }

        if ($addAtFirst) {
            $this->checkPoints = [$key => $checkPoint] + $this->checkPoints;
            return;
        }

        $this->checkPoints[$key] = $checkPoint;
    }


    /**
     * Add the first checkpoint
     *
     * @param  string $tag
     * @return void
     */
    private function addScriptPoint(string $tag = ''): void
    {
        $time = $this->getRequestTime();
        $path = $this->getScriptName();
        $tag = $this->makeTag($tag, $this->incrementLastCheckPointNumber(true));

        // add a check point as preload
        $this->addCheckPoint($time, 0, $path, 0, $tag);
    }


    /**
     * Add a new checkpoint
     * 
     * @param  string $tag
     * @return void
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

        // debug_backtrace
        $debugBT = Utils::getBacktrace()[0];

        $file = $debugBT['file'];
        $line = $debugBT['line'];

        if (strrpos($file, __FILE__) !== false) {
            $file = '-';
            $line = '-';
        }


        $tag = $this->makeTag($tag, $this->incrementLastCheckPointNumber(true));

        $this->addCheckPoint($currentTime, $ramUsage, $file, $line, $tag);

        $this->setLastCheckPointInMS($currentTime);
    }


    /**
     * Calculate elapsed time for each checkpoint
     *
     * @return void
     */
    private function processCheckPoints(): void
    {
        // depends on the the array, it may the below loop take some time to process
        $this->endPointMS = $this->getCurrentTime();

        $prevKey = '';
        $prevCP = null;

        foreach ($this->getCheckPoints() as $key => $cp) {
            if (!empty($prevKey) && $prevCP != null) {
                $diff = $cp->getTimestamp() - $prevCP->getTimestamp();
                $this->checkPoints[$prevKey]->setTimestamp($diff);
            }

            $prevKey = $key;
            $prevCP = $cp;
        }

        $diff = $this->endPointMS - $prevCP->getTimestamp();
        $this->checkPoints[$prevKey]->setTimestamp($diff);
    }


    /**
     * Is Debench in minimal mode
     *
     * @return bool
     */
    public function isMinimalOnly(): bool
    {
        return self::$minimalOnly ?? false;
    }


    /**
     * Set Debench to only minimal mode
     *
     * @param  bool $minimalModeOnly
     * @return void
     */
    public function setMinimalOnly(bool $minimalModeOnly): void
    {
        self::$minimalOnly = $minimalModeOnly;
    }


    /**
     * Set Debench to only minimal mode
     *
     * @param  bool $minimalModeOnly
     * @return void
     */
    public static function minimalOnly(bool $minimalModeOnly): void
    {
        self::$minimalOnly = $minimalModeOnly;
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
        return $this->lastCheckPointInMS ?? 0;
    }


    /**
     * Set the last checkpoint in milliseconds
     *
     * @param  int $timestamp
     * @return void
     */
    private function setLastCheckPointInMS(int $timestamp): void
    {
        $this->lastCheckPointInMS = $timestamp;
    }


    /**
     * Get the last checkpoint number
     *
     * @return int
     */
    private function getLastCheckPointNumber(): int
    {
        return $this->lastCheckPointNumber ?? 0;
    }


    /**
     * Get the last checkpoint number, and increase it
     *
     * @param  bool $postfix
     * @return int
     */
    private function incrementLastCheckPointNumber(bool $postfix = true): int
    {
        if (!isset($this->lastCheckPointNumber)) {
            $this->lastCheckPointNumber = 0;
        }

        if ($postfix) {
            return $this->lastCheckPointNumber++;
        }

        return ++$this->lastCheckPointNumber;
    }


    /**
     * Get the $pathUI
     *
     * @return string
     */
    public function getPathUI(): string
    {
        return self::$pathUI ?? '';
    }


    /**
     * Get checkpoints array
     *
     * @return array<string,object>
     */
    public function getCheckPoints(): array
    {
        return $this->checkPoints ?? [];
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
        return $this->getCurrentTime() - $this->getRequestTime();
        // what about loads before Debench such as composer !?
        // return $this->endPointMS - $this->initPointMS;
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
     * Get the script name
     *
     * @return string
     */
    public function getScriptName(): string
    {
        return $_SERVER['SCRIPT_NAME'];
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
     * Make the tag
     * 
     * @param  string $tag
     * @param  int $id
     * @return string
     */
    private function makeTag(string $tag, int $id): string
    {
        if (empty($tag)) {
            $tag = 'point ' . $id;
        }

        // tags(keys) should to be unique
        return $tag .= '#' . $id;
    }


    /**
     * Validate the tag
     * 
     * @param  string $tag
     * @return bool
     */
    private function checkTag(string $tag): bool
    {
        $regex = "/^([a-zA-Z0-9_ -]+)#[0-9]+$/";

        if (preg_match($regex, $tag)) {
            return true;
        }

        return false;
    }


    /**
     * Add an exception to exceptions array
     * 
     * @param  Throwable $exception
     * @return void
     */
    public function addException(\Throwable $exception): void
    {
        if (!isset($this->exceptions)) {
            $this->exceptions = [];
        }

        $this->exceptions[] = $exception;
    }


    /**
     * Get the exceptions array
     * 
     * @return array
     */
    private function getExceptions(): array
    {
        return $this->exceptions ?? [];
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
        if ($this->isMinimalOnly()) {
            return Template::render(self::$pathUI . '/widget.minimal.htm', [
                'base' => self::$ui,
                'ramUsage' => $this->getRamUsage(true),
                'requestInfo' => $_SERVER['REQUEST_METHOD'] . ' ' . http_response_code(),
                'fullExecTime' => $eTime
            ]);
        }


        // ------- infoLog
        $infoLog = Template::render(self::$pathUI . '/widget.log.info.htm', [
            "phpVersion" => SystemInfo::getPHPVersion(),
            "opcache" => SystemInfo::getOPCacheStatus(),
            "systemAPI" => SystemInfo::getSystemAPI(),
        ]);


        // ------- timeLog
        $timeLog = '';

        foreach ($this->getCheckPoints() as $key => $cp) {
            $timeLog .= Template::render(self::$pathUI . '/widget.log.checkpoint.htm', [
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
        $logPost = $this->makeOutputLoop(self::$pathUI . '/widget.log.request.post.htm', $_POST, false);


        // ------- logGet
        $logGet = $this->makeOutputLoop(self::$pathUI . '/widget.log.request.get.htm', $_GET, false);


        // ------- logCookie
        $logCookie = $this->makeOutputLoop(self::$pathUI . '/widget.log.request.cookie.htm', $_COOKIE, false);


        if (empty($logPost . $logGet . $logCookie)) {
            $logPost = '<b>Nothing</b> Yet!';
        }


        // ------- logSession
        if (SystemInfo::isCLI()) {
            $logSession = 'CLI mode!';
        } else if (!isset($_SESSION)) {
            $logSession = '<b>_SESSION</b> is not available!';
        } else {
            $logSession = $this->makeOutputLoop(self::$pathUI . '/widget.log.request.session.htm', $_SESSION);
        }


        // ------- logException
        $logException = '';

        foreach ($this->getExceptions() as $exception) {
            $file = basename($exception->getFile());
            $path = str_replace($file, "<b>$file</b>", $exception->getFile());

            $logException .= Template::render(self::$pathUI . '/widget.log.exception.htm', [
                // "code" => $exception->getCode(),
                "message" => $exception->getMessage(),
                "path" => $path,
                "line" => $exception->getLine(),
            ]);
        }

        if (empty($logException)) {
            $logException = '<b>Nothing</b> Yet!';
        }


        // ------- the main widget
        return Template::render(self::$pathUI . '/widget.htm', [
            'base' => self::$ui,
            'ramUsagePeak' => $this->getRamUsagePeak(true),
            'ramUsage' => $this->getRamUsage(true),
            // 'includedFilesCount' => $this->getLoadedFilesCount(),
            'preloadTime' => $this->initPointMS - $this->getRequestTime(),
            'request' => count($_POST) + count($_GET) + count($_COOKIE),
            'logPost' => $logPost,
            'logGet' => $logGet,
            'logCookie' => $logCookie,
            'session' => count($_SESSION ?? []),
            'sessionLog' => $logSession,
            'infoLog' => $infoLog,
            'timeLog' => $timeLog,
            'logException' => $logException,
            'exception' => count($this->getExceptions()),
            'requestInfo' => $_SERVER['REQUEST_METHOD'] . ' ' . http_response_code(),
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
            self::$instance = new self($enable, $ui);
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
