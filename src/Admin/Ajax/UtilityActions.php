<?php

declare(strict_types=1);

namespace DevMode\Admin\Ajax;

/**
 * AJAX handler for utility actions (cache, permalinks, logs)
 *
 * @codeCoverageIgnore WordPress glue code
 */
class UtilityActions
{
    /**
     * Register the AJAX actions
     *
     * @return void
     */
    public static function register(): void
    {
        add_action('wp_ajax_dev_mode_clear_cache', [self::class, 'handleClearCache']);
        add_action('wp_ajax_dev_mode_flush_permalinks', [self::class, 'handleFlushPermalinks']);
        add_action('wp_ajax_dev_mode_clear_simple_history', [self::class, 'handleClearSimpleHistory']);
        add_action('wp_ajax_dev_mode_clear_action_scheduler', [self::class, 'handleClearActionScheduler']);
    }

    /**
     * Clear WordPress object cache
     *
     * @return void
     */
    public static function handleClearCache(): void
    {
        if (!self::verifyRequest()) {
            return;
        }

        wp_cache_flush();

        wp_send_json_success(['message' => 'Cache cleared.']);
    }

    /**
     * Flush rewrite rules (permalinks)
     *
     * @return void
     */
    public static function handleFlushPermalinks(): void
    {
        if (!self::verifyRequest()) {
            return;
        }

        flush_rewrite_rules();

        wp_send_json_success(['message' => 'Permalinks flushed.']);
    }

    /**
     * Clear Simple History log
     *
     * @return void
     */
    public static function handleClearSimpleHistory(): void
    {
        if (!self::verifyRequest()) {
            return;
        }

        // Check if Simple History is active
        $hasSimpleHistory = class_exists('SimpleHistory')
            || class_exists('Simple_History\\Simple_History');
        if (!$hasSimpleHistory) {
            wp_send_json_error(['message' => 'Simple History plugin not active.'], 400);
        }

        global $wpdb;

        // Simple History uses these tables
        $eventsTable = $wpdb->prefix . 'simple_history';
        $contextsTable = $wpdb->prefix . 'simple_history_contexts';

        // Truncate both tables
        $wpdb->query("TRUNCATE TABLE {$eventsTable}");
        $wpdb->query("TRUNCATE TABLE {$contextsTable}");

        wp_send_json_success(['message' => 'Simple History log cleared.']);
    }

    /**
     * Clear Action Scheduler completed/failed actions
     *
     * @return void
     */
    public static function handleClearActionScheduler(): void
    {
        if (!self::verifyRequest()) {
            return;
        }

        // Check if Action Scheduler is available
        if (!class_exists('ActionScheduler_DBStore')) {
            wp_send_json_error(['message' => 'Action Scheduler not available.'], 400);
        }

        global $wpdb;

        $actionsTable = $wpdb->prefix . 'actionscheduler_actions';
        $logsTable = $wpdb->prefix . 'actionscheduler_logs';

        // Delete completed and failed actions (not pending or in-progress)
        $deletedActions = $wpdb->query(
            "DELETE FROM {$actionsTable} WHERE status IN ('complete', 'failed', 'canceled')"
        );

        // Clean up orphaned logs
        $wpdb->query(
            "DELETE l FROM {$logsTable} l
             LEFT JOIN {$actionsTable} a ON l.action_id = a.action_id
             WHERE a.action_id IS NULL"
        );

        wp_send_json_success([
            'message' => "Action Scheduler cleared ({$deletedActions} actions removed).",
        ]);
    }

    /**
     * Verify nonce and capability
     *
     * @return bool
     */
    private static function verifyRequest(): bool
    {
        if (!check_ajax_referer('dev_mode_ajax', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid security token.'], 403);
            return false;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions.'], 403);
            return false;
        }

        return true;
    }
}
