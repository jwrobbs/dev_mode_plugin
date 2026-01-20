<?php

declare(strict_types=1);

namespace DevMode\Admin;

use DevMode\Config\EnvironmentDetector;
use DevMode\Config\WpConfigEditor;
use DevMode\Data\SiteSnapshot;
use DevMode\Constants;

/**
 * Dashboard widget for dev mode controls
 *
 * @codeCoverageIgnore WordPress glue code
 */
class DashboardWidget
{
    private const WIDGET_ID = 'dev_mode_widget';

    /**
     * Register the dashboard widget
     *
     * @return void
     */
    public static function register(): void
    {
        add_action('wp_dashboard_setup', [self::class, 'addWidget']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAssets']);
    }

    /**
     * Add the dashboard widget
     *
     * @return void
     */
    public static function addWidget(): void
    {
        wp_add_dashboard_widget(
            self::WIDGET_ID,
            'Dev Mode',
            [self::class, 'renderWidget']
        );
    }

    /**
     * Enqueue CSS and JS assets
     *
     * @param string $hook The current admin page
     * @return void
     */
    public static function enqueueAssets(string $hook): void
    {
        if ($hook !== 'index.php') {
            return;
        }

        wp_enqueue_style(
            'dev-mode-widget',
            Constants::url() . 'assets/css/dashboard-widget.css',
            [],
            Constants::VERSION
        );

        wp_enqueue_script(
            'dev-mode-widget',
            Constants::url() . 'assets/js/dashboard-widget.js',
            ['jquery'],
            Constants::VERSION,
            true
        );

        wp_localize_script('dev-mode-widget', 'devModeAjax', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dev_mode_ajax'),
        ]);
    }

    /**
     * Render the widget content
     *
     * @return void
     */
    public static function renderWidget(): void
    {
        $environment = EnvironmentDetector::detect();
        $envColor = EnvironmentDetector::getColor();
        $isDebugEnabled = WpConfigEditor::isDebugEnabled();
        $isConfigWritable = WpConfigEditor::isWritable();
        $snapshot = SiteSnapshot::collect();
        $logInfo = SiteSnapshot::getDebugLogInfo();
        $watchedPlugins = self::getWatchedInstalledPlugins();

        ?>
        <div class="dev-mode-widget">
            <!-- Environment Indicator -->
            <div class="dev-mode-section dev-mode-environment">
                <span class="dev-mode-env-badge" style="background-color: <?php echo esc_attr($envColor); ?>">
                    <?php echo esc_html(strtoupper($environment)); ?>
                </span>
            </div>

            <!-- Debug Toggle -->
            <div class="dev-mode-section">
                <h4>Debug Mode</h4>
                <?php if (!$isConfigWritable) : ?>
                    <p class="dev-mode-warning">
                        wp-config.php is not writable. Debug toggle disabled.
                    </p>
                <?php endif; ?>
                <label class="dev-mode-toggle">
                    <input
                        type="checkbox"
                        id="dev-mode-debug-toggle"
                        <?php checked($isDebugEnabled); ?>
                        <?php disabled(!$isConfigWritable); ?>
                    >
                    <span class="dev-mode-toggle-slider"></span>
                    <span class="dev-mode-toggle-label">
                        WP_DEBUG: <strong><?php echo $isDebugEnabled ? 'ON' : 'OFF'; ?></strong>
                    </span>
                </label>
            </div>

            <!-- Dev Plugin Toggles -->
            <?php if (!empty($watchedPlugins)) : ?>
                <div class="dev-mode-section">
                    <h4>Dev Plugins</h4>
                    <?php foreach ($watchedPlugins as $pluginFile => $pluginData) : ?>
                        <label class="dev-mode-toggle">
                            <input
                                type="checkbox"
                                class="dev-mode-plugin-toggle"
                                data-plugin="<?php echo esc_attr($pluginFile); ?>"
                                <?php checked($pluginData['active']); ?>
                            >
                            <span class="dev-mode-toggle-slider"></span>
                            <span class="dev-mode-toggle-label">
                                <?php echo esc_html($pluginData['name']); ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Site Snapshot -->
            <div class="dev-mode-section">
                <h4>
                    Site Info
                    <button type="button" class="button button-small" id="dev-mode-copy-snapshot">
                        Copy
                    </button>
                </h4>
                <table class="dev-mode-snapshot">
                    <?php foreach ($snapshot as $label => $value) : ?>
                        <tr>
                            <td><?php echo esc_html($label); ?></td>
                            <td><?php echo esc_html($value); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <textarea id="dev-mode-snapshot-text" style="display:none;"><?php
                    echo esc_textarea(SiteSnapshot::formatForClipboard());
                ?></textarea>
            </div>

            <!-- Debug Log -->
            <div class="dev-mode-section">
                <h4>Debug Log</h4>
                <?php if ($logInfo['exists']) : ?>
                    <p>
                        Size: <strong><?php echo esc_html($logInfo['size']); ?></strong>
                    </p>
                    <p>
                        <?php
                        $logUrl = admin_url('admin-ajax.php?action=dev_mode_view_log&nonce='
                            . wp_create_nonce('dev_mode_view_log'));
                        ?>
                        <a href="<?php echo esc_url($logUrl); ?>"
                           target="_blank"
                           class="button button-small">
                            View Log
                        </a>
                        <button type="button"
                                class="button button-small"
                                id="dev-mode-clear-log">
                            Clear Log
                        </button>
                    </p>
                <?php else : ?>
                    <p class="dev-mode-muted">No debug.log file exists.</p>
                <?php endif; ?>
            </div>

            <!-- Settings Link -->
            <div class="dev-mode-section dev-mode-footer">
                <a href="<?php echo esc_url(admin_url('options-general.php?page=dev-mode-settings')); ?>">
                    Manage watched plugins &rarr;
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Get watched plugins that are actually installed
     *
     * @return array<string, array{name: string, active: bool}> Plugin info indexed by file
     */
    private static function getWatchedInstalledPlugins(): array
    {
        $watched = get_option(Constants::OPTION_WATCHED_PLUGINS, []);
        if (!is_array($watched) || empty($watched)) {
            return [];
        }

        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $allPlugins = get_plugins();
        $activePlugins = get_option('active_plugins', []);
        $result = [];

        foreach ($watched as $pluginFile) {
            if (isset($allPlugins[$pluginFile])) {
                $result[$pluginFile] = [
                    'name' => $allPlugins[$pluginFile]['Name'],
                    'active' => in_array($pluginFile, $activePlugins, true),
                ];
            }
        }

        return $result;
    }
}
