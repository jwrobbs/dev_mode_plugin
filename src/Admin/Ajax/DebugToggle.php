<?php

declare(strict_types=1);

namespace DevMode\Admin\Ajax;

use DevMode\Config\WpConfigEditor;

/**
 * AJAX handler for toggling debug mode
 *
 * @codeCoverageIgnore WordPress glue code
 */
class DebugToggle
{
    /**
     * Register the AJAX action
     *
     * @return void
     */
    public static function register(): void
    {
        add_action('wp_ajax_dev_mode_toggle_debug', [self::class, 'handle']);
    }

    /**
     * Handle the AJAX request
     *
     * @return void
     */
    public static function handle(): void
    {
        // Verify nonce
        if (!check_ajax_referer('dev_mode_ajax', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid security token.'], 403);
        }

        // Verify capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions.'], 403);
        }

        // Get the requested state
        $enable = isset($_POST['enable']) && $_POST['enable'] === 'true';

        // Check if config is writable
        if (!WpConfigEditor::isWritable()) {
            wp_send_json_error(['message' => 'wp-config.php is not writable.'], 500);
        }

        // Toggle the debug mode
        $result = WpConfigEditor::setDebugMode($enable);

        if ($result) {
            wp_send_json_success([
                'message' => $enable ? 'Debug mode enabled.' : 'Debug mode disabled.',
                'debug_enabled' => $enable,
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to update wp-config.php.'], 500);
        }
    }
}
