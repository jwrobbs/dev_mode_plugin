<?php

declare(strict_types=1);

namespace DevMode\Admin\Ajax;

/**
 * AJAX handler for debug log operations
 *
 * @codeCoverageIgnore WordPress glue code
 */
class LogManager
{
    /**
     * Register the AJAX actions
     *
     * @return void
     */
    public static function register(): void
    {
        add_action('wp_ajax_dev_mode_clear_log', [self::class, 'handleClear']);
        add_action('wp_ajax_dev_mode_view_log', [self::class, 'handleView']);
    }

    /**
     * Handle clearing the debug log
     *
     * @return void
     */
    public static function handleClear(): void
    {
        // Verify nonce
        if (!check_ajax_referer('dev_mode_ajax', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid security token.'], 403);
        }

        // Verify capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions.'], 403);
        }

        $logPath = WP_CONTENT_DIR . '/debug.log';

        if (!file_exists($logPath)) {
            wp_send_json_success(['message' => 'No log file to clear.']);
        }

        // Clear the log by truncating it
        $result = file_put_contents($logPath, '');

        if ($result === false) {
            wp_send_json_error(['message' => 'Failed to clear log file.'], 500);
        }

        wp_send_json_success([
            'message' => 'Debug log cleared.',
            'size' => '0 B',
        ]);
    }

    /**
     * Handle viewing the debug log (opens in new tab)
     *
     * @return void
     */
    public static function handleView(): void
    {
        // Verify nonce via GET parameter
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'dev_mode_view_log')) {
            wp_die('Invalid security token.', 'Error', ['response' => 403]);
        }

        // Verify capability
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.', 'Error', ['response' => 403]);
        }

        $logPath = WP_CONTENT_DIR . '/debug.log';

        if (!file_exists($logPath)) {
            wp_die('No debug.log file exists.', 'Not Found', ['response' => 404]);
        }

        // Get the file size
        $size = filesize($logPath);
        $maxSize = 5 * 1024 * 1024; // 5MB limit for viewing

        // Set headers for plain text display
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        if ($size > $maxSize) {
            echo "=== Log file is too large to display fully ===\n";
            echo "=== Showing last 5MB of content ===\n\n";

            $handle = fopen($logPath, 'r');
            if ($handle) {
                fseek($handle, -$maxSize, SEEK_END);
                // Skip to next newline to avoid partial line
                fgets($handle);
                echo fread($handle, $maxSize);
                fclose($handle);
            }
        } else {
            readfile($logPath);
        }

        exit;
    }

    /**
     * Get debug log size
     *
     * @return string Human-readable size
     */
    public static function getLogSize(): string
    {
        $logPath = WP_CONTENT_DIR . '/debug.log';

        if (!file_exists($logPath)) {
            return '0 B';
        }

        $size = filesize($logPath);
        return self::formatSize($size);
    }

    /**
     * Format bytes to human-readable size
     *
     * @param int|false $bytes Size in bytes
     * @return string Formatted size
     */
    private static function formatSize(int|false $bytes): string
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
