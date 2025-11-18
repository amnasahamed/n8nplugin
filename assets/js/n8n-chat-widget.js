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
    let triggersFired = {
        exitIntent: false,
        time: false,
        scroll: false
    };
    let prechatCompleted = false;
    let $prechatForm = null;

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

        // Initialize proactive triggers
        initProactiveTriggers();

        // Track widget load
        trackAnalyticsEvent('widget_load');
    }

    /**
     * Initialize proactive triggers (exit intent, time-based, scroll-based)
     */
    function initProactiveTriggers() {
        if (typeof n8nchwiData === 'undefined') {
            return;
        }

        // Check if triggers have been fired in this session
        const sessionKey = 'n8n_triggers_fired';
        const firedTriggers = sessionStorage.getItem(sessionKey);
        if (firedTriggers) {
            triggersFired = JSON.parse(firedTriggers);
        }

        // Exit intent trigger
        if (n8nchwiData.triggerExitIntent === 'yes' && !triggersFired.exitIntent) {
            initExitIntentTrigger();
        }

        // Time-based trigger
        if (n8nchwiData.triggerTimeEnabled === 'yes' && !triggersFired.time) {
            initTimeTrigger();
        }

        // Scroll-based trigger
        if (n8nchwiData.triggerScrollEnabled === 'yes' && !triggersFired.scroll) {
            initScrollTrigger();
        }
    }

    /**
     * Initialize exit intent trigger
     */
    function initExitIntentTrigger() {
        $(document).on('mouseleave', function(e) {
            // Only trigger when mouse leaves from the top of the viewport
            if (e.clientY <= 0 && !isWidgetOpen && !triggersFired.exitIntent) {
                triggersFired.exitIntent = true;
                saveTriggerState();
                playNotificationSound();
                openChatWidget();
                trackAnalyticsEvent('trigger_exit_intent');
            }
        });
    }

    /**
     * Initialize time-based trigger
     */
    function initTimeTrigger() {
        const delay = (parseInt(n8nchwiData.triggerTimeDelay, 10) || 30) * 1000;

        setTimeout(function() {
            if (!isWidgetOpen && !triggersFired.time) {
                triggersFired.time = true;
                saveTriggerState();
                playNotificationSound();
                openChatWidget();
                trackAnalyticsEvent('trigger_time');
            }
        }, delay);
    }

    /**
     * Initialize scroll-based trigger
     */
    function initScrollTrigger() {
        const triggerPercent = parseInt(n8nchwiData.triggerScrollPercent, 10) || 50;

        $(window).on('scroll.n8nTrigger', function() {
            if (triggersFired.scroll || isWidgetOpen) {
                return;
            }

            const scrollTop = $(window).scrollTop();
            const docHeight = $(document).height();
            const winHeight = $(window).height();
            const scrollPercent = (scrollTop / (docHeight - winHeight)) * 100;

            if (scrollPercent >= triggerPercent) {
                triggersFired.scroll = true;
                saveTriggerState();
                $(window).off('scroll.n8nTrigger');
                playNotificationSound();
                openChatWidget();
                trackAnalyticsEvent('trigger_scroll');
            }
        });
    }

    /**
     * Save trigger state to session storage
     */
    function saveTriggerState() {
        sessionStorage.setItem('n8n_triggers_fired', JSON.stringify(triggersFired));
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

        // Check if pre-chat form is needed
        if (typeof n8nchwiData !== 'undefined' &&
            n8nchwiData.prechatEnabled === 'yes' &&
            !prechatCompleted) {
            showPrechatForm();
        } else {
            showChatIframe();
        }

        // Animate opening
        setTimeout(function() {
            $popup.addClass('n8n-chat-widget-popup-open');
            // Set focus to close button for accessibility
            $closeBtn.focus();
        }, 10);
    }

    /**
     * Show the chat iframe
     */
    function showChatIframe() {
        // Hide pre-chat form if visible
        if ($prechatForm) {
            $prechatForm.remove();
            $prechatForm = null;
        }

        // Show frame container
        $iframe.parent().show();

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
    }

    /**
     * Show the pre-chat form
     */
    function showPrechatForm() {
        // Hide iframe container
        $iframe.parent().hide();

        // Build form HTML
        var formHtml = '<div class="n8n-prechat-form">';
        formHtml += '<h3 class="n8n-prechat-title">' + escapeHtml(n8nchwiData.prechatTitle) + '</h3>';
        formHtml += '<div class="n8n-prechat-fields">';

        // Name field
        if (n8nchwiData.prechatName === 'yes') {
            formHtml += '<div class="n8n-prechat-field">';
            formHtml += '<label for="n8n-prechat-name">Name</label>';
            formHtml += '<input type="text" id="n8n-prechat-name" name="name" placeholder="Your name" />';
            formHtml += '</div>';
        }

        // Email field
        if (n8nchwiData.prechatEmail === 'yes') {
            formHtml += '<div class="n8n-prechat-field">';
            formHtml += '<label for="n8n-prechat-email">Email</label>';
            formHtml += '<input type="email" id="n8n-prechat-email" name="email" placeholder="your@email.com" />';
            formHtml += '</div>';
        }

        // Phone field
        if (n8nchwiData.prechatPhone === 'yes') {
            formHtml += '<div class="n8n-prechat-field">';
            formHtml += '<label for="n8n-prechat-phone">Phone</label>';
            formHtml += '<input type="tel" id="n8n-prechat-phone" name="phone" placeholder="Your phone number" />';
            formHtml += '</div>';
        }

        // Message field
        if (n8nchwiData.prechatMessage === 'yes') {
            formHtml += '<div class="n8n-prechat-field">';
            formHtml += '<label for="n8n-prechat-message">Message</label>';
            formHtml += '<textarea id="n8n-prechat-message" name="message" placeholder="How can we help you?"></textarea>';
            formHtml += '</div>';
        }

        formHtml += '</div>';
        formHtml += '<button type="button" class="n8n-prechat-submit">' + escapeHtml(n8nchwiData.prechatButton) + '</button>';
        formHtml += '</div>';

        // Create and append form
        $prechatForm = $(formHtml);
        $popup.find('.n8n-chat-widget-frame-container').after($prechatForm);

        // Handle form submission
        $prechatForm.find('.n8n-prechat-submit').on('click', function() {
            submitPrechatForm();
        });

        // Handle enter key on inputs
        $prechatForm.find('input').on('keypress', function(e) {
            if (e.which === 13) {
                submitPrechatForm();
            }
        });
    }

    /**
     * Submit pre-chat form
     */
    function submitPrechatForm() {
        var $submitBtn = $prechatForm.find('.n8n-prechat-submit');
        $submitBtn.prop('disabled', true).text('Sending...');

        // Gather form data
        var formData = {
            action: 'n8nchwi_save_lead',
            nonce: n8nchwiData.prechatNonce,
            name: $prechatForm.find('#n8n-prechat-name').val() || '',
            email: $prechatForm.find('#n8n-prechat-email').val() || '',
            phone: $prechatForm.find('#n8n-prechat-phone').val() || '',
            message: $prechatForm.find('#n8n-prechat-message').val() || '',
            page_url: window.location.href
        };

        // Validate email if provided
        if (formData.email && !isValidEmail(formData.email)) {
            $prechatForm.find('#n8n-prechat-email').addClass('error');
            $submitBtn.prop('disabled', false).text(n8nchwiData.prechatButton);
            return;
        }

        // Send AJAX request
        $.ajax({
            url: n8nchwiData.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    prechatCompleted = true;
                    trackAnalyticsEvent('prechat_submit');
                    showChatIframe();
                } else {
                    $submitBtn.prop('disabled', false).text(n8nchwiData.prechatButton);
                }
            },
            error: function() {
                // Still proceed to chat on error
                prechatCompleted = true;
                showChatIframe();
            }
        });
    }

    /**
     * Validate email format
     */
    function isValidEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    /**
     * Play notification sound using Web Audio API
     */
    function playNotificationSound() {
        if (typeof n8nchwiData === 'undefined' || n8nchwiData.soundEnabled !== 'yes') {
            return;
        }

        try {
            var audioContext = new (window.AudioContext || window.webkitAudioContext)();
            var oscillator = audioContext.createOscillator();
            var gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            var volume = (n8nchwiData.soundVolume / 100) * 0.3;
            gainNode.gain.value = volume;

            var type = n8nchwiData.soundType || 'gentle';

            // Different sound types
            switch (type) {
                case 'gentle':
                    oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                    oscillator.frequency.exponentialRampToValueAtTime(600, audioContext.currentTime + 0.1);
                    oscillator.type = 'sine';
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
                    oscillator.start(audioContext.currentTime);
                    oscillator.stop(audioContext.currentTime + 0.3);
                    break;
                case 'chime':
                    oscillator.frequency.setValueAtTime(1200, audioContext.currentTime);
                    oscillator.frequency.exponentialRampToValueAtTime(800, audioContext.currentTime + 0.15);
                    oscillator.type = 'sine';
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.4);
                    oscillator.start(audioContext.currentTime);
                    oscillator.stop(audioContext.currentTime + 0.4);
                    break;
                case 'pop':
                    oscillator.frequency.setValueAtTime(600, audioContext.currentTime);
                    oscillator.frequency.exponentialRampToValueAtTime(200, audioContext.currentTime + 0.08);
                    oscillator.type = 'sine';
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.15);
                    oscillator.start(audioContext.currentTime);
                    oscillator.stop(audioContext.currentTime + 0.15);
                    break;
                case 'bell':
                    oscillator.frequency.setValueAtTime(1000, audioContext.currentTime);
                    oscillator.type = 'triangle';
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
                    oscillator.start(audioContext.currentTime);
                    oscillator.stop(audioContext.currentTime + 0.5);
                    break;
                default:
                    oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                    oscillator.type = 'sine';
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
                    oscillator.start(audioContext.currentTime);
                    oscillator.stop(audioContext.currentTime + 0.3);
            }
        } catch (e) {
            // Silently fail
        }
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