<?php

declare(strict_types=1);

/**
 * @package Debench, Utils
 * @link http://github.com/myaaghubi/debench Github
 * @author Mohammad Yaaghubi <m.yaaghubi.abc@gmail.com>
 * @copyright Copyright (c) 2024, Mohammad Yaaghubi
 * @license MIT License
 */

namespace DEBENCH;

class Utils
{
    /**
     * Get the backtrace, make sure to call it directly
     *
     * @return array
     */
    public static function getBacktrace(): array
    {
        $debugBTOut = [];

        $debugBT = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 0);

        foreach ($debugBT as $btItem) {
            if (strrpos($btItem['file'], __DIR__) === false) {
                $debugBTOut[] = $btItem;
            }
        }

        return $debugBTOut;
    }


    /**
     * Copy folder
     *
     * @param  string $from
     * @param  string $to
     * @param  bool $checkForError
     * @return void
     */
    public static function copyDir(string $from, string $to): void
    {
        // open the source directory
        $dir = opendir($from);

        if ($dir===false) {
            return;
        }

        @mkdir($to);

        // Loop through the files in source directory
        while ($file = readdir($dir)) {
            if (($file != '.') && ($file != '..')) {
                $fileFrom = $from . DIRECTORY_SEPARATOR . $file;
                $fileTo = $to . DIRECTORY_SEPARATOR . $file;
                if (is_dir($fileFrom)) {
                    // for sub directory 
                    Utils::copyDir($fileFrom, $fileTo);
                } else {
                    if (!file_exists($fileTo) || filesize($fileFrom) != filesize($fileTo)) {
                        copy($fileFrom, $fileTo);
                    }
                }
            }
        }

        closedir($dir);
    }



    /**
     * Delete a folder recursively
     *
     * @param  string $dir
     * @return void
     */
    public static function deleteDir(string $dir): void
    {
        $glob = glob($dir);
        foreach ($glob as $file) {
            if (is_dir($file)) {
                self::deleteDir("$file/*");
                rmdir($file);
            } else {
                unlink($file);
            }
        }
    }


    /**
     * Format bytes with B, KB, MB, 'GB', 'TB' etc.
     *
     * @param  int $size
     * @param  bool $roundUnderMB
     * @return string
     */
    public static function toFormattedBytes(int $size = 0, bool $roundUnderMB = false): string
    {
        if ($size == 0) {
            return '0 B';
        }

        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($size, 1024);

        $round = 1;
        if ($roundUnderMB && floor($base) < array_search('MB', $suffixes)) {
            $round = 0;
        }

        return round(pow(1024, $base - floor($base)), $round) . ' ' . $suffixes[floor($base)];
    }


    /**
     * Check if PHPUnit test is running
     *
     * @return bool
     */
    public static function isInTestMode(): bool
    {
        if (SystemInfo::isCLI() && defined('PHPUNIT_COMPOSER_INSTALL') && defined('__PHPUNIT_PHAR__')) {
            return true;
        }

        if (SystemInfo::isCLI() && strpos($_SERVER['argv'][0], 'phpunit') !== false) {
            return true;
        }

        return false;
    }
}
