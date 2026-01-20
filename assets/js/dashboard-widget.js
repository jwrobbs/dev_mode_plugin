/**
 * Dev Mode Dashboard Widget JavaScript
 */
(function($) {
    'use strict';

    /**
     * Show a toast notification
     * @param {string} message - The message to display
     * @param {string} type - 'success' or 'error'
     */
    function showToast(message, type) {
        var $toast = $('<div class="dev-mode-toast ' + type + '">' + message + '</div>');
        $('body').append($toast);

        setTimeout(function() {
            $toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }

    /**
     * Handle debug constant toggle
     */
    $(document).on('change', '.dev-mode-debug-constant', function() {
        var $toggle = $(this);
        var $widget = $toggle.closest('.dev-mode-widget');
        var constant = $toggle.data('constant');
        var enable = $toggle.is(':checked');

        $widget.addClass('dev-mode-loading');

        $.ajax({
            url: devModeAjax.ajaxUrl,
            method: 'POST',
            data: {
                action: 'dev_mode_toggle_debug',
                nonce: devModeAjax.nonce,
                constant: constant,
                enable: enable ? 'true' : 'false'
            },
            success: function(response) {
                if (response.success) {
                    showToast(response.data.message, 'success');

                    // Update the label text
                    $toggle.closest('.dev-mode-toggle')
                        .find('.dev-mode-toggle-label strong')
                        .text(enable ? 'ON' : 'OFF');

                    // If this is WP_DEBUG, show/hide sub-toggles and update Site Info
                    if (constant === 'WP_DEBUG') {
                        var $subToggles = $widget.find('.dev-mode-sub-toggles');
                        if (enable) {
                            $subToggles.slideDown(200);
                        } else {
                            $subToggles.slideUp(200);
                        }

                        // Update Debug Mode in Site Info table
                        $widget.find('.dev-mode-snapshot td').each(function() {
                            if ($(this).text() === 'Debug Mode') {
                                $(this).next('td').text(enable ? 'Enabled' : 'Disabled');
                            }
                        });

                        // Update the hidden textarea for copy
                        var $textarea = $widget.find('#dev-mode-snapshot-text');
                        var text = $textarea.val();
                        text = text.replace(
                            /Debug Mode: (Enabled|Disabled)/,
                            'Debug Mode: ' + (enable ? 'Enabled' : 'Disabled')
                        );
                        $textarea.val(text);
                    }
                } else {
                    showToast(response.data.message || 'Error toggling ' + constant, 'error');
                    $toggle.prop('checked', !enable); // Revert
                }
            },
            error: function() {
                showToast('Network error occurred', 'error');
                $toggle.prop('checked', !enable); // Revert
            },
            complete: function() {
                $widget.removeClass('dev-mode-loading');
            }
        });
    });

    /**
     * Handle plugin toggle
     */
    $(document).on('change', '.dev-mode-plugin-toggle', function() {
        var $toggle = $(this);
        var $widget = $toggle.closest('.dev-mode-widget');
        var pluginFile = $toggle.data('plugin');
        var activate = $toggle.is(':checked');

        $widget.addClass('dev-mode-loading');

        $.ajax({
            url: devModeAjax.ajaxUrl,
            method: 'POST',
            data: {
                action: 'dev_mode_toggle_plugin',
                nonce: devModeAjax.nonce,
                plugin: pluginFile,
                activate: activate ? 'true' : 'false'
            },
            success: function(response) {
                if (response.success) {
                    showToast(response.data.message, 'success');
                } else {
                    showToast(response.data.message || 'Error toggling plugin', 'error');
                    $toggle.prop('checked', !activate); // Revert
                }
            },
            error: function() {
                showToast('Network error occurred', 'error');
                $toggle.prop('checked', !activate); // Revert
            },
            complete: function() {
                $widget.removeClass('dev-mode-loading');
            }
        });
    });

    /**
     * Handle copy snapshot
     */
    $(document).on('click', '#dev-mode-copy-snapshot', function() {
        var text = $('#dev-mode-snapshot-text').val();

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                showToast('Site info copied to clipboard', 'success');
            }).catch(function() {
                fallbackCopy(text);
            });
        } else {
            fallbackCopy(text);
        }
    });

    /**
     * Fallback copy method for older browsers
     */
    function fallbackCopy(text) {
        var $textarea = $('#dev-mode-snapshot-text');
        $textarea.css('display', 'block');
        $textarea.select();

        try {
            document.execCommand('copy');
            showToast('Site info copied to clipboard', 'success');
        } catch (e) {
            showToast('Failed to copy to clipboard', 'error');
        }

        $textarea.css('display', 'none');
    }

    /**
     * Handle utility action buttons
     */
    $(document).on('click', '.dev-mode-utility-btn', function() {
        var $btn = $(this);
        var $widget = $btn.closest('.dev-mode-widget');
        var action = $btn.data('action');
        var confirmMsg = $btn.data('confirm');

        if (confirmMsg && !confirm(confirmMsg)) {
            return;
        }

        $widget.addClass('dev-mode-loading');

        $.ajax({
            url: devModeAjax.ajaxUrl,
            method: 'POST',
            data: {
                action: action,
                nonce: devModeAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showToast(response.data.message, 'success');
                } else {
                    showToast(response.data.message || 'Error performing action', 'error');
                }
            },
            error: function() {
                showToast('Network error occurred', 'error');
            },
            complete: function() {
                $widget.removeClass('dev-mode-loading');
            }
        });
    });

    /**
     * Handle clear log
     */
    $(document).on('click', '#dev-mode-clear-log', function() {
        var $btn = $(this);
        var $widget = $btn.closest('.dev-mode-widget');

        if (!confirm('Are you sure you want to clear the debug log?')) {
            return;
        }

        $widget.addClass('dev-mode-loading');

        $.ajax({
            url: devModeAjax.ajaxUrl,
            method: 'POST',
            data: {
                action: 'dev_mode_clear_log',
                nonce: devModeAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showToast(response.data.message, 'success');
                    // Update the size display
                    $widget.find('.dev-mode-section:contains("Debug Log") strong')
                        .first()
                        .text(response.data.size || '0 B');
                } else {
                    showToast(response.data.message || 'Error clearing log', 'error');
                }
            },
            error: function() {
                showToast('Network error occurred', 'error');
            },
            complete: function() {
                $widget.removeClass('dev-mode-loading');
            }
        });
    });

})(jQuery);
