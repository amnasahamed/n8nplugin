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
                multiple: false
            });
            
            // When an image is selected in the media frame...
            frame.on('select', function() {
                // Get media attachment details from the frame state
                const attachment = frame.state().get('selection').first().toJSON();
                
                // Only allow SVG files
                if (attachment.subtype !== 'svg+xml') {
                    alert('Please select an SVG file.');
                    return;
                }
                
                // Set the value of the input field
                $('#n8n_chat_widget_svg_icon').val(attachment.url);
                
                // Update button preview too if svg is selected
                if ($('input[name="n8n_chat_widget_icon_type"]:checked').val() === 'svg') {
                    $('#preview-button-icon').html(`<img src="${attachment.url}" alt="Icon" style="max-width: 60%; max-height: 60%; filter: brightness(0) invert(1);">`);
                }
                
                // Update or create the preview
                if ($('.svg-preview').length) {
                    // The preview exists, update it
                    // Check if we need to reload the page to refresh the attachment ID
                    $('.svg-preview').html(`
                        <p class="description">Current icon:</p>
                        <div style="width: 60px; height: 60px; border: 1px solid #ddd; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; background-color: ${$('#n8n_chat_widget_color').val()};">
                            <img src="${attachment.url}" alt="SVG Icon" style="max-width: 60%; max-height: 60%;">
                        </div>
                        <p class="description" style="color: #d63638;">Save settings to properly display the icon.</p>
                    `);
                } else {
                    // Create a new preview
                    const previewHtml = `
                        <div class="svg-preview" style="margin: 10px 0;">
                            <p class="description">Current icon:</p>
                            <div style="width: 60px; height: 60px; border: 1px solid #ddd; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; background-color: ${$('#n8n_chat_widget_color').val()};">
                                <img src="${attachment.url}" alt="SVG Icon" style="max-width: 60%; max-height: 60%;">
                            </div>
                            <p class="description" style="color: #d63638;">Save settings to properly display the icon.</p>
                        </div>
                    `;
                    $('.svg-upload-container').after(previewHtml);
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
            const positionText = n8nChatWidgetSettings.positionTemplate.replace('%s', position);
            $('#preview-position-text').text(positionText);
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
        
        // Also handle the form submission to update the preview immediately if URL changed
        $('#n8n-chat-settings-form').on('submit', function() {
            // Store the current URL to check if it changed
            const currentUrl = $('#n8n_chat_widget_url').val();
            const currentUrlField = $('<input type="hidden" name="previous_url" />').val(currentUrl);
            $(this).append(currentUrlField);
        });
    });

})(jQuery); 