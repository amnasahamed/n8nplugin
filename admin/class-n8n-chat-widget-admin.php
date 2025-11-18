<?php
/**
 * The admin-specific functionality of the plugin.
 */
class N8NCHWI_Admin {

    /**
     * Initialize the class and set up settings.
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_settings_page'), 9);
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_notices', array($this, 'admin_notice'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_media_uploader'));
        add_action('admin_init', array($this, 'handle_settings_update'));
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_n8n-chat-widget' !== $hook) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('n8n-chat-widget-admin-css', N8N_CHAT_WIDGET_URL . 'admin/css/n8n-chat-widget-admin.css', array(), N8N_CHAT_WIDGET_VERSION);
        wp_enqueue_script('n8n-chat-widget-admin-js', N8N_CHAT_WIDGET_URL . 'admin/js/n8n-chat-widget-admin.js', array('jquery', 'wp-color-picker'), N8N_CHAT_WIDGET_VERSION, true);

        wp_localize_script('n8n-chat-widget-admin-js', 'n8nchwiAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('n8nchwi_admin_nonce'),
            'positionTemplate' => esc_html__('This chat button will appear in the bottom %s corner of your website.', 'n8n-chat-widget'),
            'confirmReset' => esc_html__('Are you sure you want to reset all settings to defaults? This cannot be undone.', 'n8n-chat-widget'),
            'exportSuccess' => esc_html__('Settings exported successfully!', 'n8n-chat-widget'),
            'importSuccess' => esc_html__('Settings imported successfully! Refreshing page...', 'n8n-chat-widget'),
            'resetSuccess' => esc_html__('Settings reset successfully! Refreshing page...', 'n8n-chat-widget'),
        ));
    }

    /**
     * Enqueue media uploader scripts
     */
    public function enqueue_media_uploader($hook) {
        if ('toplevel_page_n8n-chat-widget' !== $hook) {
            return;
        }
        wp_enqueue_media();
    }

    /**
     * Add settings page to admin menu.
     */
    public function add_settings_page() {
        add_menu_page(
            __('n8n Chat Widget', 'n8n-chat-widget'),
            __('n8n Chat', 'n8n-chat-widget'),
            'manage_options',
            'n8n-chat-widget',
            array($this, 'render_settings_page'),
            'dashicons-format-chat',
            25
        );
    }

    /**
     * Register all settings.
     */
    public function register_settings() {
        // Core settings
        $settings = array(
            'n8n_chat_widget_url' => 'esc_url_raw',
            'n8n_chat_widget_enabled' => array($this, 'sanitize_checkbox'),
            'n8n_chat_widget_position' => array($this, 'sanitize_position'),
            'n8n_chat_widget_title' => 'sanitize_text_field',
            'n8n_chat_widget_color' => 'sanitize_hex_color',
            'n8n_chat_widget_icon' => 'sanitize_text_field',
            'n8n_chat_widget_icon_type' => array($this, 'sanitize_icon_type'),
            'n8n_chat_widget_svg_icon' => 'esc_url_raw',
            'n8n_chat_widget_zoom' => array($this, 'sanitize_zoom'),
            'n8n_chat_widget_theme_preset' => 'sanitize_text_field',
            'n8n_chat_widget_welcome_enabled' => array($this, 'sanitize_checkbox'),
            'n8n_chat_widget_welcome_message' => 'sanitize_textarea_field',
            'n8n_chat_widget_welcome_delay' => 'absint',
            'n8n_chat_widget_auto_open' => array($this, 'sanitize_checkbox'),
            'n8n_chat_widget_auto_open_delay' => 'absint',
            'n8n_chat_widget_sound_enabled' => array($this, 'sanitize_checkbox'),
            'n8n_chat_widget_remember_state' => array($this, 'sanitize_checkbox'),
            'n8n_chat_widget_button_style' => 'sanitize_text_field',
            'n8n_chat_widget_popup_style' => 'sanitize_text_field',
            'n8n_chat_widget_animation_style' => 'sanitize_text_field',
            'n8n_chat_widget_margin_bottom' => 'absint',
            'n8n_chat_widget_margin_side' => 'absint',
            'n8n_chat_widget_mobile_margin_bottom' => 'absint',
            'n8n_chat_widget_mobile_margin_side' => 'absint',
            'n8n_chat_widget_display_mode' => 'sanitize_text_field',
            'n8n_chat_widget_include_pages' => 'sanitize_text_field',
            'n8n_chat_widget_exclude_pages' => 'sanitize_text_field',
            'n8n_chat_widget_show_on_mobile' => array($this, 'sanitize_checkbox'),
            'n8n_chat_widget_powered_by' => array($this, 'sanitize_checkbox'),
            'n8n_chat_widget_powered_text' => 'sanitize_text_field',
            'n8n_chat_widget_header_logo' => 'esc_url_raw',
            'n8n_chat_widget_custom_css' => array($this, 'sanitize_css'),
            'n8n_chat_widget_close_on_outside' => array($this, 'sanitize_checkbox'),
            'n8n_chat_widget_close_on_escape' => array($this, 'sanitize_checkbox'),
        );

        foreach ($settings as $option => $callback) {
            register_setting('n8n_chat_widget_options', $option, array(
                'sanitize_callback' => $callback,
            ));
        }
    }

