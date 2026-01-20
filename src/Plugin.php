<?php

declare(strict_types=1);

namespace DevMode;

/**
 * Main plugin initialization class
 */
class Plugin
{
    /**
     * Initialize the plugin by registering all components
     *
     * @return void
     */
    public static function init(): void
    {
        // Only load for users who can manage options
        if (!current_user_can('manage_options')) {
            return;
        }

        // Register admin components
        Admin\DashboardWidget::register();
        Admin\SettingsPage::register();

        // Register AJAX handlers
        Admin\Ajax\DebugToggle::register();
        Admin\Ajax\PluginToggle::register();
        Admin\Ajax\LogManager::register();
    }
}
