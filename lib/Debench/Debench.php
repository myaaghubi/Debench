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
    private static array $messages;

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

        set_error_handler([$this, 'addAsException']);

        register_shutdown_function([$this, 'shutdownFunction']);

        self::$instance = $this;
    }


    /**
     * To finalize the Debench
     * 
     * @return bool
     */
    private function shutdownFunction(): bool
    {
        if (!self::$enable) {
            return false;
        }

        $this->processCheckPoints();

        $output = $this->makeOutput();

        print Utils::isInTestMode() ? '' : $output;

        return !empty($output);
    }


    /**
     * Run session_start()
     * 
     * @return void
     */
    private function startSession(): void
    {
        if (session_status() != PHP_SESSION_ACTIVE) {
            @session_start();
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
     * @return void
     */
    private function addCheckPoint(int $currentTime, int $memory, string $path, int $lineNumber, string $key = ''): void
    {
        if (empty($key) || !$this->checkTag($key)) {
            throw new \Exception("The `key` is empty or is not in the right format!!");
        }

        $checkPoint = new CheckPoint($currentTime, $memory, $path, $lineNumber);

        if (!isset($this->checkPoints)) {
            $this->checkPoints = [];
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
     * @param  bool $roundUnderMB
     * @return int|string
     */
    public function getRamUsage(bool $formatted = false, bool $roundUnderMB = false): int|string
    {
        // true => memory_real_usage
        $peak = memory_get_usage();

        if ($formatted)
            return Utils::toFormattedBytes($peak, $roundUnderMB);

        return $peak;
    }


    /**
     * Get the real ram usage (peak)
     *
     * @param  bool $formatted
     * @param  bool $roundUnderMB
     * @return int|string
     */
    public function getRamUsagePeak(bool $formatted = false, bool $roundUnderMB = false): int|string
    {
        // true => memory_real_usage
        $peak = memory_get_peak_usage(true);

        if ($formatted)
            return Utils::toFormattedBytes($peak, $roundUnderMB);

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
        return $_SERVER['SCRIPT_NAME'] ?? 'non';
    }


    /**
     * Get the $_SERVER['REQUEST_METHOD']
     *
     * @return string
     */
    public function getRequestMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'non';
    }


    /**
     * Get the http_response_code()
     *
     * @return string
     */
    public function getResponseCode(): int
    {
        $rCode = http_response_code();
        // 501: Not Implemented
        return $rCode === false ? 501 : $rCode;
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
     * Add an info
     * 
     * @param  string $message
     * @return void
     */
    public static function info(string $message): void
    {
        self::addMessage($message, MessageLevel::INFO);
    }


    /**
     * Add a warning
     * 
     * @param  string $message
     * @return void
     */
    public static function warning(string $message): void
    {
        self::addMessage($message, MessageLevel::WARNING);
    }


    /**
     * Add an error
     * 
     * @param  string $message
     * @return void
     */
    public static function error(string $message): void
    {
        self::addMessage($message, MessageLevel::ERROR);
    }


    /**
     * Dump var/s
     * 
     * @param  mixed $var/s
     * @return void
     */
    public static function dump(...$args): void
    {
        $messages = [];

        foreach (func_get_args() as $var) {
            ob_start();
            var_dump($var);
            $dumped = ob_get_clean();
            $messages[] = preg_replace("/\n*<small>.*?<\/small>/", "", $dumped, 1);
        }

        $messageString = implode('', $messages);

        self::addMessage($messageString, MessageLevel::DUMP);
    }


    /**
     * Add a message
     * 
     * @param  string $message
     * @param  MessageLevel $level
     * @return void
     */
    private static function addMessage(string $message, MessageLevel $level): void
    {
        $lastBT = Utils::getBacktrace()[0];
        $path = $lastBT['file'];
        $line = $lastBT['line'];

        $messageObject = new Message($message, $level, $path, $line);

        if (empty(self::$messages)) {
            self::$messages = [];
        }

        self::$messages[] = $messageObject;
    }


    /**
     * Get the exceptions array
     * 
     * @return array
     */
    public static function messages(): array
    {
        return self::$messages ?? [];
    }


    /**
     * Clear messages
     * 
     * @return void
     */
    public static function clearMessages(): void
    {
        self::$messages = [];
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
     * Throw errors as exceptions
     * 
     * @param int $level
     * @param string $message
     * @param string $file
     * @param int $line
     * @param array $context
     * @return void
     */
    public function addAsException(int $level, string $message, string $file = '', int $line = 0, array $context = []): void
    {
        if (!isset($this->exceptions)) {
            $this->exceptions = [];
        }

        $this->exceptions[]  = new \ErrorException($message, 0, $level, $file, $line);
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
                'ramUsage' => $this->getRamUsage(true, true),
                'requestInfo' => $this->getRequestMethod() . ' ' . $this->getResponseCode(),
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
        $session = isset($_SESSION) ? $_SESSION : null;

        $logSession = $this->makeOutputLoop(self::$pathUI . '/widget.log.request.session.htm', $session);

        if (!isset($session)) {
            $logSession = '<b>_SESSION</b> is not available!';
        }


        // ------- logMessages
        $logMessage = '<b>Nothing</b> Yet!';

        if (!empty(self::messages())) {
            $logMessage = '';
        }

        foreach (self::messages() as $message) {
            $file = basename($message->getPath());
            $path = str_replace($file, "<b>$file</b>", $message->getPath());

            $logMessage .= Template::render(self::$pathUI . '/widget.log.message.htm', [
                // "code" => $exception->getCode(),
                "level" => strtoupper($message->getLevel()->name()),
                "message" => $message->getMessage(),
                "path" => $path,
                "line" => $message->getLineNumber(),
            ]);
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
            'ramUsagePeak' => $this->getRamUsagePeak(true, true),
            'ramUsage' => $this->getRamUsage(true, true),
            // 'includedFilesCount' => $this->getLoadedFilesCount(),
            'preloadTime' => $this->initPointMS - $this->getRequestTime(),
            'pointsCount' => count($this->getCheckPoints()),
            'request' => count($_POST) + count($_GET) + count($_COOKIE),
            'logPost' => $logPost,
            'logGet' => $logGet,
            'logCookie' => $logCookie,
            'session' => count($_SESSION ?? []),
            'sessionLog' => $logSession,
            'infoLog' => $infoLog,
            'timeLog' => $timeLog,
            'logMessage' => $logMessage,
            'message' => count(self::messages()),
            'logException' => $logException,
            'exception' => count($this->getExceptions()),
            'requestInfo' => $this->getRequestMethod() . ' ' . $this->getResponseCode(),
            'fullExecTime' => $eTime
        ]);
    }


    /**
     * Make formatted output in loop, $key => $value
     *
     * @return string
     */
    private function makeOutputLoop(string $theme, array|null $data, string|false $message = ''): string
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
