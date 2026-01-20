<?php

declare(strict_types=1);

namespace DevMode\Config;

/**
 * Detects the current environment from WP_ENV
 */
class EnvironmentDetector
{
    /**
     * Environment to color mapping for display
     */
    public const ENVIRONMENT_COLORS = [
        'production' => '#dc3545', // red
        'staging' => '#ffc107',    // yellow
        'local' => '#28a745',      // green
        'dev' => '#28a745',        // green (alias for local)
        'unknown' => '#6c757d',    // gray
    ];

    /**
     * Get the current environment from WP_ENV
     *
     * Reads from $_ENV which is populated by phpdotenv in wp-config.php
     *
     * @return string Environment name or 'unknown'
     */
    public static function detect(): string
    {
        $environment = $_ENV['WP_ENV'] ?? getenv('WP_ENV') ?: 'unknown';
        return $environment !== '' ? $environment : 'unknown';
    }

    /**
     * Get the color for the current environment
     *
     * @return string Hex color code
     */
    public static function getColor(): string
    {
        $environment = self::detect();
        return self::ENVIRONMENT_COLORS[$environment] ?? self::ENVIRONMENT_COLORS['unknown'];
    }

    /**
     * Check if this is a production environment
     *
     * @return bool
     */
    public static function isProduction(): bool
    {
        return self::detect() === 'production';
    }

    /**
     * Check if this is a local/development environment
     *
     * @return bool
     */
    public static function isLocal(): bool
    {
        return in_array(self::detect(), ['local', 'dev'], true);
    }
}
