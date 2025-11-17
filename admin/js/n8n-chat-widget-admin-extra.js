/**
 * Additional admin functionality for n8n Chat Widget
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Make both buttons submit the form
        $('#preview-save-changes, #load-preview-button').on('click', function(e) {
            e.preventDefault();
            
            // Show loading state on button
            const $button = $(this);
            const originalText = $button.text();
            $button.prop('disabled', true).text('Saving...');
            
            // Submit the form
            $('#n8n-chat-settings-form').submit();
        });
        
        // Allow Enter key to submit the form as well
        $('#n8n_chat_widget_url').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                $('#load-preview-button').click();
                return false;
            }
        });
        
        // Set up iframe loading indicator
        $('#zoom-preview-iframe').on('load', function() {
            $('#preview-loading-spinner').css('display', 'none');
        });
        
        // Icon type toggle
        $('input[name="n8n_chat_widget_icon_type"]').on('change', function() {
            var selectedType = $('input[name="n8n_chat_widget_icon_type"]:checked').val();
            
            if (selectedType === 'emoji') {
                $('#emoji-icon-section').show();
                $('#svg-icon-section').hide();
            } else {
                $('#emoji-icon-section').hide();
                $('#svg-icon-section').show();
            }
        });
        
        // Emoji selection
        $('.icon-option').on('click', function() {
            var emoji = $(this).text();
            $('#n8n_chat_widget_icon').val(emoji);
            
            // Update preview button icon
            $('#preview-button-icon').html(emoji);
        });
        
        // Media uploader for SVG
        var mediaUploader;
        $('#upload_svg_button').on('click', function(e) {
            e.preventDefault();
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            mediaUploader = wp.media({
                title: 'Select or Upload SVG Icon',
                button: {
                    text: 'Use this icon'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#n8n_chat_widget_svg_icon').val(attachment.url);
                
                // Update preview
                var previewHtml = '<div style="width: 60px; height: 60px; border: 1px solid #ddd; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; background-color: ' + $('#n8n_chat_widget_color').val() + ';">';
                previewHtml += '<img src="' + attachment.url + '" style="max-width: 60%; max-height: 60%;" alt="SVG Icon">';
                previewHtml += '</div>';
                
                $('.svg-preview').html('<p class="description">Current icon:</p>' + previewHtml);
                
                // Update preview button icon
                $('#preview-button-icon').html('<img src="' + attachment.url + '" alt="Chat" style="max-width: 60%; max-height: 60%; filter: brightness(0) invert(1);">');
            });
            
            mediaUploader.open();
        });
        
        // Initialize color picker with live preview update
        if ($.fn.wpColorPicker) {
            $('.n8n-color-picker').wpColorPicker({
                change: function(event, ui) {
                    var color = ui.color.toString();
                    
                    // Update all preview elements with the new color
                    $('#preview-widget-header').css('background-color', color);
                    $('#preview-chat-button').css('background-color', color);
                    $('.n8n-chat-widget-preview-header-inner').css('background-color', color);
                    $('.n8n-chat-widget-preview-button').css('background-color', color);
                    
                    // Update SVG preview background if it exists
                    if ($('.svg-preview div').length) {
                        $('.svg-preview div').css('background-color', color);
                    }
                    
                    // Update loading spinner color
                    $('.preview-loading-spinner div').css('border-top-color', color);
                },
                palettes: true
            });
        }
        
        // Zoom slider functionality with live preview
        $('#n8n_chat_widget_zoom_slider').on('input', function() {
            var zoomValue = $(this).val();
            $('#n8n_chat_widget_zoom').val(zoomValue);
            
            // Update iframe zoom in real-time
            if ($('#zoom-preview-iframe').length) {
                $('#zoom-preview-iframe').css('transform', 'scale(' + (zoomValue / 100) + ')');
            }
        });
        
        // Zoom input field functionality
        $('#n8n_chat_widget_zoom').on('input', function() {
            var zoomValue = $(this).val();
            $('#n8n_chat_widget_zoom_slider').val(zoomValue);
            
            // Update iframe zoom in real-time
            if ($('#zoom-preview-iframe').length) {
                $('#zoom-preview-iframe').css('transform', 'scale(' + (zoomValue / 100) + ')');
            }
        });
    });

})(jQuery); 