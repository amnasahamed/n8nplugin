/**
 * N8N Chat Widget Front-end JavaScript
 */
(function($) {
    'use strict';

    // DOM elements
    let $container, $button, $popup, $closeBtn, $iframe, $loading, $welcomeMessage;

    // Track widget state
    let isWidgetOpen = false;
    let hasLoaded = false;
    let welcomeMessageTimeout = null;

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

        // Initialize welcome message if enabled
        initWelcomeMessage();

        // Track widget load
        trackAnalyticsEvent('widget_load');
    }

    /**
     * Track analytics event
     */
    function trackAnalyticsEvent(eventType) {
        // Check if analytics is enabled
        if (typeof n8nchwiData === 'undefined' || n8nchwiData.analyticsEnabled !== 'yes') {
            return;
        }

        // Send AJAX request
        $.ajax({
            url: n8nchwiData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'n8nchwi_record_analytics',
                nonce: n8nchwiData.analyticsNonce,
                event_type: eventType
            },
            // Silent tracking - no need to handle response
            error: function() {
                // Silently fail
            }
        });
    }

    /**
     * Initialize and show welcome message
     */
    function initWelcomeMessage() {
        // Check if welcome message is enabled
        if (typeof n8nchwiData === 'undefined' || n8nchwiData.welcomeEnabled !== 'yes') {
            return;
        }

        // Check if user has already dismissed the welcome message
        const storageKey = 'n8n_welcome_dismissed';
        if (localStorage.getItem(storageKey)) {
            return;
        }

        // Get message and delay
        const message = n8nchwiData.welcomeMessage || 'Hi there! How can I help you today?';
        const delay = (parseInt(n8nchwiData.welcomeDelay, 10) || 3) * 1000;

        // Create welcome message element
        $welcomeMessage = $('<div>', {
            class: 'n8n-chat-widget-welcome',
            html: '<span class="n8n-chat-widget-welcome-text">' + escapeHtml(message) + '</span><button class="n8n-chat-widget-welcome-close" aria-label="Close">&times;</button>'
        });

        // Insert before the button
        $button.before($welcomeMessage);

        // Handle click on message (opens chat)
        $welcomeMessage.on('click', function(e) {
            if (!$(e.target).hasClass('n8n-chat-widget-welcome-close')) {
                openChatWidget();
                hideWelcomeMessage(true);
            }
        });

        // Handle close button
        $welcomeMessage.find('.n8n-chat-widget-welcome-close').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            hideWelcomeMessage(true);
        });

        // Show message after delay
        welcomeMessageTimeout = setTimeout(function() {
            $welcomeMessage.addClass('n8n-chat-widget-welcome-visible');
        }, delay);
    }

    /**
     * Hide welcome message
     */
    function hideWelcomeMessage(remember) {
        if ($welcomeMessage) {
            $welcomeMessage.removeClass('n8n-chat-widget-welcome-visible');

            // Remember dismissal
            if (remember) {
                localStorage.setItem('n8n_welcome_dismissed', '1');
            }

            // Remove element after animation
            setTimeout(function() {
                $welcomeMessage.remove();
                $welcomeMessage = null;
            }, 500);
        }

        // Clear timeout if still pending
        if (welcomeMessageTimeout) {
            clearTimeout(welcomeMessageTimeout);
            welcomeMessageTimeout = null;
        }
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
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

        // Hide welcome message if visible
        hideWelcomeMessage(false);

        // Track chat open
        trackAnalyticsEvent('chat_open');

        // Update ARIA attributes
        $button.attr('aria-expanded', 'true');
        $popup.attr('aria-hidden', 'false');

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
            // Set focus to close button for accessibility
            $closeBtn.focus();
        }, 10);
    }

    /**
     * Close the chat widget
     */
    function closeChatWidget() {
        $popup.removeClass('n8n-chat-widget-popup-open');
        $container.removeClass('n8n-chat-widget-open');

        // Update ARIA attributes
        $button.attr('aria-expanded', 'false');
        $popup.attr('aria-hidden', 'true');

        // Wait for animation to complete before hiding
        setTimeout(function() {
            $popup.css('display', 'none');
            isWidgetOpen = false;
            // Return focus to the button for accessibility
            $button.focus();
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