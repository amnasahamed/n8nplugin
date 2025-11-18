/**
 * n8n Chat Widget - World Class Frontend JavaScript
 */
(function($) {
    'use strict';

    // DOM elements
    let $container, $button, $popup, $closeBtn, $iframe, $loading, $welcome;

    // State
    let isWidgetOpen = false;
    let hasLoaded = false;
    let welcomeDismissed = false;
    let autoOpenTriggered = false;

    // Settings
    let settings = {};

    /**
     * Initialize the chat widget
     */
    function initChatWidget() {
        // Get settings from localized data
        if (typeof n8nchwiData !== 'undefined') {
            settings = n8nchwiData;
        }

        // Cache DOM elements
        $container = $('#n8n-chat-widget-container');
        $button = $('#n8n-chat-widget-button');
        $popup = $('#n8n-chat-widget-popup');
        $closeBtn = $('#n8n-chat-widget-close');
        $iframe = $('#n8n-chat-widget-iframe');
        $loading = $('#n8n-chat-widget-loading');
        $welcome = $('#n8n-chat-widget-welcome');

        if (!$container.length) {
            return;
        }

        // Add button style class
        if (settings.buttonStyle) {
            $button.addClass('n8n-button-style-' + settings.buttonStyle);
        }

        // Add animation class
        if (settings.animationStyle && settings.animationStyle !== 'none') {
            $container.addClass('n8n-animation-' + settings.animationStyle);
        }

        // Handle chat button click
        $button.on('click', function() {
            hideWelcomeMessage();
            toggleChatWidget();
        });

        // Handle close button click
        $closeBtn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeChatWidget();
        });

        // Handle clicks outside the widget
        if (settings.closeOnOutside === 'yes') {
            $(document).on('click', function(event) {
                if (isWidgetOpen &&
                    !$(event.target).closest($popup).length &&
                    !$(event.target).closest($button).length &&
                    !$(event.target).closest($welcome).length) {
                    closeChatWidget();
                }
            });
        }

        // Prevent popup clicks from closing
        $popup.on('click', function(e) {
            e.stopPropagation();
        });

        // Handle ESC key press
        if (settings.closeOnEscape === 'yes') {
            $(document).on('keydown', function(event) {
                if (isWidgetOpen && event.key === 'Escape') {
                    closeChatWidget();
                    event.preventDefault();
                }
            });
        }

        // Handle iframe load events
        $iframe.on('load', function() {
            hasLoaded = true;
            $loading.fadeOut(300);

            // Apply zoom
            if (settings.zoom) {
                applyZoomToIframe(settings.zoom);
            }
        });

        // Welcome message close
        $welcome.find('.n8n-chat-widget-welcome-close').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            hideWelcomeMessage();
        });

        // Add mobile class
        checkMobile();

        // Handle window resize
        $(window).on('resize', checkMobile);

        // Check for stored state
        if (settings.rememberState === 'yes') {
            const storedState = localStorage.getItem('n8n_widget_state');
            if (storedState === 'open') {
                setTimeout(function() {
                    openChatWidget();
                }, 500);
                return;
            } else if (storedState === 'closed') {
                return; // Don't show welcome or auto-open
            }
        }

        // Show welcome message
        if (settings.welcomeEnabled === 'yes' && !welcomeDismissed) {
            const delay = (parseInt(settings.welcomeDelay, 10) || 3) * 1000;
            setTimeout(showWelcomeMessage, delay);
        }

        // Auto-open widget
        if (settings.autoOpen === 'yes' && !autoOpenTriggered) {
            const delay = (parseInt(settings.autoOpenDelay, 10) || 5) * 1000;
            setTimeout(function() {
                if (!isWidgetOpen) {
                    hideWelcomeMessage();
                    openChatWidget();
                    autoOpenTriggered = true;
                }
            }, delay);
        }
    }

    /**
     * Check if mobile and add class
     */
    function checkMobile() {
        if (window.innerWidth < 480) {
            $container.addClass('n8n-chat-widget-mobile');
        } else {
            $container.removeClass('n8n-chat-widget-mobile');
        }
    }

    /**
     * Toggle the chat widget
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

        // Play sound
        if (settings.soundEnabled === 'yes') {
            playSound('open');
        }

        // Update ARIA
        $button.attr('aria-expanded', 'true');
        $popup.attr('aria-hidden', 'false');

        // Load iframe on first open
        if (!hasLoaded) {
            var chatUrl = $iframe.attr('data-src');
            if (chatUrl) {
                $loading.show();
                $iframe.attr('src', chatUrl);
            }
        } else {
            if (settings.zoom) {
                applyZoomToIframe(settings.zoom);
            }
        }

        // Animate
        setTimeout(function() {
            $popup.addClass('n8n-chat-widget-popup-open');
            $closeBtn.focus();
        }, 10);

        // Save state
        if (settings.rememberState === 'yes') {
            localStorage.setItem('n8n_widget_state', 'open');
        }
    }

    /**
     * Close the chat widget
     */
    function closeChatWidget() {
        $popup.removeClass('n8n-chat-widget-popup-open');
        $container.removeClass('n8n-chat-widget-open');

        // Update ARIA
        $button.attr('aria-expanded', 'false');
        $popup.attr('aria-hidden', 'true');

        // Wait for animation
        setTimeout(function() {
            $popup.css('display', 'none');
            isWidgetOpen = false;
            $button.focus();
        }, 300);

        // Save state
        if (settings.rememberState === 'yes') {
            localStorage.setItem('n8n_widget_state', 'closed');
        }
    }

    /**
     * Show welcome message
     */
    function showWelcomeMessage() {
        if (welcomeDismissed || isWidgetOpen) return;

        $welcome.addClass('visible');
    }

    /**
     * Hide welcome message
     */
    function hideWelcomeMessage() {
        welcomeDismissed = true;
        $welcome.removeClass('visible');
    }

    /**
     * Apply zoom to iframe
     */
    function applyZoomToIframe(zoomLevel) {
        try {
            const scale = parseInt(zoomLevel, 10) / 100;
            if (isNaN(scale) || scale <= 0) return;

            $iframe.css({
                'transform': 'scale(' + scale + ')',
                'transform-origin': 'top left',
                'width': (100 / scale) + '%',
                'height': (100 / scale) + '%'
            });

            $iframe.parent().css({
                'overflow': 'hidden',
                'height': '100%'
            });

        } catch (e) {
            console.warn('Could not apply zoom to iframe:', e);
        }
    }

    /**
     * Play sound effect
     */
    function playSound(type) {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.type = 'sine';
            gainNode.gain.value = 0.1;

            if (type === 'open') {
                oscillator.frequency.setValueAtTime(880, audioContext.currentTime);
                oscillator.frequency.exponentialRampToValueAtTime(1320, audioContext.currentTime + 0.1);
            } else {
                oscillator.frequency.setValueAtTime(1320, audioContext.currentTime);
                oscillator.frequency.exponentialRampToValueAtTime(880, audioContext.currentTime + 0.1);
            }

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.15);
        } catch (e) {
            // Silently fail if audio not supported
        }
    }

    // Initialize when DOM ready
    $(document).ready(function() {
        initChatWidget();
    });

})(jQuery);
