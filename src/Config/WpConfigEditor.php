<?php

declare(strict_types=1);

namespace DevMode\Config;

/**
 * Handles reading and writing debug constants to wp-config.php
 */
class WpConfigEditor
{
    /**
     * Debug constants we manage
     */
    private const DEBUG_CONSTANTS = [
        'WP_DEBUG',
        'WP_DEBUG_LOG',
        'WP_DEBUG_DISPLAY',
    ];

    /**
     * Find the wp-config.php file path
     *
     * @return string|null Path to wp-config.php or null if not found
     */
    public static function findConfigPath(): ?string
    {
        // Check in ABSPATH (web root)
        $webRootPath = ABSPATH . 'wp-config.php';
        if (file_exists($webRootPath)) {
            return $webRootPath;
        }

        // Check one level up from ABSPATH
        $parentPath = dirname(ABSPATH) . '/wp-config.php';
        if (file_exists($parentPath)) {
            return $parentPath;
        }

        return null;
    }

    /**
     * Check if wp-config.php is writable
     *
     * @return bool
     */
    public static function isWritable(): bool
    {
        $path = self::findConfigPath();
        return $path !== null && is_writable($path);
    }

    /**
     * Get current debug settings from wp-config.php
     *
     * @return array<string, bool> Array of constant => value
     */
    public static function getDebugSettings(): array
    {
        $settings = [
            'WP_DEBUG' => defined('WP_DEBUG') && WP_DEBUG,
            'WP_DEBUG_LOG' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
            'WP_DEBUG_DISPLAY' => defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY,
        ];

        return $settings;
    }

    /**
     * Check if debug mode is currently enabled
     *
     * @return bool
     */
    public static function isDebugEnabled(): bool
    {
        return defined('WP_DEBUG') && WP_DEBUG;
    }

    /**
     * Toggle debug mode on or off
     *
     * @param bool $enable True to enable debug mode, false to disable
     * @return bool True on success, false on failure
     */
    public static function setDebugMode(bool $enable): bool
    {
        $path = self::findConfigPath();
        if ($path === null || !is_writable($path)) {
            return false;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return false;
        }

        $newValue = $enable ? 'true' : 'false';

        // Update or add each debug constant
        foreach (self::DEBUG_CONSTANTS as $constant) {
            $content = self::updateConstant($content, $constant, $newValue);
        }

        // Write the file
        $result = file_put_contents($path, $content);
        return $result !== false;
    }

    /**
     * Update or add a constant in wp-config.php content
     *
     * @param string $content The wp-config.php content
     * @param string $constant The constant name
     * @param string $value The value ('true' or 'false')
     * @return string Updated content
     */
    private static function updateConstant(string $content, string $constant, string $value): string
    {
        // Pattern to match existing define statement for this constant
        $pattern = "/define\s*\(\s*['\"]" . preg_quote($constant, '/') . "['\"]\s*,\s*[^)]+\s*\)\s*;/";

        $replacement = "define('{$constant}', {$value});";

        if (preg_match($pattern, $content)) {
            // Replace existing constant
            $content = preg_replace($pattern, $replacement, $content);
        } else {
            // Add new constant before "That's all" comment or at end
            $insertPoint = self::findInsertPoint($content);
            $content = substr($content, 0, $insertPoint)
                . $replacement . "\n"
                . substr($content, $insertPoint);
        }

        return $content;
    }

    /**
     * Find the best point to insert new constants
     *
     * @param string $content The wp-config.php content
     * @return int Position to insert at
     */
    private static function findInsertPoint(string $content): int
    {
        // Look for "That's all, stop editing!" comment
        $markers = [
            "/* That's all, stop editing!",
            "/* That's all, stop editing!",
            '/** Absolute path to the WordPress directory.',
        ];

        foreach ($markers as $marker) {
            $pos = strpos($content, $marker);
            if ($pos !== false) {
                return $pos;
            }
        }

        // If no marker found, insert before the require for wp-settings.php
        $pos = strpos($content, "require_once ABSPATH . 'wp-settings.php'");
        if ($pos !== false) {
            return $pos;
        }

        // Last resort: end of file
        return strlen($content);
    }
}
