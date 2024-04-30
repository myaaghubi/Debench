<?php

declare(strict_types=1);

/**
 * @package Debench, Template
 * @link http://github.com/myaaghubi/debench Github
 * @author Mohammad Yaaghubi <m.yaaghubi.abc@gmail.com>
 * @copyright Copyright (c) 2024, Mohammad Yaaghubi
 * @license MIT License
 */

namespace DEBENCH;

class Template
{
    private static array $paths;


    /**
     * Make suer to have the UI dir
     *
     * @param  string $pathToWrite
     * @param  string $targetPath
     * @return void
     */
    public static function makeUI(string $pathToWrite, string $targetPath): void
    {
        $currentPath = __DIR__;

        // for path
        if (!is_dir($pathToWrite)) {
            @mkdir($pathToWrite, 0777, true);
            
            if (!is_dir($pathToWrite) || !is_writable($pathToWrite)) {
                throw new \Exception("Directory not exists or not writable! `$pathToWrite` ", 500);
            }
        }

        // for ui/assets
        if (!is_dir($targetPath)) {
            @mkdir($targetPath, 0777, true);

            // Copy the template from ui dir into your webroot dir if it doesn't exist
            Utils::copyDir($currentPath . '/ui', $targetPath);
        }
    }


    /**
     * Render .htm files by params
     * 
     * @param string $themePath
     * @param array $params
     * @return string
     */
    public static function render(string $themePath, array $params): string
    {
        if (!file_exists($themePath)) {
            throw new \Exception("File '$themePath` doesn't exists!");
        }

        if (!isset(self::$paths)) {
            self::$paths = [];
        }

        if (empty($theme = @self::$paths[$themePath])) {
            $theme = file_get_contents($themePath);
        }

        foreach ($params as $key => $value) {
            $theme = str_replace("{{@$key}}", "$value", $theme);
        }

        return $theme;
    }
}
