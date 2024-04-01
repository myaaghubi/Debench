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
    public static function copyDir(string $from, string $to, bool $checkForError=true): void
    {
        if ($checkForError) {
            if (!file_exists($from)) {
                throw new \Exception("File/Dir '$from` doesn't exists!");
            }
            if (!file_exists($to)) {
                throw new \Exception("File/Dir '$to` doesn't exists!");
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
                    $this->copyDir($from . DIRECTORY_SEPARATOR . $file, $to . DIRECTORY_SEPARATOR . $file, false);
                } else {
                    copy($from . DIRECTORY_SEPARATOR . $file, $to . DIRECTORY_SEPARATOR . $file);
                }
            }
        }

        closedir($dir);
    }
}