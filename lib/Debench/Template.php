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
    /**
     * Render the theme by params
     * 
     * @return string
     */
    public static function render(string $themePath, array $params): string
    {
        if (!file_exists($themePath)) {
            throw new \Exception("File '$themePath` doesn't exists!");
        }

        $theme = file_get_contents($themePath);

        foreach ($params as $key => $value) {
            $theme = str_replace("{{@$key}}", "$value", $theme);
        }

        return $theme;
    }
}