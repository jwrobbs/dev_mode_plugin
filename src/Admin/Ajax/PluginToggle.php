<?php

declare(strict_types=1);

namespace DevMode\Admin\Ajax;

/**
 * AJAX handler for toggling dev plugins
 *
 * @codeCoverageIgnore WordPress glue code
 */
class PluginToggle
{
    /**
     * Register the AJAX action
     *
     * @return void
     */
    public static function register(): void
    {
        add_action('wp_ajax_dev_mode_toggle_plugin', [self::class, 'handle']);
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

        // Get plugin file and desired state
        $pluginFile = isset($_POST['plugin']) ? sanitize_text_field($_POST['plugin']) : '';
        $activate = isset($_POST['activate']) && $_POST['activate'] === 'true';

        if (empty($pluginFile)) {
            wp_send_json_error(['message' => 'No plugin specified.'], 400);
        }

        // Load plugin functions if needed
        if (!function_exists('activate_plugin')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Check if plugin exists
        $allPlugins = get_plugins();
        if (!isset($allPlugins[$pluginFile])) {
            wp_send_json_error(['message' => 'Plugin not found.'], 404);
        }

        $pluginName = $allPlugins[$pluginFile]['Name'];

        if ($activate) {
            // Activate the plugin
            $result = activate_plugin($pluginFile);

            if (is_wp_error($result)) {
                wp_send_json_error([
                    'message' => 'Failed to activate: ' . $result->get_error_message(),
                ], 500);
            }

            wp_send_json_success([
                'message' => "{$pluginName} activated.",
                'active' => true,
            ]);
        } else {
            // Deactivate the plugin
            deactivate_plugins($pluginFile);

            wp_send_json_success([
                'message' => "{$pluginName} deactivated.",
                'active' => false,
            ]);
        }
    }
}
