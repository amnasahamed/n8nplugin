/**
 * N8N Chat Widget Front-end JavaScript (Vanilla JS - No jQuery)
 */
(function() {
    'use strict';

    // DOM elements
    var container, button, popup, closeBtn, iframe, loading, welcomeMessage;

    // Track widget state
    var isWidgetOpen = false;
    var hasLoaded = false;
    var welcomeMessageTimeout = null;
    var triggersFired = {
        exitIntent: false,
        time: false,
        scroll: false
    };
    var prechatCompleted = false;
    var prechatForm = null;

    /**
     * Initialize the chat widget
     */
    function initChatWidget() {
        // Cache DOM elements
        container = document.getElementById('n8n-chat-widget-container');
        button = document.getElementById('n8n-chat-widget-button');
        popup = document.getElementById('n8n-chat-widget-popup');
        closeBtn = document.getElementById('n8n-chat-widget-close');
        iframe = document.getElementById('n8n-chat-widget-iframe');
        loading = document.getElementById('n8n-chat-widget-loading');

        if (!container) {
            return;
        }

        // Handle chat button click
        button.addEventListener('click', toggleChatWidget);

        // Handle close button click
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeChatWidget();
        });

        // Handle clicks outside the widget
        document.addEventListener('click', function(event) {
            if (isWidgetOpen &&
                !popup.contains(event.target) &&
                !button.contains(event.target)) {
                closeChatWidget();
            }
        });

        // Prevent popup clicks from closing
        popup.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Handle ESC key press
        document.addEventListener('keydown', function(event) {
            if (isWidgetOpen && event.key === 'Escape') {
                closeChatWidget();
                event.preventDefault();
            }
        });

        // Handle iframe load events
        iframe.addEventListener('load', function() {
            hasLoaded = true;
            fadeOut(loading, 300);

            // Apply zoom if available
            if (typeof n8nchwiData !== 'undefined' && n8nchwiData.zoom) {
                applyZoomToIframe(n8nchwiData.zoom);
            }
        });

        // Add mobile class for smaller screens
        if (window.innerWidth < 480) {
            container.classList.add('n8n-chat-widget-mobile');
        }

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth < 480) {
                container.classList.add('n8n-chat-widget-mobile');
            } else {
                container.classList.remove('n8n-chat-widget-mobile');
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
        var sessionKey = 'n8n_triggers_fired';
        var firedTriggers = sessionStorage.getItem(sessionKey);
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
        document.addEventListener('mouseleave', function(e) {
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
        var delay = (parseInt(n8nchwiData.triggerTimeDelay, 10) || 30) * 1000;

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
        var triggerPercent = parseInt(n8nchwiData.triggerScrollPercent, 10) || 50;

        function onScroll() {
            if (triggersFired.scroll || isWidgetOpen) {
                return;
            }

            var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            var docHeight = Math.max(
                document.body.scrollHeight, document.documentElement.scrollHeight,
                document.body.offsetHeight, document.documentElement.offsetHeight
            );
            var winHeight = window.innerHeight;
            var scrollPercent = (scrollTop / (docHeight - winHeight)) * 100;

            if (scrollPercent >= triggerPercent) {
                triggersFired.scroll = true;
                saveTriggerState();
                window.removeEventListener('scroll', onScroll);
                playNotificationSound();
                openChatWidget();
                trackAnalyticsEvent('trigger_scroll');
            }
        }

        window.addEventListener('scroll', onScroll);
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
        var xhr = new XMLHttpRequest();
        xhr.open('POST', n8nchwiData.ajaxUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send(
            'action=n8nchwi_record_analytics' +
            '&nonce=' + encodeURIComponent(n8nchwiData.analyticsNonce) +
            '&event_type=' + encodeURIComponent(eventType)
        );
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
        var storageKey = 'n8n_welcome_dismissed';
        if (localStorage.getItem(storageKey)) {
            return;
        }

        // Get message and delay
        var message = n8nchwiData.welcomeMessage || 'Hi there! How can I help you today?';
        var delay = (parseInt(n8nchwiData.welcomeDelay, 10) || 3) * 1000;

        // Create welcome message element
        welcomeMessage = document.createElement('div');
        welcomeMessage.className = 'n8n-chat-widget-welcome';
        welcomeMessage.innerHTML = '<span class="n8n-chat-widget-welcome-text">' + escapeHtml(message) + '</span><button class="n8n-chat-widget-welcome-close" aria-label="Close">&times;</button>';

        // Insert before the button
        button.parentNode.insertBefore(welcomeMessage, button);

        // Handle click on message (opens chat)
        welcomeMessage.addEventListener('click', function(e) {
            if (!e.target.classList.contains('n8n-chat-widget-welcome-close')) {
                openChatWidget();
                hideWelcomeMessage(true);
            }
        });

        // Handle close button
        var closeWelcomeBtn = welcomeMessage.querySelector('.n8n-chat-widget-welcome-close');
        closeWelcomeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            hideWelcomeMessage(true);
        });

        // Show message after delay
        welcomeMessageTimeout = setTimeout(function() {
            welcomeMessage.classList.add('n8n-chat-widget-welcome-visible');
        }, delay);
    }

    /**
     * Hide welcome message
     */
    function hideWelcomeMessage(remember) {
        if (welcomeMessage) {
            welcomeMessage.classList.remove('n8n-chat-widget-welcome-visible');

            // Remember dismissal
            if (remember) {
                localStorage.setItem('n8n_welcome_dismissed', '1');
            }

            // Remove element after animation
            setTimeout(function() {
                if (welcomeMessage && welcomeMessage.parentNode) {
                    welcomeMessage.parentNode.removeChild(welcomeMessage);
                }
                welcomeMessage = null;
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
        var div = document.createElement('div');
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
        popup.style.display = 'flex';
        container.classList.add('n8n-chat-widget-open');
        isWidgetOpen = true;

        // Hide welcome message if visible
        hideWelcomeMessage(false);

        // Track chat open
        trackAnalyticsEvent('chat_open');

        // Update ARIA attributes
        button.setAttribute('aria-expanded', 'true');
        popup.setAttribute('aria-hidden', 'false');

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
            popup.classList.add('n8n-chat-widget-popup-open');
            // Set focus to close button for accessibility
            closeBtn.focus();
        }, 10);
    }

    /**
     * Show the chat iframe
     */
    function showChatIframe() {
        // Hide pre-chat form if visible
        if (prechatForm && prechatForm.parentNode) {
            prechatForm.parentNode.removeChild(prechatForm);
            prechatForm = null;
        }

        // Show frame container
        var frameContainer = iframe.parentNode;
        frameContainer.style.display = '';

        // Only load the iframe content when opened for the first time
        if (!hasLoaded) {
            var chatUrl = iframe.getAttribute('data-src');
            if (chatUrl) {
                loading.style.display = 'block';
                iframe.setAttribute('src', chatUrl);
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
        iframe.parentNode.style.display = 'none';

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
        var tempDiv = document.createElement('div');
        tempDiv.innerHTML = formHtml;
        prechatForm = tempDiv.firstChild;

        var frameContainer = popup.querySelector('.n8n-chat-widget-frame-container');
        frameContainer.parentNode.insertBefore(prechatForm, frameContainer.nextSibling);

        // Handle form submission
        var submitBtn = prechatForm.querySelector('.n8n-prechat-submit');
        submitBtn.addEventListener('click', function() {
            submitPrechatForm();
        });

        // Handle enter key on inputs
        var inputs = prechatForm.querySelectorAll('input');
        for (var i = 0; i < inputs.length; i++) {
            inputs[i].addEventListener('keypress', function(e) {
                if (e.which === 13 || e.keyCode === 13) {
                    submitPrechatForm();
                }
            });
        }
    }

    /**
     * Submit pre-chat form
     */
    function submitPrechatForm() {
        var submitBtn = prechatForm.querySelector('.n8n-prechat-submit');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';

        // Gather form data
        var nameInput = prechatForm.querySelector('#n8n-prechat-name');
        var emailInput = prechatForm.querySelector('#n8n-prechat-email');
        var phoneInput = prechatForm.querySelector('#n8n-prechat-phone');
        var messageInput = prechatForm.querySelector('#n8n-prechat-message');

        var formData = {
            name: nameInput ? nameInput.value : '',
            email: emailInput ? emailInput.value : '',
            phone: phoneInput ? phoneInput.value : '',
            message: messageInput ? messageInput.value : '',
            page_url: window.location.href
        };

        // Validate email if provided
        if (formData.email && !isValidEmail(formData.email)) {
            if (emailInput) emailInput.classList.add('error');
            submitBtn.disabled = false;
            submitBtn.textContent = n8nchwiData.prechatButton;
            return;
        }

        // Send AJAX request
        var xhr = new XMLHttpRequest();
        xhr.open('POST', n8nchwiData.ajaxUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            prechatCompleted = true;
                            trackAnalyticsEvent('prechat_submit');
                            showChatIframe();
                        } else {
                            submitBtn.disabled = false;
                            submitBtn.textContent = n8nchwiData.prechatButton;
                        }
                    } catch (e) {
                        // Still proceed to chat on error
                        prechatCompleted = true;
                        showChatIframe();
                    }
                } else {
                    // Still proceed to chat on error
                    prechatCompleted = true;
                    showChatIframe();
                }
            }
        };

        var postData = 'action=n8nchwi_save_lead' +
            '&nonce=' + encodeURIComponent(n8nchwiData.prechatNonce) +
            '&name=' + encodeURIComponent(formData.name) +
            '&email=' + encodeURIComponent(formData.email) +
            '&phone=' + encodeURIComponent(formData.phone) +
            '&message=' + encodeURIComponent(formData.message) +
            '&page_url=' + encodeURIComponent(formData.page_url);

        xhr.send(postData);
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
        popup.classList.remove('n8n-chat-widget-popup-open');
        container.classList.remove('n8n-chat-widget-open');

        // Update ARIA attributes
        button.setAttribute('aria-expanded', 'false');
        popup.setAttribute('aria-hidden', 'true');

        // Wait for animation to complete before hiding
        setTimeout(function() {
            popup.style.display = 'none';
            isWidgetOpen = false;
            // Return focus to the button for accessibility
            button.focus();
        }, 300);
    }

    /**
     * Apply zoom scale to the iframe content
     */
    function applyZoomToIframe(zoomLevel) {
        try {
            var scale = parseInt(zoomLevel, 10) / 100;
            if (isNaN(scale) || scale <= 0) return;

            // Apply transform to the iframe
            iframe.style.transform = 'scale(' + scale + ')';
            iframe.style.transformOrigin = 'top left';
            iframe.style.width = (100/scale) + '%';
            iframe.style.height = (100/scale) + '%';

            // Adjust the container to handle overflow properly
            iframe.parentNode.style.overflow = 'hidden';
            iframe.parentNode.style.height = '100%';

        } catch (e) {
            console.warn('Could not apply zoom to iframe:', e);
        }
    }

    /**
     * Fade out element
     */
    function fadeOut(element, duration) {
        if (!element) return;
        element.style.transition = 'opacity ' + duration + 'ms';
        element.style.opacity = '0';
        setTimeout(function() {
            element.style.display = 'none';
            element.style.opacity = '';
            element.style.transition = '';
        }, duration);
    }

    // Initialize the widget when the DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChatWidget);
    } else {
        initChatWidget();
    }

})();
