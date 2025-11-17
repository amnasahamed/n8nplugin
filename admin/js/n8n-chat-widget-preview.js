/**
 * Preview functionality for n8n Chat Widget
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Preview functionality
        var previewButton = $('#load-preview-button');
        var previewContainer = $('#n8n-chat-widget-preview');
        var widgetUrl = $('#n8n_chat_widget_url').val();
        var widgetPosition = $('input[name="n8n_chat_widget_position"]:checked').val() || 'right';
        var widgetTitle = $('#n8n_chat_widget_title').val() || 'Chat Support';
        var widgetColor = $('#n8n_chat_widget_color').val() || '#45d3d3';
        var widgetIcon = $('#n8n_chat_widget_icon').val() || '💬';
        var widgetIconType = $('input[name="n8n_chat_widget_icon_type"]:checked').val() || 'emoji';
        var widgetSvgIcon = $('#n8n_chat_widget_svg_icon').val() || '';
        var widgetZoom = $('#n8n_chat_widget_zoom').val() || '100';
        
        // Device preview functionality
        var deviceButtons = $('.n8n-chat-widget-preview-device-button');
        var previewFrame = $('.n8n-chat-widget-preview-frame');
        
        deviceButtons.on('click', function() {
            var device = $(this).data('device');
            deviceButtons.removeClass('active');
            $(this).addClass('active');
            
            previewFrame.removeClass('desktop tablet mobile');
            previewFrame.addClass(device);
        });
        
        // Preview chat button functionality
        var previewChatButton = $('.n8n-chat-widget-preview-button');
        var previewChatPopup = $('.n8n-chat-widget-preview-popup');
        var previewChatClose = $('.n8n-chat-widget-preview-close');
        
        previewChatButton.on('click', function() {
            previewChatPopup.addClass('open');
        });
        
        previewChatClose.on('click', function() {
            previewChatPopup.addClass('closing');
            setTimeout(function() {
                previewChatPopup.removeClass('open closing');
            }, 300);
        });
        
        // Update preview when settings change
        function updatePreview() {
            widgetUrl = $('#n8n_chat_widget_url').val();
            widgetPosition = $('input[name="n8n_chat_widget_position"]:checked').val() || 'right';
            widgetTitle = $('#n8n_chat_widget_title').val() || 'Chat Support';
            widgetColor = $('#n8n_chat_widget_color').val() || '#45d3d3';
            widgetIcon = $('#n8n_chat_widget_icon').val() || '💬';
            widgetIconType = $('input[name="n8n_chat_widget_icon_type"]:checked').val() || 'emoji';
            widgetSvgIcon = $('#n8n_chat_widget_svg_icon').val() || '';
            widgetZoom = $('#n8n_chat_widget_zoom').val() || '100';
            
            // Update position
            var previewWidget = $('.n8n-chat-widget-preview-widget');
            previewWidget.removeClass('left right');
            previewWidget.addClass(widgetPosition);
            
            // Update title
            $('.n8n-chat-widget-preview-title-inner').text(widgetTitle);
            
            // Update color
            $('.n8n-chat-widget-preview-button, .n8n-chat-widget-preview-header-inner').css('background-color', widgetColor);
            
            // Update icon
            if (widgetIconType === 'emoji') {
                $('.n8n-chat-widget-preview-icon').text(widgetIcon).show();
                $('.n8n-chat-widget-preview-svg-icon').hide();
            } else {
                $('.n8n-chat-widget-preview-icon').hide();
                if (widgetSvgIcon) {
                    const $svgIcon = $('.n8n-chat-widget-preview-svg-icon');
                    $svgIcon.empty().append($('<img>', {src: widgetSvgIcon, alt: 'Chat'})).show();
                } else {
                    $('.n8n-chat-widget-preview-icon').text('💬').show();
                }
            }
        }
        
        // Initialize preview
        updatePreview();
        
        // Update preview when settings change
        $('#n8n_chat_widget_title, #n8n_chat_widget_icon').on('input', updatePreview);
        $('#n8n_chat_widget_color').wpColorPicker({
            change: function(event, ui) {
                setTimeout(updatePreview, 100);
            }
        });
        $('input[name="n8n_chat_widget_position"], input[name="n8n_chat_widget_icon_type"]').on('change', updatePreview);
        $('#n8n_chat_widget_zoom').on('input', updatePreview);
        
        // Handle emoji selection
        $('.n8n-chat-widget-emoji-option').on('click', function() {
            var emoji = $(this).data('emoji');
            $('#n8n_chat_widget_icon').val(emoji);
            $('.n8n-chat-widget-emoji-option').removeClass('selected');
            $(this).addClass('selected');
            updatePreview();
        });
        
        // Media uploader for SVG icon
        var mediaUploader;
        $('#upload_svg_icon_button').on('click', function(e) {
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
                const $svgPreview = $('.n8n-chat-widget-svg-preview');
                $svgPreview.empty().append($('<img>', {src: attachment.url, alt: 'SVG Icon'}));
                updatePreview();
            });
            
            mediaUploader.open();
        });
        
        // Settings tabs
        $('.n8n-chat-widget-tab').on('click', function() {
            var tabId = $(this).data('tab');
            $('.n8n-chat-widget-tab').removeClass('active');
            $(this).addClass('active');
            $('.n8n-chat-widget-tab-content').removeClass('active');
            $('#' + tabId).addClass('active');
        });
        
        // Load preview button
        previewButton.on('click', function() {
            // Save settings first
            $('#n8n-chat-widget-form').submit();
            
            // Show preview after a short delay
            setTimeout(function() {
                previewContainer.show();
                updatePreview();
            }, 500);
        });
    });

})(jQuery); 