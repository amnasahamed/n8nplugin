/**
 * n8n Chat Widget Admin JavaScript - World Class Functionality
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Tab Navigation
        initTabs();

        // Color Picker
        initColorPicker();

        // Theme Presets
        initThemePresets();

        // Icon Selection
        initIconSelection();

        // Style Options
        initStyleOptions();

        // Range Sliders
        initRangeSliders();

        // Display Mode
        initDisplayMode();

        // Media Uploads
        initMediaUploads();

        // Import/Export
        initImportExport();

        // Reset Settings
        initResetSettings();

        // Live Preview Updates
        initLivePreview();

        // Form Validation
        initFormValidation();
    });

    /**
     * Tab Navigation
     */
    function initTabs() {
        $('.n8n-tab').on('click', function() {
            const tabId = $(this).data('tab');

            $('.n8n-tab').removeClass('active');
            $(this).addClass('active');

            $('.n8n-tab-content').removeClass('active');
            $('#tab-' + tabId).addClass('active');
        });
    }

    /**
     * Color Picker
     */
    function initColorPicker() {
        $('.n8n-color-picker').wpColorPicker({
            change: function(event, ui) {
                updatePreviewColor(ui.color.toString());
            }
        });
    }

    /**
     * Theme Presets
     */
    function initThemePresets() {
        const presetColors = {
            purple: '#854fff',
            blue: '#2563eb',
            teal: '#14b8a6',
            rose: '#f43f5e',
            orange: '#f97316',
            emerald: '#10b981',
            indigo: '#6366f1',
            slate: '#475569'
        };

        $('input[name="n8n_chat_widget_theme_preset"]').on('change', function() {
            const preset = $(this).val();

            $('.n8n-theme-preset').removeClass('selected');
            $(this).closest('.n8n-theme-preset').addClass('selected');

            if (preset === 'custom') {
                $('.n8n-custom-color-field').slideDown();
            } else {
                $('.n8n-custom-color-field').slideUp();
                if (presetColors[preset]) {
                    updatePreviewColor(presetColors[preset]);
                }
            }
        });
    }

    /**
     * Icon Selection
     */
    function initIconSelection() {
        // Icon type toggle
        $('input[name="n8n_chat_widget_icon_type"]').on('change', function() {
            const type = $(this).val();
            if (type === 'emoji') {
                $('#emoji-icon-section').slideDown();
                $('#svg-icon-section').slideUp();
                updatePreviewIcon($('#n8n_chat_widget_icon').val());
            } else {
                $('#emoji-icon-section').slideUp();
                $('#svg-icon-section').slideDown();
            }
        });

        // Emoji selection
        $('.n8n-emoji-option').on('click', function() {
            const emoji = $(this).text();
            $('#n8n_chat_widget_icon').val(emoji);
            $('.n8n-emoji-option').removeClass('selected');
            $(this).addClass('selected');
            updatePreviewIcon(emoji);
        });

        // Manual emoji input
        $('#n8n_chat_widget_icon').on('input', function() {
            const emoji = $(this).val();
            updatePreviewIcon(emoji);
            $('.n8n-emoji-option').removeClass('selected');
            $('.n8n-emoji-option').each(function() {
                if ($(this).text() === emoji) {
                    $(this).addClass('selected');
                }
            });
        });
    }

    /**
     * Style Options
     */
    function initStyleOptions() {
        $('input[name="n8n_chat_widget_button_style"]').on('change', function() {
            $('.n8n-style-option').removeClass('selected');
            $(this).closest('.n8n-style-option').addClass('selected');
        });
    }

    /**
     * Range Sliders
     */
    function initRangeSliders() {
        $('#n8n_chat_widget_zoom_slider').on('input', function() {
            const value = $(this).val();
            $('#n8n_chat_widget_zoom').val(value);
            updatePreviewZoom(value);
        });

        $('#n8n_chat_widget_zoom').on('change', function() {
            let value = Math.max(50, Math.min(150, $(this).val()));
            $(this).val(value);
            $('#n8n_chat_widget_zoom_slider').val(value);
            updatePreviewZoom(value);
        });
    }

    /**
     * Display Mode Toggle
     */
    function initDisplayMode() {
        $('#n8n_display_mode').on('change', function() {
            const mode = $(this).val();
            $('.n8n-include-pages, .n8n-exclude-pages').hide();

            if (mode === 'include') {
                $('.n8n-include-pages').slideDown();
            } else if (mode === 'exclude') {
                $('.n8n-exclude-pages').slideDown();
            }
        });
    }

    /**
     * Media Uploads
     */
    function initMediaUploads() {
        // SVG Icon Upload
        $('#upload_svg_button').on('click', function(e) {
            e.preventDefault();
            openMediaFrame('svg', '#n8n_chat_widget_svg_icon', 'image/svg+xml');
        });

        // Logo Upload
        $('#upload_logo_button').on('click', function(e) {
            e.preventDefault();
            openMediaFrame('logo', '#n8n_chat_widget_header_logo', 'image');
        });
    }

    function openMediaFrame(type, targetInput, fileType) {
        const frame = wp.media({
            title: type === 'svg' ? 'Select SVG Icon' : 'Select Logo',
            button: { text: 'Use this image' },
            multiple: false,
            library: { type: fileType }
        });

        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();

            if (type === 'svg' && attachment.subtype !== 'svg+xml') {
                alert('Please select an SVG file.');
                return;
            }

            $(targetInput).val(attachment.url);

            if (type === 'svg' && $('input[name="n8n_chat_widget_icon_type"]:checked').val() === 'svg') {
                $('#preview-icon').html('<img src="' + attachment.url + '" style="width: 24px; height: 24px; filter: brightness(0) invert(1);">');
            }
        });

        frame.open();
    }

    /**
     * Import/Export Settings
     */
    function initImportExport() {
        // Export
        $('#export-settings').on('click', function() {
            const $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: n8nchwiAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'n8nchwi_export_settings',
                    nonce: n8nchwiAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const blob = new Blob([response.data.settings], { type: 'application/json' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'n8n-chat-widget-settings.json';
                        a.click();
                        URL.revokeObjectURL(url);
                        showNotice('success', n8nchwiAdmin.exportSuccess);
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });

        // Import Toggle
        $('#import-settings').on('click', function() {
            $('#import-field').slideToggle();
        });

        // Confirm Import
        $('#confirm-import').on('click', function() {
            const settings = $('#import-data').val().trim();

            if (!settings) {
                alert('Please paste your settings JSON.');
                return;
            }

            try {
                JSON.parse(settings);
            } catch (e) {
                alert('Invalid JSON format.');
                return;
            }

            const $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: n8nchwiAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'n8nchwi_import_settings',
                    nonce: n8nchwiAdmin.nonce,
                    settings: settings
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('success', n8nchwiAdmin.importSuccess);
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        alert(response.data || 'Import failed.');
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Import failed. Please try again.');
                    $btn.prop('disabled', false);
                }
            });
        });
    }

    /**
     * Reset Settings
     */
    function initResetSettings() {
        $('#reset-settings').on('click', function() {
            if (!confirm(n8nchwiAdmin.confirmReset)) {
                return;
            }

            const $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: n8nchwiAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'n8nchwi_reset_settings',
                    nonce: n8nchwiAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('success', n8nchwiAdmin.resetSuccess);
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        alert(response.data || 'Reset failed.');
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Reset failed. Please try again.');
                    $btn.prop('disabled', false);
                }
            });
        });
    }

    /**
     * Live Preview Updates
     */
    function initLivePreview() {
        // Title
        $('#n8n_chat_widget_title').on('input', function() {
            $('#preview-title').text($(this).val() || 'Chat Support');
        });

        // Position
        $('#n8n_chat_widget_position').on('change', function() {
            const position = $(this).val();
            $('#preview-position-text').text('Position: Bottom ' + position);
        });
    }

    /**
     * Form Validation
     */
    function initFormValidation() {
        $('#n8n_chat_widget_url').on('blur', function() {
            const url = $(this).val().trim();
            if (url && !isValidUrl(url)) {
                $(this).css('border-color', '#ef4444');
            } else {
                $(this).css('border-color', '');
            }
        });

        $('#n8n-chat-settings-form').on('submit', function(e) {
            const url = $('#n8n_chat_widget_url').val().trim();
            if (url && !isValidUrl(url)) {
                e.preventDefault();
                alert('Please enter a valid URL starting with http:// or https://');
                $('#n8n_chat_widget_url').focus();
                return false;
            }
        });
    }

    /**
     * Helper Functions
     */
    function updatePreviewColor(color) {
        $('#preview-header').css('background', color);
        $('#preview-button').css('background', color);
    }

    function updatePreviewIcon(icon) {
        $('#preview-icon').text(icon);
    }

    function updatePreviewZoom(zoom) {
        const scale = zoom / 100;
        $('#preview-iframe').css('transform', 'scale(' + scale + ')');
    }

    function isValidUrl(string) {
        try {
            const url = new URL(string);
            return url.protocol === 'http:' || url.protocol === 'https:';
        } catch (_) {
            return false;
        }
    }

    function showNotice(type, message) {
        const $notice = $('<div class="notice notice-' + type + ' is-dismissible" style="position: fixed; top: 50px; right: 20px; z-index: 9999; padding: 12px 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);"><p>' + message + '</p></div>');
        $('body').append($notice);
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }

})(jQuery);
