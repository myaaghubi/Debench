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
     * Copy folder
     *
     * @param  string $from
     * @param  string $to
     * @return void
     */
    public static function copyDir(string $from, string $to, bool $checkForError = true): void
    {
        if ($checkForError) {
            if (!file_exists($from)) {
                throw new \Exception("File/Dir source '$from` doesn't exists!");
            }
            $toBack = dirname($to, 1);
            if (!is_writable($toBack)) {
                throw new \Exception("Dir dest '$toBack' is not writable!");
            }
        }

        // open the source directory
        $dir = opendir($from);

        mkdir($to);

        // Loop through the files in source directory
        while ($file = readdir($dir)) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($from . DIRECTORY_SEPARATOR . $file)) {
                    // for sub directory 
                    Utils::copyDir($from . DIRECTORY_SEPARATOR . $file, $to . DIRECTORY_SEPARATOR . $file, false);
                } else {
                    copy($from . DIRECTORY_SEPARATOR . $file, $to . DIRECTORY_SEPARATOR . $file);
                }
            }
        }

        closedir($dir);
    }


    /**
     * Format bytes with B, KB, MB, 'GB', 'TB' etc.
     *
     * @param  int $size
     * @return string
     */
    public static function toFormattedBytes(int $size = 0): string
    {
        if ($size == 0) {
            return '0 B';
        }

        $base = log($size, 1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');

        return round(pow(1024, $base - floor($base))) . ' ' . $suffixes[floor($base)];
    }


    /**
     * Check if PHPUnit test is running
     *
     * @return bool
     */
    public static function isInTestMode(): bool
    {
        if (PHP_SAPI != 'cli') {
            return false;
        }

        if (defined('PHPUNIT_COMPOSER_INSTALL') && defined('__PHPUNIT_PHAR__')) {
            return true;
        }

        if (strpos($_SERVER['argv'][0], 'phpunit') !== false) {
            return true;
        }

        return false;
    }
}