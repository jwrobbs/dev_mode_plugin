<?php

declare(strict_types=1);

namespace DevMode\Data;

/**
 * Collects and formats site information for the dashboard widget
 */
class SiteSnapshot
{
    /**
     * Get all site information
     *
     * @return array<string, string> Array of label => value
     */
    public static function collect(): array
    {
        global $wpdb;

        $dbInfo = self::getDatabaseInfo();

        return [
            'WordPress Version' => get_bloginfo('version'),
            'PHP Version' => PHP_VERSION,
            $dbInfo['label'] => $dbInfo['version'],
            'Active Theme' => self::getActiveTheme(),
            'Active Plugins' => self::getActivePluginCount(),
            'Memory Limit' => WP_MEMORY_LIMIT,
            'Max Memory Limit' => defined('WP_MAX_MEMORY_LIMIT') ? WP_MAX_MEMORY_LIMIT : 'Not set',
            'Debug Mode' => defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled',
            'Site URL' => get_site_url(),
            'Home URL' => get_home_url(),
        ];
    }

    /**
     * Get database type and version
     *
     * @return array{label: string, version: string}
     */
    private static function getDatabaseInfo(): array
    {
        global $wpdb;

        $serverInfo = $wpdb->db_server_info();
        $version = $wpdb->db_version();

        if (stripos($serverInfo, 'mariadb') !== false) {
            return ['label' => 'MariaDB Version', 'version' => $version];
        }

        return ['label' => 'MySQL Version', 'version' => $version];
    }

    /**
     * Get the active theme name
     *
     * @return string
     */
    private static function getActiveTheme(): string
    {
        $theme = wp_get_theme();
        $name = $theme->get('Name');

        // Include parent theme if this is a child theme
        $parent = $theme->parent();
        if ($parent) {
            $name .= ' (child of ' . $parent->get('Name') . ')';
        }

        return $name;
    }

    /**
     * Get the count of active plugins
     *
     * @return string
     */
    private static function getActivePluginCount(): string
    {
        $activePlugins = get_option('active_plugins', []);
        $count = count($activePlugins);

        // Include network-activated plugins in multisite
        if (is_multisite()) {
            $networkPlugins = get_site_option('active_sitewide_plugins', []);
            $count += count($networkPlugins);
            return "{$count} (including network)";
        }

        return (string) $count;
    }

    /**
     * Format the snapshot as copyable text
     *
     * @return string Formatted text for clipboard
     */
    public static function formatForClipboard(): string
    {
        $data = self::collect();
        $lines = [];

        foreach ($data as $label => $value) {
            $lines[] = "{$label}: {$value}";
        }

        return implode("\n", $lines);
    }

    /**
     * Get debug log information
     *
     * @return array{exists: bool, size: string, path: string}
     */
    public static function getDebugLogInfo(): array
    {
        $logPath = WP_CONTENT_DIR . '/debug.log';

        if (!file_exists($logPath)) {
            return [
                'exists' => false,
                'size' => '0 B',
                'path' => $logPath,
            ];
        }

        $size = filesize($logPath);
        return [
            'exists' => true,
            'size' => self::formatFileSize($size),
            'path' => $logPath,
        ];
    }

    /**
     * Format file size to human-readable string
     *
     * @param int|false $bytes File size in bytes
     * @return string Formatted size
     */
    private static function formatFileSize(int|false $bytes): string
    {
        if ($bytes === false || $bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
