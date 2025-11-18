/**
 * N8N Chat Widget Admin JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize color pickers
        $('.n8n-color-picker').wpColorPicker({
            change: function(event, ui) {
                // Get the color value
                const colorValue = ui.color.toString();

                // Update all color-dependent elements in the preview
                updateColorInPreview(colorValue);
            },
            palettes: true
        });

        // Connection Test Functionality
        $('#n8n-test-connection').on('click', function() {
            const $button = $(this);
            const $urlInput = $('#n8n_chat_widget_url');
            const $status = $('#n8n-connection-status');
            const $message = $('#n8n-connection-message');
            const url = $urlInput.val().trim();

            // Validate URL exists
            if (!url) {
                updateConnectionStatus('error', n8nchwiSettings.strings.failed);
                showConnectionMessage('error', 'Please enter a URL to test.');
                $urlInput.focus();
                return;
            }

            // Validate URL format
            if (!isValidUrl(url)) {
                updateConnectionStatus('error', n8nchwiSettings.strings.failed);
                showConnectionMessage('error', 'Please enter a valid URL starting with http:// or https://');
                $urlInput.focus();
                return;
            }

            // Set testing state
            $button.prop('disabled', true).addClass('testing').text(n8nchwiSettings.strings.testing);
            updateConnectionStatus('testing', n8nchwiSettings.strings.testing);
            $message.hide();

            // Make AJAX request
            $.ajax({
                url: n8nchwiSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'n8nchwi_test_connection',
                    nonce: n8nchwiSettings.testConnectionNonce,
                    url: url
                },
                success: function(response) {
                    if (response.success) {
                        updateConnectionStatus('success', n8nchwiSettings.strings.connected);
                        showConnectionMessage('success', response.data.message);
                    } else {
                        updateConnectionStatus('error', n8nchwiSettings.strings.failed);
                        showConnectionMessage('error', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    updateConnectionStatus('error', n8nchwiSettings.strings.failed);
                    showConnectionMessage('error', 'Network error. Please check your connection and try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).removeClass('testing').text(n8nchwiSettings.strings.testConnection);
                }
            });
        });

        // Helper function to update connection status indicator
        function updateConnectionStatus(status, text) {
            const $statusEl = $('#n8n-connection-status');

            // Ensure status indicator has content
            if (!$statusEl.find('.status-dot').length) {
                $statusEl.html('<span class="status-dot"></span><span class="status-text"></span>');
            }

            // Update classes
            $statusEl.removeClass('status-unknown status-testing status-success status-error')
                     .addClass('status-' + status);

            // Update text
            $statusEl.find('.status-text').text(text);
        }

        // Helper function to show connection message
        function showConnectionMessage(type, message) {
            const $message = $('#n8n-connection-message');
            $message.removeClass('message-success message-error')
                    .addClass('message-' + type)
                    .text(message)
                    .show();
        }

        // Auto-test connection when URL changes (debounced)
        let urlTestTimeout;
        $('#n8n_chat_widget_url').on('input', function() {
            const url = $(this).val().trim();

            // Reset status when URL changes
            if (url) {
                updateConnectionStatus('unknown', 'Not tested');
            } else {
                $('#n8n-connection-status').empty().removeClass('status-unknown status-testing status-success status-error');
            }
            $('#n8n-connection-message').hide();
        });

        // ========== SETTINGS GROUP COLLAPSE ==========
        $('.n8n-settings-group-header').on('click', function() {
            const $group = $(this).closest('.n8n-settings-group');
            $group.toggleClass('collapsed');
        });

        // ========== POSITION SELECTOR ==========
        $('.n8n-position-option input').on('change', function() {
            const $options = $('.n8n-position-option');
            $options.removeClass('selected');
            $(this).closest('.n8n-position-option').addClass('selected');

            // Update preview position text
            const position = $(this).val();
            if (typeof n8nchwiSettings !== 'undefined' && n8nchwiSettings.positionTemplate) {
                const positionText = n8nchwiSettings.positionTemplate.replace('%s', position);
                $('#preview-position-text').text(positionText);
            }
        });

        // ========== ICON TABS ==========
        $('.n8n-icon-tab').on('click', function() {
            const tab = $(this).data('tab');

            // Update tab buttons
            $('.n8n-icon-tab').removeClass('active');
            $(this).addClass('active');

            // Update tab content
            $('.n8n-icon-tab-content').removeClass('active');
            $('#' + tab + '-icon-section').addClass('active');

            // Update hidden input
            $('#n8n_chat_widget_icon_type').val(tab);

            // Update preview
            if (tab === 'emoji') {
                const emoji = $('#n8n_chat_widget_icon').val() || '💬';
                $('#preview-button-icon').html(emoji);
            } else {
                const svgUrl = $('#n8n_chat_widget_svg_icon').val();
                if (svgUrl) {
                    $('#preview-button-icon').html('<img src="' + svgUrl + '" alt="Icon" style="max-width: 60%; max-height: 60%; filter: brightness(0) invert(1);">');
                }
            }
        });

        // ========== EMOJI GRID ==========
        $('.n8n-emoji-option').on('click', function() {
            const emoji = $(this).text();
            $('#n8n_chat_widget_icon').val(emoji);
            $('#preview-button-icon').text(emoji);

            // Update selected state
            $('.n8n-emoji-option').removeClass('selected');
            $(this).addClass('selected');
        });

        // ========== ZOOM PRESETS ==========
        $('.n8n-zoom-preset').on('click', function() {
            const zoom = $(this).data('zoom');

            // Update preset buttons
            $('.n8n-zoom-preset').removeClass('active');
            $(this).addClass('active');

            // Update inputs
            $('#n8n_chat_widget_zoom').val(zoom);
            $('#n8n_chat_widget_zoom_slider').val(zoom);

            // Update preview
            updateZoomPreview(zoom);
        });

        // Update preset buttons when slider changes
        $('#n8n_chat_widget_zoom_slider, #n8n_chat_widget_zoom').on('input', function() {
            const zoom = parseInt($(this).val());

            // Update preset button states
            $('.n8n-zoom-preset').each(function() {
                const presetZoom = parseInt($(this).data('zoom'));
                $(this).toggleClass('active', presetZoom === zoom);
            });
        });

        // ========== TARGETING MODE SELECTOR ==========
        $('input[name="n8n_chat_widget_targeting_mode"]').on('change', function() {
            const mode = $(this).val();

            // Update selected state
            $('.n8n-targeting-option').removeClass('selected');
            $(this).closest('.n8n-targeting-option').addClass('selected');

            // Show/hide pages textarea
            if (mode === 'all') {
                $('#targeting-pages-wrapper').slideUp(200);
            } else {
                $('#targeting-pages-wrapper').slideDown(200);
            }
        });

        // ========== WELCOME MESSAGE TOGGLE ==========
        $('#n8n_chat_widget_welcome_enabled').on('change', function() {
            const isEnabled = $(this).is(':checked');

            if (isEnabled) {
                $('#welcome-message-wrapper').slideDown(200);
                $('#welcome-delay-wrapper').slideDown(200);
            } else {
                $('#welcome-message-wrapper').slideUp(200);
                $('#welcome-delay-wrapper').slideUp(200);
            }
        });

        // Function to update all color elements in the preview
        function updateColorInPreview(colorValue) {
            // Update header background
            $('#preview-widget-header').css('background-color', colorValue);
            
            // Update loading spinner
            $('.preview-loading-spinner div').css('border-top-color', colorValue);
            
            // Update button color
            $('#preview-chat-button').css('background-color', colorValue);
            
            // Update SVG preview background if it exists
            $('.svg-preview div').css('background-color', colorValue);
        }

        // Handle color input direct changes (for browsers that support color inputs)
        $('#n8n_chat_widget_color').on('input', function() {
            updateColorInPreview($(this).val());
        });

        // Handle icon selection
        $('.icon-option').on('click', function() {
            const emoji = $(this).text();
            $('#n8n_chat_widget_icon').val(emoji);
            $('#preview-button-icon').text(emoji);
        });
        
        // Handle icon type toggle
        $('input[name="n8n_chat_widget_icon_type"]').on('change', function() {
            const iconType = $(this).val();
            if (iconType === 'emoji') {
                $('#emoji-icon-section').show();
                $('#svg-icon-section').hide();
                
                // Update the preview button icon to show emoji
                const emoji = $('#n8n_chat_widget_icon').val() || '💬';
                $('#preview-button-icon').html(emoji);
            } else {
                $('#emoji-icon-section').hide();
                $('#svg-icon-section').show();
                
                // If SVG is already selected, try to show it in the preview
                const svgUrl = $('#n8n_chat_widget_svg_icon').val();
                if (svgUrl) {
                    $('#preview-button-icon').html(`<img src="${svgUrl}" alt="Icon" style="max-width: 60%; max-height: 60%; filter: brightness(0) invert(1);">`);
                }
            }
        });
        
        // Handle SVG upload
        $('#upload_svg_button').on('click', function(e) {
            e.preventDefault();

            // Create a media frame
            const frame = wp.media({
                title: 'Select or Upload SVG Icon',
                button: {
                    text: 'Use this icon'
                },
                multiple: false,
                library: {
                    type: 'image/svg+xml'
                }
            });

            // When an image is selected in the media frame...
            frame.on('select', function() {
                // Get media attachment details from the frame state
                const attachment = frame.state().get('selection').first().toJSON();

                // Validate attachment exists
                if (!attachment || !attachment.url) {
                    alert('Error: Invalid attachment selected.');
                    return;
                }

                // Only allow SVG files
                if (attachment.subtype !== 'svg+xml' && attachment.type !== 'image/svg+xml') {
                    alert('Please select an SVG file. Other image formats are not supported.');
                    return;
                }

                // Additional file size check (limit to 1MB for SVG)
                if (attachment.filesizeInBytes && attachment.filesizeInBytes > 1048576) {
                    alert('SVG file is too large. Please select a file smaller than 1MB.');
                    return;
                }

                // Set the value of the input field
                $('#n8n_chat_widget_svg_icon').val(attachment.url);
                
                // Update button preview too if svg is selected
                if ($('input[name="n8n_chat_widget_icon_type"]:checked').val() === 'svg') {
                    const img = $('<img>', {
                        src: attachment.url,
                        alt: 'Icon',
                        css: {
                            'max-width': '60%',
                            'max-height': '60%',
                            'filter': 'brightness(0) invert(1)'
                        }
                    });
                    $('#preview-button-icon').empty().append(img);
                }
                
                // Update or create the preview
                if ($('.svg-preview').length) {
                    // The preview exists, update it
                    // Check if we need to reload the page to refresh the attachment ID
                    const $preview = $('.svg-preview');
                    $preview.empty();

                    $preview.append($('<p>', {class: 'description', text: 'Current icon:'}));

                    const $iconContainer = $('<div>', {
                        css: {
                            'width': '60px',
                            'height': '60px',
                            'border': '1px solid #ddd',
                            'border-radius': '50%',
                            'overflow': 'hidden',
                            'display': 'flex',
                            'align-items': 'center',
                            'justify-content': 'center',
                            'background-color': $('#n8n_chat_widget_color').val()
                        }
                    });

                    const $iconImg = $('<img>', {
                        src: attachment.url,
                        alt: 'SVG Icon',
                        css: {'max-width': '60%', 'max-height': '60%'}
                    });

                    $iconContainer.append($iconImg);
                    $preview.append($iconContainer);
                    $preview.append($('<p>', {class: 'description', css: {'color': '#d63638'}, text: 'Save settings to properly display the icon.'}));
                } else {
                    // Create a new preview
                    const $newPreview = $('<div>', {class: 'svg-preview', css: {'margin': '10px 0'}});

                    $newPreview.append($('<p>', {class: 'description', text: 'Current icon:'}));

                    const $iconContainer = $('<div>', {
                        css: {
                            'width': '60px',
                            'height': '60px',
                            'border': '1px solid #ddd',
                            'border-radius': '50%',
                            'overflow': 'hidden',
                            'display': 'flex',
                            'align-items': 'center',
                            'justify-content': 'center',
                            'background-color': $('#n8n_chat_widget_color').val()
                        }
                    });

                    const $iconImg = $('<img>', {
                        src: attachment.url,
                        alt: 'SVG Icon',
                        css: {'max-width': '60%', 'max-height': '60%'}
                    });

                    $iconContainer.append($iconImg);
                    $newPreview.append($iconContainer);
                    $newPreview.append($('<p>', {class: 'description', css: {'color': '#d63638'}, text: 'Save settings to properly display the icon.'}));

                    $('.svg-upload-container').after($newPreview);
                }
            });
            
            // Open the media library frame
            frame.open();
        });
        
        // Handle zoom slider
        function updateZoomPreview(zoomValue) {
            const scale = zoomValue / 100;
            
            // Update iframe preview if it exists
            const $previewIframe = $('#zoom-preview-iframe');
            if ($previewIframe.length) {
                // For scaling, handle differently based on zoom level
                if (scale < 1) {
                    // When zooming out, adjust width/height to ensure content fits
                    $previewIframe.css({
                        'transform': 'scale(' + scale + ')',
                        'transform-origin': 'top left',
                        'width': (100 / scale) + '%',
                        'height': (100 / scale) + '%'
                    });
                } else {
                    // When zooming in or at 100%, keep width/height at 100%
                    $previewIframe.css({
                        'transform': 'scale(' + scale + ')',
                        'transform-origin': 'top left',
                        'width': '100%',
                        'height': '100%'
                    });
                }
            }
        }
        
        // Initialize zoom preview
        updateZoomPreview($('#n8n_chat_widget_zoom').val());
        
        // Handle zoom slider changes
        $('#n8n_chat_widget_zoom_slider').on('input', function() {
            const zoomValue = $(this).val();
            $('#n8n_chat_widget_zoom').val(zoomValue);
            updateZoomPreview(zoomValue);
        });
        
        // Handle zoom input changes
        $('#n8n_chat_widget_zoom').on('input', function() {
            let zoomValue = $(this).val();
            
            // Enforce min/max boundaries
            zoomValue = Math.max(50, Math.min(150, zoomValue));
            $(this).val(zoomValue);
            
            $('#n8n_chat_widget_zoom_slider').val(zoomValue);
            updateZoomPreview(zoomValue);
        });
        
        // Update the preview title when widget title changes
        $('#n8n_chat_widget_title').on('input', function() {
            const title = $(this).val() || 'Chat Support';
            $('#preview-widget-title').text(title);
        });
        
        // Handle position changes for the preview text
        $('#n8n_chat_widget_position').on('change', function() {
            const position = $(this).val();
            if (typeof n8nchwiSettings !== 'undefined' && n8nchwiSettings.positionTemplate) {
                const positionText = n8nchwiSettings.positionTemplate.replace('%s', position);
                $('#preview-position-text').text(positionText);
            }
        });
        
        // Handle iframe load error
        $('#zoom-preview-iframe').on('error', function() {
            $(this).parent().html('<div style="padding: 15px; color: #d63638;">Error loading preview. Please check your N8N Chat URL.</div>');
        });

        // Trigger Save button when clicking the top "Save Changes" button
        $('#preview-save-changes').on('click', function(e) {
            e.preventDefault();
            
            // Show loading state for the button
            const $button = $(this);
            const originalText = $button.text();
            $button.prop('disabled', true).css('opacity', '0.7').text('Saving...');
            
            // Submit the form
            $('#n8n-chat-settings-form').submit();
            
            // Restore button state after a short delay (visual feedback)
            setTimeout(function() {
                $button.prop('disabled', false).css('opacity', '1').text(originalText);
                
                // Flash success message
                const $successMessage = $('<div>', {
                    class: 'notice notice-success is-dismissible inline',
                    style: 'padding: 10px; margin: 0 0 0 15px; display: inline-block;',
                    html: '<p>Settings saved successfully!</p>'
                });
                
                $button.after($successMessage);
                
                // Auto-remove the message after 3 seconds
                setTimeout(function() {
                    $successMessage.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 3000);
            }, 1000);
        });
        
        // Allow Enter key to submit the form
        $('#n8n_chat_widget_url').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                $('#load-preview-button').click();
                return false;
            }
        });

        // URL validation
        $('#n8n_chat_widget_url').on('blur', function() {
            const url = $(this).val().trim();
            if (url && !isValidUrl(url)) {
                $(this).css('border-color', '#d63638');
                if (!$('#url-error-message').length) {
                    $(this).after('<p id="url-error-message" class="description" style="color: #d63638;">Please enter a valid URL starting with http:// or https://</p>');
                }
            } else {
                $(this).css('border-color', '');
                $('#url-error-message').remove();
            }
        });

        // URL validation helper function
        function isValidUrl(string) {
            try {
                const url = new URL(string);
                return url.protocol === 'http:' || url.protocol === 'https:';
            } catch (_) {
                return false;
            }
        }

        // Also handle the form submission to update the preview immediately if URL changed
        $('#n8n-chat-settings-form').on('submit', function(e) {
            // Validate URL before submitting
            const url = $('#n8n_chat_widget_url').val().trim();
            if (url && !isValidUrl(url)) {
                e.preventDefault();
                alert('Please enter a valid URL starting with http:// or https://');
                $('#n8n_chat_widget_url').focus();
                return false;
            }

            // Store the current URL to check if it changed
            const currentUrl = $('#n8n_chat_widget_url').val();
            const currentUrlField = $('<input type="hidden" name="previous_url" />').val(currentUrl);
            $(this).append(currentUrlField);
        });
    });

})(jQuery); 