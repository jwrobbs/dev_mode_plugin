<?php

declare(strict_types=1);

namespace DevMode;

/**
 * Plugin bootstrap and hook registration
 */
class Bootstrap
{
    /**
     * Initialize the plugin
     *
     * @param string $pluginFile Main plugin file path
     * @return void
     */
    public static function init(string $pluginFile): void
    {
        // Initialize plugin components on plugins_loaded
        add_action('plugins_loaded', function () {
            Plugin::init();
        });
    }
}