    public function sanitize_checkbox($input) {
        return ($input === 'yes') ? 'yes' : 'no';
    }

    public function sanitize_position($input) {
        return in_array($input, array('left', 'right')) ? $input : 'right';
    }

    public function sanitize_icon_type($input) {
        return in_array($input, array('emoji', 'svg')) ? $input : 'emoji';
    }

    public function sanitize_zoom($input) {
        return max(50, min(150, absint($input)));
    }

    public function sanitize_css($input) {
        return wp_strip_all_tags($input);
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $options = n8nchwi_get_options();
        $presets = n8nchwi_get_theme_presets();
        ?>
        <div class="wrap n8n-admin-wrap">
            <div class="n8n-admin-header">
                <div class="n8n-admin-header-content">
                    <h1 class="n8n-admin-title">
                        <span class="dashicons dashicons-format-chat"></span>
                        <?php esc_html_e('n8n Chat Widget', 'n8n-chat-widget'); ?>
                        <span class="n8n-version">v<?php echo esc_html(N8N_CHAT_WIDGET_VERSION); ?></span>
                    </h1>
                    <p class="n8n-admin-subtitle"><?php esc_html_e('Configure your AI-powered chat widget with world-class design and functionality', 'n8n-chat-widget'); ?></p>
                </div>
                <div class="n8n-admin-header-actions">
                    <a href="https://far.hn/feature-request" target="_blank" class="n8n-header-link">
                        <span class="dashicons dashicons-lightbulb"></span>
                        <?php esc_html_e('Request Feature', 'n8n-chat-widget'); ?>
                    </a>
                    <a href="https://www.buymeacoffee.com/farhansrambiyan" target="_blank" class="n8n-header-link n8n-header-link-coffee">
                        <span class="dashicons dashicons-heart"></span>
                        <?php esc_html_e('Support', 'n8n-chat-widget'); ?>
                    </a>
                </div>
            </div>

            <div class="n8n-admin-container">
                <div class="n8n-admin-main">
                    <!-- Tab Navigation -->
                    <div class="n8n-tabs">
                        <button class="n8n-tab active" data-tab="general">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php esc_html_e('General', 'n8n-chat-widget'); ?>
                        </button>
                        <button class="n8n-tab" data-tab="appearance">
                            <span class="dashicons dashicons-art"></span>
                            <?php esc_html_e('Appearance', 'n8n-chat-widget'); ?>
                        </button>
                        <button class="n8n-tab" data-tab="behavior">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php esc_html_e('Behavior', 'n8n-chat-widget'); ?>
                        </button>
                        <button class="n8n-tab" data-tab="targeting">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php esc_html_e('Targeting', 'n8n-chat-widget'); ?>
                        </button>
                        <button class="n8n-tab" data-tab="advanced">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <?php esc_html_e('Advanced', 'n8n-chat-widget'); ?>
                        </button>
                    </div>

                    <form action="options.php" method="post" id="n8n-chat-settings-form">
                        <?php settings_fields('n8n_chat_widget_options'); ?>

                        <!-- General Tab -->
                        <div class="n8n-tab-content active" id="tab-general">
                            <div class="n8n-card">
                                <div class="n8n-card-header">
                                    <h3><?php esc_html_e('Connection Settings', 'n8n-chat-widget'); ?></h3>
                                </div>
                                <div class="n8n-card-body">
                                    <div class="n8n-field">
                                        <label for="n8n_chat_widget_url"><?php esc_html_e('n8n Chat URL', 'n8n-chat-widget'); ?></label>
                                        <div class="n8n-input-group">
                                            <input type="url" id="n8n_chat_widget_url" name="n8n_chat_widget_url" value="<?php echo esc_attr($options['url']); ?>" placeholder="https://n8n.example.com/webhook/your-chat-id/chat" />
                                            <button type="button" id="test-connection" class="n8n-btn n8n-btn-secondary"><?php esc_html_e('Test', 'n8n-chat-widget'); ?></button>
                                        </div>
                                        <p class="n8n-field-help"><?php esc_html_e('Enter your n8n chat webhook URL', 'n8n-chat-widget'); ?></p>

                                        <details class="n8n-help-section">
                                            <summary><?php esc_html_e('Need help getting your Chat URL?', 'n8n-chat-widget'); ?></summary>
                                            <div class="n8n-help-content">
                                                <ol>
                                                    <li><?php esc_html_e('Set up a workflow in n8n using the Chat Trigger node', 'n8n-chat-widget'); ?></li>
                                                    <li><?php esc_html_e('Connect it to an AI agent or chain', 'n8n-chat-widget'); ?></li>
                                                    <li><?php esc_html_e('Enable "Make Chat Publicly Available"', 'n8n-chat-widget'); ?></li>
                                                    <li><?php esc_html_e('Set Mode to "Hosted Chat"', 'n8n-chat-widget'); ?></li>
                                                    <li><?php esc_html_e('Activate your workflow and copy the Chat URL', 'n8n-chat-widget'); ?></li>
                                                </ol>
                                                <div class="n8n-help-links">
                                                    <a href="https://docs.n8n.io/integrations/builtin/core-nodes/n8n-nodes-langchain.chattrigger/" target="_blank">
                                                        <span class="dashicons dashicons-media-document"></span> <?php esc_html_e('Documentation', 'n8n-chat-widget'); ?>
                                                    </a>
                                                    <a href="https://www.youtube.com/watch?v=qirFuwSgrfw" target="_blank">
                                                        <span class="dashicons dashicons-video-alt3"></span> <?php esc_html_e('Video Tutorial', 'n8n-chat-widget'); ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </details>
                                    </div>

                                    <div class="n8n-field">
                                        <label class="n8n-toggle">
                                            <input type="checkbox" name="n8n_chat_widget_enabled" value="yes" <?php checked('yes', $options['enabled']); ?> />
                                            <span class="n8n-toggle-slider"></span>
                                            <span class="n8n-toggle-label"><?php esc_html_e('Enable Chat Widget', 'n8n-chat-widget'); ?></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="n8n-card">
                                <div class="n8n-card-header">
                                    <h3><?php esc_html_e('Basic Settings', 'n8n-chat-widget'); ?></h3>
                                </div>
                                <div class="n8n-card-body">
                                    <div class="n8n-field">
                                        <label for="n8n_chat_widget_title"><?php esc_html_e('Widget Title', 'n8n-chat-widget'); ?></label>
                                        <input type="text" id="n8n_chat_widget_title" name="n8n_chat_widget_title" value="<?php echo esc_attr($options['title']); ?>" />
                                    </div>

                                    <div class="n8n-field">
                                        <label for="n8n_chat_widget_position"><?php esc_html_e('Position', 'n8n-chat-widget'); ?></label>
                                        <select id="n8n_chat_widget_position" name="n8n_chat_widget_position">
                                            <option value="right" <?php selected('right', $options['position']); ?>><?php esc_html_e('Bottom Right', 'n8n-chat-widget'); ?></option>
                                            <option value="left" <?php selected('left', $options['position']); ?>><?php esc_html_e('Bottom Left', 'n8n-chat-widget'); ?></option>
                                        </select>
                                    </div>

                                    <div class="n8n-field">
                                        <label><?php esc_html_e('Content Zoom', 'n8n-chat-widget'); ?></label>
                                        <div class="n8n-range-field">
                                            <input type="range" id="n8n_chat_widget_zoom_slider" min="50" max="150" step="5" value="<?php echo esc_attr($options['zoom']); ?>" />
                                            <input type="number" id="n8n_chat_widget_zoom" name="n8n_chat_widget_zoom" value="<?php echo esc_attr($options['zoom']); ?>" min="50" max="150" />
                                            <span>%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Appearance Tab -->
                        <div class="n8n-tab-content" id="tab-appearance">
                            <div class="n8n-card">
                                <div class="n8n-card-header">
                                    <h3><?php esc_html_e('Theme & Colors', 'n8n-chat-widget'); ?></h3>
                                </div>
                                <div class="n8n-card-body">
                                    <div class="n8n-field">
                                        <label><?php esc_html_e('Theme Preset', 'n8n-chat-widget'); ?></label>
                                        <div class="n8n-theme-presets">
                                            <?php foreach ($presets as $key => $preset) : ?>
                                                <?php if ($key !== 'custom') : ?>
                                                <label class="n8n-theme-preset <?php echo $options['theme_preset'] === $key ? 'selected' : ''; ?>">
                                                    <input type="radio" name="n8n_chat_widget_theme_preset" value="<?php echo esc_attr($key); ?>" <?php checked($key, $options['theme_preset']); ?> />
                                                    <span class="n8n-preset-swatch" style="background: <?php echo esc_attr($preset['gradient']); ?>"></span>
                                                    <span class="n8n-preset-name"><?php echo esc_html($preset['name']); ?></span>
                                                </label>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                            <label class="n8n-theme-preset <?php echo $options['theme_preset'] === 'custom' ? 'selected' : ''; ?>">
                                                <input type="radio" name="n8n_chat_widget_theme_preset" value="custom" <?php checked('custom', $options['theme_preset']); ?> />
                                                <span class="n8n-preset-swatch n8n-preset-custom">
                                                    <span class="dashicons dashicons-admin-customizer"></span>
                                                </span>
                                                <span class="n8n-preset-name"><?php esc_html_e('Custom', 'n8n-chat-widget'); ?></span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="n8n-field n8n-custom-color-field" style="<?php echo $options['theme_preset'] !== 'custom' ? 'display:none;' : ''; ?>">
                                        <label for="n8n_chat_widget_color"><?php esc_html_e('Custom Color', 'n8n-chat-widget'); ?></label>
                                        <input type="text" id="n8n_chat_widget_color" name="n8n_chat_widget_color" value="<?php echo esc_attr($options['color']); ?>" class="n8n-color-picker" />
                                    </div>
                                </div>
                            </div>

                            <div class="n8n-card">
                                <div class="n8n-card-header">
                                    <h3><?php esc_html_e('Button Icon', 'n8n-chat-widget'); ?></h3>
                                </div>
                                <div class="n8n-card-body">
                                    <div class="n8n-field">
                                        <div class="n8n-radio-group">
                                            <label>
                                                <input type="radio" name="n8n_chat_widget_icon_type" value="emoji" <?php checked('emoji', $options['icon_type']); ?> />
                                                <?php esc_html_e('Emoji', 'n8n-chat-widget'); ?>
                                            </label>
                                            <label>
                                                <input type="radio" name="n8n_chat_widget_icon_type" value="svg" <?php checked('svg', $options['icon_type']); ?> />
                                                <?php esc_html_e('Custom SVG', 'n8n-chat-widget'); ?>
                                            </label>
                                        </div>
                                    </div>

                                    <div id="emoji-icon-section" class="n8n-field" style="<?php echo $options['icon_type'] !== 'emoji' ? 'display:none;' : ''; ?>">
                                        <label><?php esc_html_e('Select Icon', 'n8n-chat-widget'); ?></label>
                                        <input type="text" id="n8n_chat_widget_icon" name="n8n_chat_widget_icon" value="<?php echo esc_attr($options['icon']); ?>" class="n8n-emoji-input" />
                                        <div class="n8n-emoji-grid">
                                            <?php
                                            $emojis = array('💬', '🤖', '💻', '🔔', '📨', '📝', '🎯', '🔍', '📱', '👋', '🚀', '💡', '⭐', '❤️', '🎉');
                                            foreach ($emojis as $emoji) : ?>
                                                <button type="button" class="n8n-emoji-option <?php echo $options['icon'] === $emoji ? 'selected' : ''; ?>"><?php echo esc_html($emoji); ?></button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div id="svg-icon-section" class="n8n-field" style="<?php echo $options['icon_type'] !== 'svg' ? 'display:none;' : ''; ?>">
                                        <label><?php esc_html_e('SVG Icon', 'n8n-chat-widget'); ?></label>
                                        <div class="n8n-input-group">
                                            <input type="text" id="n8n_chat_widget_svg_icon" name="n8n_chat_widget_svg_icon" value="<?php echo esc_url($options['svg_icon']); ?>" readonly />
                                            <button type="button" id="upload_svg_button" class="n8n-btn n8n-btn-secondary"><?php esc_html_e('Upload', 'n8n-chat-widget'); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="n8n-card">
                                <div class="n8n-card-header">
                                    <h3><?php esc_html_e('Widget Styles', 'n8n-chat-widget'); ?></h3>
                                </div>
                                <div class="n8n-card-body">
                                    <div class="n8n-field">
                                        <label><?php esc_html_e('Button Style', 'n8n-chat-widget'); ?></label>
                                        <div class="n8n-style-options">
                                            <label class="n8n-style-option <?php echo $options['button_style'] === 'circle' ? 'selected' : ''; ?>">
                                                <input type="radio" name="n8n_chat_widget_button_style" value="circle" <?php checked('circle', $options['button_style']); ?> />
                                                <span class="n8n-style-preview n8n-style-circle"></span>
                                                <span><?php esc_html_e('Circle', 'n8n-chat-widget'); ?></span>
                                            </label>
                                            <label class="n8n-style-option <?php echo $options['button_style'] === 'rounded' ? 'selected' : ''; ?>">
                                                <input type="radio" name="n8n_chat_widget_button_style" value="rounded" <?php checked('rounded', $options['button_style']); ?> />
                                                <span class="n8n-style-preview n8n-style-rounded"></span>
                                                <span><?php esc_html_e('Rounded', 'n8n-chat-widget'); ?></span>
                                            </label>
                                            <label class="n8n-style-option <?php echo $options['button_style'] === 'square' ? 'selected' : ''; ?>">
                                                <input type="radio" name="n8n_chat_widget_button_style" value="square" <?php checked('square', $options['button_style']); ?> />
                                                <span class="n8n-style-preview n8n-style-square"></span>
                                                <span><?php esc_html_e('Square', 'n8n-chat-widget'); ?></span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="n8n-field">
                                        <label><?php esc_html_e('Animation Style', 'n8n-chat-widget'); ?></label>
                                        <select name="n8n_chat_widget_animation_style">
                                            <option value="bounce" <?php selected('bounce', $options['animation_style']); ?>><?php esc_html_e('Bounce', 'n8n-chat-widget'); ?></option>
                                            <option value="fade" <?php selected('fade', $options['animation_style']); ?>><?php esc_html_e('Fade', 'n8n-chat-widget'); ?></option>
                                            <option value="slide" <?php selected('slide', $options['animation_style']); ?>><?php esc_html_e('Slide', 'n8n-chat-widget'); ?></option>
                                            <option value="scale" <?php selected('scale', $options['animation_style']); ?>><?php esc_html_e('Scale', 'n8n-chat-widget'); ?></option>
                                            <option value="none" <?php selected('none', $options['animation_style']); ?>><?php esc_html_e('None', 'n8n-chat-widget'); ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="n8n-card">
                                <div class="n8n-card-header">
                                    <h3><?php esc_html_e('Positioning', 'n8n-chat-widget'); ?></h3>
                                </div>
                                <div class="n8n-card-body">
                                    <div class="n8n-field-row">
                                        <div class="n8n-field">
                                            <label><?php esc_html_e('Bottom Margin (px)', 'n8n-chat-widget'); ?></label>
                                            <input type="number" name="n8n_chat_widget_margin_bottom" value="<?php echo esc_attr($options['margin_bottom']); ?>" min="0" max="200" />
                                        </div>
                                        <div class="n8n-field">
                                            <label><?php esc_html_e('Side Margin (px)', 'n8n-chat-widget'); ?></label>
                                            <input type="number" name="n8n_chat_widget_margin_side" value="<?php echo esc_attr($options['margin_side']); ?>" min="0" max="200" />
                                        </div>
                                    </div>
                                    <div class="n8n-field-row">
                                        <div class="n8n-field">
                                            <label><?php esc_html_e('Mobile Bottom (px)', 'n8n-chat-widget'); ?></label>
                                            <input type="number" name="n8n_chat_widget_mobile_margin_bottom" value="<?php echo esc_attr($options['mobile_margin_bottom']); ?>" min="0" max="200" />
                                        </div>
                                        <div class="n8n-field">
                                            <label><?php esc_html_e('Mobile Side (px)', 'n8n-chat-widget'); ?></label>
                                            <input type="number" name="n8n_chat_widget_mobile_margin_side" value="<?php echo esc_attr($options['mobile_margin_side']); ?>" min="0" max="200" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Behavior Tab -->
                        <div class="n8n-tab-content" id="tab-behavior">
                            <div class="n8n-card">
                                <div class="n8n-card-header">
                                    <h3><?php esc_html_e('Welcome Message', 'n8n-chat-widget'); ?></h3>
                                </div>
                                <div class="n8n-card-body">
                                    <div class="n8n-field">
                                        <label class="n8n-toggle">
                                            <input type="checkbox" name="n8n_chat_widget_welcome_enabled" value="yes" <?php checked('yes', $options['welcome_enabled']); ?> />
                                            <span class="n8n-toggle-slider"></span>
                                            <span class="n8n-toggle-label"><?php esc_html_e('Show Welcome Message', 'n8n-chat-widget'); ?></span>
                                        </label>
                                    </div>

                                    <div class="n8n-field">
                                        <label for="n8n_chat_widget_welcome_message"><?php esc_html_e('Message Text', 'n8n-chat-widget'); ?></label>
                                        <textarea id="n8n_chat_widget_welcome_message" name="n8n_chat_widget_welcome_message" rows="3"><?php echo esc_textarea($options['welcome_message']); ?></textarea>
                                    </div>

                                    <div class="n8n-field">
                                        <label><?php esc_html_e('Display Delay (seconds)', 'n8n-chat-widget'); ?></label>
                                        <input type="number" name="n8n_chat_widget_welcome_delay" value="<?php echo esc_attr($options['welcome_delay']); ?>" min="0" max="60" />
                                    </div>
                                </div>
                            </div>

                            <div class="n8n-card">
                                <div class="n8n-card-header">
                                    <h3><?php esc_html_e('Auto-Open', 'n8n-chat-widget'); ?></h3>
                                </div>
                                <div class="n8n-card-body">
                                    <div class="n8n-field">
                                        <label class="n8n-toggle">
                                            <input type="checkbox" name="n8n_chat_widget_auto_open" value="yes" <?php checked('yes', $options['auto_open']); ?> />
                                            <span class="n8n-toggle-slider"></span>
                                            <span class="n8n-toggle-label"><?php esc_html_e('Auto-open Widget', 'n8n-chat-widget'); ?></span>
                                        </label>
                                        <p class="n8n-field-help"><?php esc_html_e('Automatically open the chat widget after a delay', 'n8n-chat-widget'); ?></p>
                                    </div>

                                    <div class="n8n-field">
                                        <label><?php esc_html_e('Auto-open Delay (seconds)', 'n8n-chat-widget'); ?></label>
                                        <input type="number" name="n8n_chat_widget_auto_open_delay" value="<?php echo esc_attr($options['auto_open_delay']); ?>" min="1" max="120" />
                                    </div>
                                </div>
                            </div>

                            <div class="n8n-card">
                                <div class="n8n-card-header">
                                    <h3><?php esc_html_e('Interaction Settings', 'n8n-chat-widget'); ?></h3>
                                </div>
                                <div class="n8n-card-body">
                                    <div class="n8n-field">
                                        <label class="n8n-toggle">
                                            <input type="checkbox" name="n8n_chat_widget_sound_enabled" value="yes" <?php checked('yes', $options['sound_enabled']); ?> />
                                            <span class="n8n-toggle-slider"></span>
                                            <span class="n8n-toggle-label"><?php esc_html_e('Enable Sound Effects', 'n8n-chat-widget'); ?></span>
                                        </label>
                                    </div>

                                    <div class="n8n-field">
                                        <label class="n8n-toggle">
                                            <input type="checkbox" name="n8n_chat_widget_remember_state" value="yes" <?php checked('yes', $options['remember_state']); ?> />
                                            <span class="n8n-toggle-slider"></span>
                                            <span class="n8n-toggle-label"><?php esc_html_e('Remember Open/Closed State', 'n8n-chat-widget'); ?></span>
                                        </label>
                                        <p class="n8n-field-help"><?php esc_html_e('Remember if user opened/closed the widget', 'n8n-chat-widget'); ?></p>
                                    </div>

                                    <div class="n8n-field">
                                        <label class="n8n-toggle">
                                            <input type="checkbox" name="n8n_chat_widget_close_on_outside" value="yes" <?php checked('yes', $options['close_on_outside']); ?> />
                                            <span class="n8n-toggle-slider"></span>
                                            <span class="n8n-toggle-label"><?php esc_html_e('Close on Outside Click', 'n8n-chat-widget'); ?></span>
                                        </label>
                                    </div>

                                    <div class="n8n-field">
                                        <label class="n8n-toggle">
                                            <input type="checkbox" name="n8n_chat_widget_close_on_escape" value="yes" <?php checked('yes', $options['close_on_escape']); ?> />
                                            <span class="n8n-toggle-slider"></span>
                                            <span class="n8n-toggle-label"><?php esc_html_e('Close on ESC Key', 'n8n-chat-widget'); ?></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Targeting Tab -->
                        <div class="n8n-tab-content" id="tab-targeting">
                            <div class="n8n-card">
                                <div class="n8n-card-header">
                                    <h3><?php esc_html_e('Page Display Rules', 'n8n-chat-widget'); ?></h3>
                                </div>
                                <div class="n8n-card-body">
                                    <div class="n8n-field">
                                        <label><?php esc_html_e('Display Mode', 'n8n-chat-widget'); ?></label>
                                        <select name="n8n_chat_widget_display_mode" id="n8n_display_mode">
                                            <option value="all" <?php selected('all', $options['display_mode']); ?>><?php esc_html_e('All Pages', 'n8n-chat-widget'); ?></option>
                                            <option value="include" <?php selected('include', $options['display_mode']); ?>><?php esc_html_e('Only Specific Pages', 'n8n-chat-widget'); ?></option>
                                            <option value="exclude" <?php selected('exclude', $options['display_mode']); ?>><?php esc_html_e('Exclude Specific Pages', 'n8n-chat-widget'); ?></option>
                                        </select>
                                    </div>

                                    <div class="n8n-field n8n-include-pages" style="<?php echo $options['display_mode'] !== 'include' ? 'display:none;' : ''; ?>">
                                        <label><?php esc_html_e('Include Page IDs', 'n8n-chat-widget'); ?></label>
                                        <input type="text" name="n8n_chat_widget_include_pages" value="<?php echo esc_attr($options['include_pages']); ?>" placeholder="1, 2, 3" />
                                        <p class="n8n-field-help"><?php esc_html_e('Comma-separated list of page/post IDs', 'n8n-chat-widget'); ?></p>
                                    </div>

                                    <div class="n8n-field n8n-exclude-pages" style="<?php echo $options['display_mode'] !== 'exclude' ? 'display:none;' : ''; ?>">
                                        <label><?php esc_html_e('Exclude Page IDs', 'n8n-chat-widget'); ?></label>
                                        <input type="text" name="n8n_chat_widget_exclude_pages" value="<?php echo esc_attr($options['exclude_pages']); ?>" placeholder="1, 2, 3" />
                                        <p class="n8n-field-help"><?php esc_html_e('Comma-separated list of page/post IDs', 'n8n-chat-widget'); ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="n8n-card">
                                <div class="n8n-card-header">
                                    <h3><?php esc_html_e('Device Targeting', 'n8n-chat-widget'); ?></h3>
                                </div>
                                <div class="n8n-card-body">
                                    <div class="n8n-field">
                                        <label class="n8n-toggle">
                                            <input type="checkbox" name="n8n_chat_widget_show_on_mobile" value="yes" <?php checked('yes', $options['show_on_mobile']); ?> />
                                            <span class="n8n-toggle-slider"></span>
                                            <span class="n8n-toggle-label"><?php esc_html_e('Show on Mobile Devices', 'n8n-chat-widget'); ?></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Advanced Tab -->
                        <div class="n8n-tab-content" id="tab-advanced">
                            <div class="n8n-card">
                                <div class="n8n-card-header">
                                    <h3><?php esc_html_e('Branding', 'n8n-chat-widget'); ?></h3>
                                </div>
                                <div class="n8n-card-body">
                                    <div class="n8n-field">
                                        <label class="n8n-toggle">
                                            <input type="checkbox" name="n8n_chat_widget_powered_by" value="yes" <?php checked('yes', $options['powered_by']); ?> />
                                            <span class="n8n-toggle-slider"></span>
                                            <span class="n8n-toggle-label"><?php esc_html_e('Show "Powered by" Text', 'n8n-chat-widget'); ?></span>
                                        </label>
                                    </div>

                                    <div class="n8n-field">
                                        <label><?php esc_html_e('Powered by Text', 'n8n-chat-widget'); ?></label>
                                        <input type="text" name="n8n_chat_widget_powered_text" value="<?php echo esc_attr($options['powered_text']); ?>" />
                                    </div>

                                    <div class="n8n-field">
                                        <label><?php esc_html_e('Header Logo', 'n8n-chat-widget'); ?></label>
                                        <div class="n8n-input-group">
                                            <input type="text" id="n8n_chat_widget_header_logo" name="n8n_chat_widget_header_logo" value="<?php echo esc_url($options['header_logo']); ?>" readonly />
                                            <button type="button" id="upload_logo_button" class="n8n-btn n8n-btn-secondary"><?php esc_html_e('Upload', 'n8n-chat-widget'); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="n8n-card">
                                <div class="n8n-card-header">
                                    <h3><?php esc_html_e('Custom CSS', 'n8n-chat-widget'); ?></h3>
                                </div>
                                <div class="n8n-card-body">
                                    <div class="n8n-field">
                                        <textarea id="n8n_chat_widget_custom_css" name="n8n_chat_widget_custom_css" rows="8" class="n8n-code-editor"><?php echo esc_textarea($options['custom_css']); ?></textarea>
                                        <p class="n8n-field-help"><?php esc_html_e('Add custom CSS to further style the widget', 'n8n-chat-widget'); ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="n8n-card">
                                <div class="n8n-card-header">
                                    <h3><?php esc_html_e('Import / Export', 'n8n-chat-widget'); ?></h3>
                                </div>
                                <div class="n8n-card-body">
                                    <div class="n8n-field">
                                        <div class="n8n-button-group">
                                            <button type="button" id="export-settings" class="n8n-btn n8n-btn-secondary">
                                                <span class="dashicons dashicons-download"></span>
                                                <?php esc_html_e('Export Settings', 'n8n-chat-widget'); ?>
                                            </button>
                                            <button type="button" id="import-settings" class="n8n-btn n8n-btn-secondary">
                                                <span class="dashicons dashicons-upload"></span>
                                                <?php esc_html_e('Import Settings', 'n8n-chat-widget'); ?>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="n8n-field" id="import-field" style="display:none;">
                                        <label><?php esc_html_e('Paste Settings JSON', 'n8n-chat-widget'); ?></label>
                                        <textarea id="import-data" rows="5"></textarea>
                                        <button type="button" id="confirm-import" class="n8n-btn n8n-btn-primary"><?php esc_html_e('Import', 'n8n-chat-widget'); ?></button>
                                    </div>
                                </div>
                            </div>

                            <div class="n8n-card n8n-card-danger">
                                <div class="n8n-card-header">
                                    <h3><?php esc_html_e('Danger Zone', 'n8n-chat-widget'); ?></h3>
                                </div>
                                <div class="n8n-card-body">
                                    <div class="n8n-field">
                                        <p class="n8n-field-help"><?php esc_html_e('Reset all settings to their default values. This action cannot be undone.', 'n8n-chat-widget'); ?></p>
                                        <button type="button" id="reset-settings" class="n8n-btn n8n-btn-danger">
                                            <span class="dashicons dashicons-warning"></span>
                                            <?php esc_html_e('Reset to Defaults', 'n8n-chat-widget'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="n8n-form-actions">
                            <button type="submit" class="n8n-btn n8n-btn-primary n8n-btn-lg">
                                <span class="dashicons dashicons-saved"></span>
                                <?php esc_html_e('Save Changes', 'n8n-chat-widget'); ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Preview Sidebar -->
                <div class="n8n-admin-sidebar">
                    <div class="n8n-preview-card">
                        <div class="n8n-preview-header">
                            <h3><?php esc_html_e('Live Preview', 'n8n-chat-widget'); ?></h3>
                        </div>
                        <div class="n8n-preview-body">
                            <div class="n8n-preview-widget">
                                <div class="n8n-preview-popup" id="preview-popup">
                                    <div class="n8n-preview-popup-header" id="preview-header">
                                        <span id="preview-title"><?php echo esc_html($options['title']); ?></span>
                                        <span class="n8n-preview-close">&times;</span>
                                    </div>
                                    <div class="n8n-preview-popup-body">
                                        <?php if (!empty($options['url'])) : ?>
                                            <iframe id="preview-iframe" src="<?php echo esc_url($options['url']); ?>"></iframe>
                                        <?php else : ?>
                                            <div class="n8n-preview-placeholder">
                                                <span class="dashicons dashicons-format-chat"></span>
                                                <p><?php esc_html_e('Enter a chat URL to see the preview', 'n8n-chat-widget'); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($options['powered_by'] === 'yes') : ?>
                                        <div class="n8n-preview-footer">
                                            <?php echo esc_html($options['powered_text']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="n8n-preview-button" id="preview-button">
                                    <span id="preview-icon"><?php echo esc_html($options['icon']); ?></span>
                                </div>
                            </div>
                            <p class="n8n-preview-position" id="preview-position-text">
                                <?php printf(esc_html__('Position: Bottom %s', 'n8n-chat-widget'), esc_html($options['position'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Display admin notice if URL not set
     */
    public function admin_notice() {
        $screen = get_current_screen();

        if ('toplevel_page_n8n-chat-widget' === $screen->id) {
            return;
        }

        if (empty(get_option('n8n_chat_widget_url'))) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php esc_html_e('n8n Chat Widget', 'n8n-chat-widget'); ?>:</strong>
                    <?php esc_html_e('Widget is activated but not configured.', 'n8n-chat-widget'); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=n8n-chat-widget')); ?>">
                        <?php esc_html_e('Configure now', 'n8n-chat-widget'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Handle settings update
     */
    public function handle_settings_update() {
        if (isset($_POST['option_page']) && sanitize_text_field(wp_unslash($_POST['option_page'])) === 'n8n_chat_widget_options') {
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'n8n_chat_widget_options-options')) {
                return;
            }

            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Settings saved successfully!', 'n8n-chat-widget'); ?></p>
                </div>
                <?php
            });
        }
    }
}
