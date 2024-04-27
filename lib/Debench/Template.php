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
    private static array $path;

    /**
     * Render .htm files by params
     * 
     * @return string
     */
    public static function render(string $themePath, array $params): string
    {
        if (!file_exists($themePath)) {
            throw new \Exception("File '$themePath` doesn't exists!");
        }

        if (!isset(self::$path)) {
            self::$path = [];
        }

        if (empty($theme = @self::$path[$themePath])) {
            $theme = file_get_contents($themePath);
        }

        foreach ($params as $key => $value) {
            $theme = str_replace("{{@$key}}", "$value", $theme);
        }

        return $theme;
    }
}
