<?php

declare(strict_types=1);

namespace DevMode;

/**
 * Plugin constants
 */
class Constants
{
    public const VERSION = '1.0.0';
    public const OPTION_WATCHED_PLUGINS = 'dev_mode_watched_plugins';

    /**
     * Default plugins to watch (pre-checked on first save)
     */
    public const DEFAULT_WATCHED_PLUGINS = [
        'query-monitor/query-monitor.php',
        'debug-bar/debug-bar.php',
        'developer/developer.php',
        'log-deprecated-notices/log-deprecated-notices.php',
    ];

    private static ?string $path = null;
    private static ?string $url = null;

    /**
     * Get plugin directory path
     *
     * @return string
     */
    public static function path(): string
    {
        if (self::$path === null) {
            self::$path = plugin_dir_path(dirname(__FILE__));
        }
        return self::$path;
    }

    /**
     * Get plugin directory URL
     *
     * @return string
     */
    public static function url(): string
    {
        if (self::$url === null) {
            self::$url = plugin_dir_url(dirname(__FILE__));
        }
        return self::$url;
    }
}
