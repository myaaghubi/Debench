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
     * @param  string $targetPath
     * @return void
     */
    public static function makeUI(string $targetPath): void
    {
        // for ui/assets
        @mkdir($targetPath, 0777, true);

        // for path
        if (is_dir($targetPath)) {
            // Copy the templates files from ui dir into your webroot dir if files don't match
            Utils::copyDir(__DIR__ . '/ui', $targetPath);
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
        if (!isset(self::$paths)) {
            self::$paths = [];
        }

        if (empty(self::$paths[$themePath])) {
            if (!file_exists($themePath)) {
                throw new \Exception("File '$themePath` doesn't exists!");
                return '';
            }
            self::$paths[$themePath] = file_get_contents($themePath);
        }

        $theme = self::$paths[$themePath];

        foreach ($params as $key => $value) {
            $theme = str_replace("{{@$key}}", "$value", $theme);
        }

        return $theme;
    }
}
