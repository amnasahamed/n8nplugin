/**
 * N8N Chat Widget Front-end JavaScript
 */
(function($) {
    'use strict';

    // DOM elements
    let $container, $button, $popup, $closeBtn, $iframe, $loading;

    // Track widget state
    let isWidgetOpen = false;
    let hasLoaded = false;

    /**
     * Initialize the chat widget
     */
    function initChatWidget() {
        // Cache DOM elements
        $container = $('#n8n-chat-widget-container');
        $button = $('#n8n-chat-widget-button');
        $popup = $('#n8n-chat-widget-popup');
        $closeBtn = $('#n8n-chat-widget-close');
        $iframe = $('#n8n-chat-widget-iframe');
        $loading = $('#n8n-chat-widget-loading');

        if (!$container.length) {
            return;
        }

        // Handle chat button click
        $button.on('click', toggleChatWidget);
        
        // Handle close button click
        $closeBtn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeChatWidget();
        });
        
        // Handle clicks outside the widget
        $(document).on('click', function(event) {
            if (isWidgetOpen && 
                !$(event.target).closest($popup).length && 
                !$(event.target).closest($button).length) {
                closeChatWidget();
            }
        });

        // Prevent popup clicks from closing
        $popup.on('click', function(e) {
            e.stopPropagation();
        });

        // Handle ESC key press
        $(document).on('keydown', function(event) {
            if (isWidgetOpen && event.key === 'Escape') {
                closeChatWidget();
                event.preventDefault();
            }
        });

        // Handle iframe load events
        $iframe.on('load', function() {
            hasLoaded = true;
            $loading.fadeOut(300);
            
            // Apply zoom if available - using the updated variable name
            if (typeof n8nchwiData !== 'undefined' && n8nchwiData.zoom) {
                applyZoomToIframe(n8nchwiData.zoom);
            }
        });

        // Add mobile class for smaller screens
        if (window.innerWidth < 480) {
            $container.addClass('n8n-chat-widget-mobile');
        }

        // Handle window resize
        $(window).on('resize', function() {
            if (window.innerWidth < 480) {
                $container.addClass('n8n-chat-widget-mobile');
            } else {
                $container.removeClass('n8n-chat-widget-mobile');
            }
        });
    }

    /**
     * Toggle the chat widget open/closed
     */
    function toggleChatWidget() {
        if (isWidgetOpen) {
            closeChatWidget();
        } else {
            openChatWidget();
        }
    }

    /**
     * Open the chat widget
     */
    function openChatWidget() {
        $popup.css('display', 'flex');
        $container.addClass('n8n-chat-widget-open');
        isWidgetOpen = true;
        
        // Only load the iframe content when opened for the first time
        if (!hasLoaded) {
            var chatUrl = $iframe.attr('data-src');
            if (chatUrl) {
                $loading.show();
                $iframe.attr('src', chatUrl);
            }
        } else {
            // If already loaded, make sure zoom is applied
            if (typeof n8nchwiData !== 'undefined' && n8nchwiData.zoom) {
                applyZoomToIframe(n8nchwiData.zoom);
            }
        }

        // Animate opening
        setTimeout(function() {
            $popup.addClass('n8n-chat-widget-popup-open');
        }, 10);
    }

    /**
     * Close the chat widget
     */
    function closeChatWidget() {
        $popup.removeClass('n8n-chat-widget-popup-open');
        $container.removeClass('n8n-chat-widget-open');
        
        // Wait for animation to complete before hiding
        setTimeout(function() {
            $popup.css('display', 'none');
            isWidgetOpen = false;
        }, 300);
    }

    /**
     * Apply zoom scale to the iframe content
     */
    function applyZoomToIframe(zoomLevel) {
        try {
            const scale = parseInt(zoomLevel, 10) / 100;
            if (isNaN(scale) || scale <= 0) return;
            
            // Apply transform to the iframe
            $iframe.css({
                'transform': `scale(${scale})`,
                'transform-origin': 'top left',
                'width': `${100/scale}%`,
                'height': `${100/scale}%`
            });
            
            // Adjust the container to handle overflow properly
            $iframe.parent().css({
                'overflow': 'hidden',
                'height': '100%'
            });
            
        } catch (e) {
            console.warn('Could not apply zoom to iframe:', e);
        }
    }

    // Initialize the widget when the DOM is ready
    $(document).ready(function() {
        initChatWidget();
    });

})(jQuery); 