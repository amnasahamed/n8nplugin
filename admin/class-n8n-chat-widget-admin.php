<?php
/**
 * The admin-specific functionality of the plugin.
 */
class N8NCHWI_Admin {

    /**
     * Initialize the class and set up settings.
     */
    public function init() {
        // Change hook priority for admin_menu to make it run earlier
        add_action('admin_menu', array($this, 'add_settings_page'), 9);
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Add an admin notice to make settings more visible
        add_action('admin_notices', array($this, 'admin_notice'));

        // Add media uploader scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_media_uploader'));

        // Handle settings update
        add_action('admin_init', array($this, 'handle_settings_update'));

        // AJAX handler for connection testing
        add_action('wp_ajax_n8nchwi_test_connection', array($this, 'ajax_test_connection'));
    }

    /**
     * AJAX handler to test n8n chat URL connection
     */
    public function ajax_test_connection() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'n8nchwi_test_connection')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'n8n-chat-widget')));
        }

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'n8n-chat-widget')));
        }

        // Get URL from request
        $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';

        if (empty($url)) {
            wp_send_json_error(array('message' => __('Please enter a URL to test.', 'n8n-chat-widget')));
        }

        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(array('message' => __('Invalid URL format.', 'n8n-chat-widget')));
        }

        // Test the connection
        $response = wp_remote_head($url, array(
            'timeout' => 10,
            'sslverify' => true,
            'user-agent' => 'n8n-chat-widget/' . N8N_CHAT_WIDGET_VERSION,
        ));

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            wp_send_json_error(array(
                'message' => sprintf(__('Connection failed: %s', 'n8n-chat-widget'), $error_message)
            ));
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code >= 200 && $response_code < 400) {
            wp_send_json_success(array(
                'message' => __('Connection successful! Your n8n chat URL is reachable.', 'n8n-chat-widget'),
                'code' => $response_code
            ));
        } else {
            wp_send_json_error(array(
                'message' => sprintf(__('Server returned error code: %d', 'n8n-chat-widget'), $response_code),
                'code' => $response_code
            ));
        }
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_n8n-chat-widget' !== $hook) {
            return;
        }
        
        // Add WordPress color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Add custom admin styles
        wp_enqueue_style('n8n-chat-widget-admin-css', N8N_CHAT_WIDGET_URL . 'admin/css/n8n-chat-widget-admin.css', array(), N8N_CHAT_WIDGET_VERSION);
        
        // Add custom admin script (consolidated - includes all admin functionality)
        wp_enqueue_script('n8n-chat-widget-admin-js', N8N_CHAT_WIDGET_URL . 'admin/js/n8n-chat-widget-admin.js', array('jquery', 'wp-color-picker'), N8N_CHAT_WIDGET_VERSION, true);

        // Localize script with translated strings and AJAX data
        wp_localize_script('n8n-chat-widget-admin-js', 'n8nchwiSettings', array(
            'positionTemplate' => /* translators: %s: position of the chat button (left or right) */ esc_html__('This chat button will appear in the bottom %s corner of your website.', 'n8n-chat-widget'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'testConnectionNonce' => wp_create_nonce('n8nchwi_test_connection'),
            'strings' => array(
                'testing' => __('Testing...', 'n8n-chat-widget'),
                'testConnection' => __('Test Connection', 'n8n-chat-widget'),
                'connected' => __('Connected', 'n8n-chat-widget'),
                'failed' => __('Failed', 'n8n-chat-widget'),
            )
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
        // Only add as a top-level menu for maximum visibility
        add_menu_page(
            __('n8n Chat Widget', 'n8n-chat-widget'),
            __('n8n Chat Widget', 'n8n-chat-widget'),
            'manage_options',
            'n8n-chat-widget',
            array($this, 'render_settings_page'),
            'dashicons-format-chat',
            25  // Higher priority position
        );
    }

    /**
     * Register settings fields.
     */
    public function register_settings() {
        register_setting('n8n_chat_widget_options', 'n8n_chat_widget_url', array(
            'sanitize_callback' => 'esc_url_raw',
        ));
        
        register_setting('n8n_chat_widget_options', 'n8n_chat_widget_enabled', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => 'yes',
        ));
        
        register_setting('n8n_chat_widget_options', 'n8n_chat_widget_position', array(
            'sanitize_callback' => array($this, 'sanitize_position'),
            'default' => 'right',
        ));
        
        register_setting('n8n_chat_widget_options', 'n8n_chat_widget_title', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Chat Support',
        ));
        
        register_setting('n8n_chat_widget_options', 'n8n_chat_widget_color', array(
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '#45d3d3',
        ));
        
        register_setting('n8n_chat_widget_options', 'n8n_chat_widget_icon', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '💬',
        ));
        
        register_setting('n8n_chat_widget_options', 'n8n_chat_widget_icon_type', array(
            'sanitize_callback' => array($this, 'sanitize_icon_type'),
            'default' => 'emoji',
        ));
        
        register_setting('n8n_chat_widget_options', 'n8n_chat_widget_svg_icon', array(
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        ));
        
        register_setting('n8n_chat_widget_options', 'n8n_chat_widget_zoom', array(
            'sanitize_callback' => array($this, 'sanitize_zoom'),
            'default' => '100',
        ));

        // Page targeting settings
        register_setting('n8n_chat_widget_options', 'n8n_chat_widget_targeting_mode', array(
            'sanitize_callback' => array($this, 'sanitize_targeting_mode'),
            'default' => 'all',
        ));

        register_setting('n8n_chat_widget_options', 'n8n_chat_widget_targeting_pages', array(
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => '',
        ));

        register_setting('n8n_chat_widget_options', 'n8n_chat_widget_hide_on_mobile', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => 'no',
        ));

        add_settings_section(
            'n8n_chat_widget_general',
            __('General Settings', 'n8n-chat-widget'),
            array($this, 'render_section_info'),
            'n8n-chat-widget'
        );

        add_settings_field(
            'n8n_chat_widget_url',
            __('n8n Chat URL', 'n8n-chat-widget'),
            array($this, 'render_url_field'),
            'n8n-chat-widget',
            'n8n_chat_widget_general'
        );

        add_settings_field(
            'n8n_chat_widget_enabled',
            __('Enable Chat Widget', 'n8n-chat-widget'),
            array($this, 'render_enabled_field'),
            'n8n-chat-widget',
            'n8n_chat_widget_general'
        );

        add_settings_field(
            'n8n_chat_widget_position',
            __('Widget Position', 'n8n-chat-widget'),
            array($this, 'render_position_field'),
            'n8n-chat-widget',
            'n8n_chat_widget_general'
        );
        
        add_settings_field(
            'n8n_chat_widget_title',
            __('Chat Widget Title', 'n8n-chat-widget'),
            array($this, 'render_title_field'),
            'n8n-chat-widget',
            'n8n_chat_widget_general'
        );
        
        add_settings_field(
            'n8n_chat_widget_color',
            __('Widget Color', 'n8n-chat-widget'),
            array($this, 'render_color_field'),
            'n8n-chat-widget',
            'n8n_chat_widget_general'
        );
        
        add_settings_field(
            'n8n_chat_widget_icon_settings',
            __('Chat Icon', 'n8n-chat-widget'),
            array($this, 'render_icon_settings_field'),
            'n8n-chat-widget',
            'n8n_chat_widget_general'
        );
        
        add_settings_field(
            'n8n_chat_widget_zoom',
            __('Chat Content Zoom', 'n8n-chat-widget'),
            array($this, 'render_zoom_field'),
            'n8n-chat-widget',
            'n8n_chat_widget_general'
        );
    }

    /**
     * Sanitize checkbox values.
     */
    public function sanitize_checkbox($input) {
        return ($input === 'yes') ? 'yes' : 'no';
    }

    /**
     * Sanitize position value.
     */
    public function sanitize_position($input) {
        $valid_positions = array('left', 'right');
        return in_array($input, $valid_positions) ? $input : 'right';
    }
    
    /**
     * Sanitize icon type.
     */
    public function sanitize_icon_type($input) {
        $valid_types = array('emoji', 'svg');
        return in_array($input, $valid_types) ? $input : 'emoji';
    }
    
    /**
     * Sanitize zoom value.
     */
    public function sanitize_zoom($input) {
        $input = absint($input);
        return max(50, min(150, $input)); // Limit zoom between 50% and 150%
    }

    /**
     * Sanitize targeting mode.
     */
    public function sanitize_targeting_mode($input) {
        $valid_modes = array('all', 'include', 'exclude');
        return in_array($input, $valid_modes) ? $input : 'all';
    }

    /**
     * Render the section info.
     */
    public function render_section_info() {
        echo '<p>' . esc_html__('Configure your n8n Chat Widget settings below.', 'n8n-chat-widget') . '</p>';
    }

    /**
     * Render URL field.
     */
    public function render_url_field() {
        $url = get_option('n8n_chat_widget_url');
        ?>
        <div style="display: flex; align-items: center;">
            <input type="url" id="n8n_chat_widget_url" name="n8n_chat_widget_url" value="<?php echo esc_attr($url); ?>" class="regular-text" style="flex: 1; margin-right: 10px;" placeholder="https://n8n.example.com/webhook/your-chat-id/chat" />
            <button type="button" id="load-preview-button" class="button button-secondary"><?php esc_html_e('Save & Preview', 'n8n-chat-widget'); ?></button>
        </div>
        <p class="description"><?php esc_html_e('Enter the full URL of your n8n chat webhook.', 'n8n-chat-widget'); ?></p>
        <details class="chat-url-help" style="margin-top: 10px;">
            <summary style="cursor: pointer; color: #0073aa; font-weight: 500; margin-bottom: 10px;"><?php esc_html_e('Need help getting your Chat URL?', 'n8n-chat-widget'); ?></summary>
            <div style="padding: 15px; background: #f8f8f8; border-left: 4px solid #45d3d3; border-radius: 4px; margin-top: 5px;">
                <p><strong><?php esc_html_e('How to get your n8n Chat URL:', 'n8n-chat-widget'); ?></strong></p>
                <ol style="margin-left: 20px; margin-bottom: 10px;">
                    <li><?php esc_html_e('Set up a workflow in n8n using the Chat Trigger node', 'n8n-chat-widget'); ?></li>
                    <li><?php esc_html_e('Connect it to an AI agent or chain', 'n8n-chat-widget'); ?></li>
                    <li><?php esc_html_e('Enable "Make Chat Publicly Available" in the Chat Trigger node', 'n8n-chat-widget'); ?></li>
                    <li><?php esc_html_e('Set Mode to "Hosted Chat" (recommended)', 'n8n-chat-widget'); ?></li>
                    <li><?php esc_html_e('Activate your workflow and copy the Chat URL', 'n8n-chat-widget'); ?></li>
                </ol>
                <p>
                    <a href="https://docs.n8n.io/integrations/builtin/core-nodes/n8n-nodes-langchain.chattrigger/" target="_blank" style="margin-right: 15px;"><span class="dashicons dashicons-media-document" style="margin-right: 3px;"></span><?php esc_html_e('n8n Chat Trigger Documentation', 'n8n-chat-widget'); ?></a>
                    <a href="https://www.youtube.com/watch?v=qirFuwSgrfw" target="_blank"><span class="dashicons dashicons-video-alt3" style="margin-right: 3px;"></span><?php esc_html_e('Video Tutorial', 'n8n-chat-widget'); ?></a>
                </p>
            </div>
        </details>
        <?php
    }

    /**
     * Render enabled field.
     */
    public function render_enabled_field() {
        $enabled = get_option('n8n_chat_widget_enabled', 'yes');
        ?>
        <label for="n8n_chat_widget_enabled">
            <input type="checkbox" id="n8n_chat_widget_enabled" name="n8n_chat_widget_enabled" value="yes" <?php checked('yes', $enabled); ?> />
            <?php esc_html_e('Enable chat widget on the website', 'n8n-chat-widget'); ?>
        </label>
        <?php
    }

    /**
     * Render position field.
     */
    public function render_position_field() {
        $position = get_option('n8n_chat_widget_position', 'right');
        ?>
        <select id="n8n_chat_widget_position" name="n8n_chat_widget_position">
            <option value="right" <?php selected('right', $position); ?>><?php esc_html_e('Right', 'n8n-chat-widget'); ?></option>
            <option value="left" <?php selected('left', $position); ?>><?php esc_html_e('Left', 'n8n-chat-widget'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('Select the position of the chat widget button.', 'n8n-chat-widget'); ?></p>
        <?php
    }
    
    /**
     * Render title field.
     */
    public function render_title_field() {
        $title = get_option('n8n_chat_widget_title', 'Chat Support');
        ?>
        <input type="text" id="n8n_chat_widget_title" name="n8n_chat_widget_title" value="<?php echo esc_attr($title); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e('Enter the title for the chat widget.', 'n8n-chat-widget'); ?></p>
        <?php
    }
    
    /**
     * Render color field.
     */
    public function render_color_field() {
        $color = get_option('n8n_chat_widget_color', '#45d3d3');
        ?>
        <input type="text" id="n8n_chat_widget_color" name="n8n_chat_widget_color" value="<?php echo esc_attr($color); ?>" class="n8n-color-picker" data-default-color="#45d3d3" />
        <p class="description"><?php esc_html_e('Select the primary color for the chat widget.', 'n8n-chat-widget'); ?></p>
        <?php
    }
    
    /**
     * Render icon settings field.
     */
    public function render_icon_settings_field() {
        $icon_type = get_option('n8n_chat_widget_icon_type', 'emoji');
        $icon = get_option('n8n_chat_widget_icon', '💬');
        $svg_icon = get_option('n8n_chat_widget_svg_icon', '');
        $popular_icons = array('💬', '🤖', '💻', '🔔', '📨', '📝', '🎯', '🔍', '📱', '👋');
        ?>
        <div class="icon-type-selector" style="margin-bottom: 15px;">
            <label style="margin-right: 15px;">
                <input type="radio" name="n8n_chat_widget_icon_type" value="emoji" <?php checked('emoji', $icon_type); ?> />
                <?php esc_html_e('Use Emoji', 'n8n-chat-widget'); ?>
            </label>
            <label>
                <input type="radio" name="n8n_chat_widget_icon_type" value="svg" <?php checked('svg', $icon_type); ?> />
                <?php esc_html_e('Use SVG Icon', 'n8n-chat-widget'); ?>
            </label>
        </div>
        
        <div id="emoji-icon-section" style="<?php echo $icon_type === 'emoji' ? '' : 'display: none;'; ?>">
            <input type="text" id="n8n_chat_widget_icon" name="n8n_chat_widget_icon" value="<?php echo esc_attr($icon); ?>" style="width: 60px; font-size: 24px; text-align: center;" maxlength="2" />
            <div class="icon-suggestions" style="margin-top: 10px;">
                <p class="description"><?php esc_html_e('Popular icons:', 'n8n-chat-widget'); ?></p>
                <div class="icon-grid" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 5px;">
                    <?php foreach ($popular_icons as $emoji) : ?>
                    <button type="button" class="icon-option" style="font-size: 24px; width: 40px; height: 40px; cursor: pointer; border: 1px solid #ddd; background: #f7f7f7;"><?php echo esc_html($emoji); ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <p class="description"><?php esc_html_e('Choose an emoji for the chat button.', 'n8n-chat-widget'); ?></p>
        </div>
        
        <div id="svg-icon-section" style="<?php echo $icon_type === 'svg' ? '' : 'display: none;'; ?>">
            <div class="svg-upload-container" style="margin-bottom: 10px;">
                <input type="text" id="n8n_chat_widget_svg_icon" name="n8n_chat_widget_svg_icon" value="<?php echo esc_url($svg_icon); ?>" class="regular-text" readonly style="margin-right: 10px;"/>
                <button type="button" id="upload_svg_button" class="button"><?php esc_html_e('Upload SVG Icon', 'n8n-chat-widget'); ?></button>
            </div>
            <?php if (!empty($svg_icon)) : ?>
            <div class="svg-preview" style="margin: 10px 0;">
                <p class="description"><?php esc_html_e('Current icon:', 'n8n-chat-widget'); ?></p>
                <div style="width: 60px; height: 60px; border: 1px solid #ddd; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; background-color: <?php echo esc_attr(get_option('n8n_chat_widget_color', '#45d3d3')); ?>;">
                    <?php 
                    // Try to get attachment ID from URL
                    $attachment_id = attachment_url_to_postid($svg_icon);
                    if ($attachment_id) {
                        echo wp_get_attachment_image($attachment_id, array(24, 24), false, array(
                            'style' => 'max-width: 60%; max-height: 60%;',
                            'alt' => esc_attr__('SVG Icon', 'n8n-chat-widget')
                        ));
                    } else {
                        // Use the helper function for proper display
                        echo wp_kses_post(n8nchwi_display_svg($svg_icon, array(24, 24), array(
                            'style' => 'max-width: 60%; max-height: 60%;',
                            'alt' => esc_attr__('SVG Icon', 'n8n-chat-widget')
                        )));
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>
            <p class="description"><?php esc_html_e('Upload an SVG icon for the chat button. Recommended size: 24x24px.', 'n8n-chat-widget'); ?></p>
            <p class="description"><?php esc_html_e('The SVG icon will be displayed inside a circular button with the chosen widget color as background.', 'n8n-chat-widget'); ?></p>
        </div>
        <?php
    }

    /**
     * Render zoom field.
     */
    public function render_zoom_field() {
        $zoom = get_option('n8n_chat_widget_zoom', '100');
        $chat_url = get_option('n8n_chat_widget_url');
        $color = get_option('n8n_chat_widget_color', '#45d3d3');
        $title = get_option('n8n_chat_widget_title', 'Chat Support');
        $position = get_option('n8n_chat_widget_position', 'right');
        ?>
        <div class="zoom-settings-container" style="display: flex; flex-wrap: wrap; gap: 30px; align-items: flex-start;">
            <!-- Left column: Zoom controls -->
            <div class="zoom-controls" style="flex: 1; min-width: 280px;">
                <div class="zoom-control" style="display: flex; align-items: center;">
                    <input type="range" id="n8n_chat_widget_zoom_slider" min="50" max="150" step="5" value="<?php echo esc_attr($zoom); ?>" style="flex: 1;" />
                    <input type="number" id="n8n_chat_widget_zoom" name="n8n_chat_widget_zoom" value="<?php echo esc_attr($zoom); ?>" min="50" max="150" step="5" style="width: 65px; margin-left: 10px;" />
                    <span style="margin-left: 5px;">%</span>
                </div>
                <p class="description"><?php esc_html_e('Adjust the zoom level of the chat content (50% - 150%).', 'n8n-chat-widget'); ?></p>
                <p class="description"><?php esc_html_e('This setting affects how the chat content is displayed in the widget.', 'n8n-chat-widget'); ?></p>
            </div>
            
            <!-- Right column: Preview -->
            <div class="preview-wrapper n8n-preview-wrapper" style="flex: 1; min-width: 350px; display: flex; flex-direction: column; align-items: center;">
                <?php if (!empty($chat_url)) : ?>
                <h4 style="margin-top: 0; align-self: flex-start;"><?php esc_html_e('Live Preview', 'n8n-chat-widget'); ?></h4>
                <div class="n8n-chat-widget-preview" style="position: relative; width: 350px; height: 500px; border-radius: 12px; box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2); overflow: hidden; flex-shrink: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background-color: <?php echo esc_attr($color); ?>; color: white;">
                        <div style="font-weight: bold; font-size: 16px;"><?php echo esc_html($title); ?></div>
                        <button type="button" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; line-height: 1; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">&times;</button>
                    </div>
                    <div style="position: relative; height: calc(100% - 60px); overflow: hidden;">
                        <div id="preview-loading-spinner" class="preview-loading-spinner" style="position: absolute; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background-color: #fff; z-index: 1;">
                            <div style="width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid <?php echo esc_attr($color); ?>; border-radius: 50%; animation: n8n-chat-widget-spin 1s linear infinite;"></div>
                        </div>
                        <iframe id="zoom-preview-iframe" src="<?php echo esc_url($chat_url); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; transform-origin: top left; transform: scale(<?php echo esc_attr($zoom / 100); ?>);"></iframe>
                    </div>
                </div>
                <p class="description" style="margin-top: 10px;"><?php esc_html_e('This is how your chat will appear with the current zoom level.', 'n8n-chat-widget'); ?></p>
                <?php else : ?>
                <h4 style="margin-top: 0; align-self: flex-start;"><?php esc_html_e('Preview', 'n8n-chat-widget'); ?></h4>
                <div class="n8n-chat-widget-preview" style="position: relative; width: 350px; height: 500px; border-radius: 12px; box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2); overflow: hidden; flex-shrink: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background-color: <?php echo esc_attr($color); ?>; color: white;">
                        <div style="font-weight: bold; font-size: 16px;"><?php echo esc_html($title); ?></div>
                        <button type="button" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; line-height: 1; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">&times;</button>
                    </div>
                    <div style="height: calc(100% - 60px); padding: 20px; background-color: #f9f9f9; overflow-y: auto;">
                        <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;">
                            <p style="margin-bottom: 15px; padding: 12px; background-color: #e9e9e9; border-radius: 18px 18px 18px 4px; max-width: 80%;"><strong>User:</strong> Hello, I need some help!</p>
                            <p style="margin-bottom: 15px; padding: 12px; background-color: #d9f4f4; border-radius: 18px 18px 4px 18px; margin-left: 20%; max-width: 80%;"><strong>Bot:</strong> Hi there! How can I assist you today?</p>
                            <p style="margin-bottom: 15px; padding: 12px; background-color: #e9e9e9; border-radius: 18px 18px 18px 4px; max-width: 80%;"><strong>User:</strong> I have a question about...</p>
                        </div>
                    </div>
                </div>
                <p class="description" style="margin-top: 10px; color: #d63638;"><?php esc_html_e('Please enter an n8n Chat URL above to see a live preview.', 'n8n-chat-widget'); ?></p>
                <?php endif; ?>
                
                <!-- Chat button preview -->
                <div style="margin-top: 30px; display: flex; align-items: center; justify-content: space-between; width: 350px; padding: 20px; background: #f8f8f8; border-radius: 8px; flex-shrink: 0;" class="button-preview-container">
                    <div>
                        <h4 style="margin-top: 0; margin-bottom: 10px;"><?php esc_html_e('Button Preview', 'n8n-chat-widget'); ?></h4>
                        <div id="preview-chat-button" style="width: 60px; height: 60px; border-radius: 50%; background-color: <?php echo esc_attr($color); ?>; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                            <span id="preview-button-icon">
                            <?php 
                            $icon_type = get_option('n8n_chat_widget_icon_type', 'emoji');
                            $icon = get_option('n8n_chat_widget_icon', '💬');
                            $svg_icon = get_option('n8n_chat_widget_svg_icon', '');
                            
                            if ($icon_type === 'emoji') {
                                echo esc_html($icon);
                            } elseif (!empty($svg_icon)) {
                                $attachment_id = attachment_url_to_postid($svg_icon);
                                if ($attachment_id) {
                                    echo wp_get_attachment_image($attachment_id, array(24, 24), false, array(
                                        'style' => 'max-width: 60%; max-height: 60%; filter: brightness(0) invert(1);',
                                        'alt' => esc_attr__('Chat', 'n8n-chat-widget')
                                    ));
                                } else {
                                    echo wp_kses_post(n8nchwi_display_svg($svg_icon, array(24, 24), array(
                                        'style' => 'max-width: 60%; max-height: 60%; filter: brightness(0) invert(1);',
                                        'alt' => esc_attr__('Chat', 'n8n-chat-widget')
                                    )));
                                }
                            } else {
                                echo '💬';
                            }
                            ?>
                            </span>
                        </div>
                    </div>
                    <p id="preview-position-text" class="description" style="margin-left: 15px; flex: 1; max-width: 250px;">
                        <?php 
                        printf(
                            /* translators: %s: position of the chat button (left or right) */
                            esc_html__('This chat button will appear in the bottom %s corner of your website.', 'n8n-chat-widget'),
                            esc_html($position)
                        );
                        ?>
                    </p>
                </div>
            </div>
        </div>
        
        <?php
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Get common settings for preview
        $color = get_option('n8n_chat_widget_color', '#45d3d3');
        $title = get_option('n8n_chat_widget_title', 'Chat Support');
        $position = get_option('n8n_chat_widget_position', 'right');
        $zoom = get_option('n8n_chat_widget_zoom', '100');
        $chat_url = get_option('n8n_chat_widget_url');
        $icon_type = get_option('n8n_chat_widget_icon_type', 'emoji');
        $icon = get_option('n8n_chat_widget_icon', '💬');
        $svg_icon = get_option('n8n_chat_widget_svg_icon', '');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="n8n-chat-support-links" style="background: #fff; padding: 15px 20px; margin: 15px 0; border-left: 4px solid #45d3d3; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div style="display: flex; align-items: center;">
                    <a href="https://far.hn/feature-request" target="_blank" style="text-decoration: none; margin-right: 20px; display: inline-flex; align-items: center;">
                        <span class="dashicons dashicons-admin-tools" style="margin-right: 5px;"></span>
                        <?php esc_html_e('Report Bug or Request a Feature', 'n8n-chat-widget'); ?>
                    </a>
                    
                    <a href="https://www.buymeacoffee.com/farhansrambiyan" target="_blank" style="text-decoration: none; display: inline-flex; align-items: center; color: #555;">
                        <span class="dashicons dashicons-coffee" style="margin-right: 5px;"></span>
                        <?php esc_html_e('Support This Plugin', 'n8n-chat-widget'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Two-column layout container -->
            <div class="n8n-admin-layout" style="display: flex; gap: 30px; margin-top: 20px; min-height: 700px;">
                <!-- Left column: Settings -->
                <div class="n8n-settings-column" style="flex: 1; display: flex; flex-direction: column;">
                    <div class="n8n-settings-container" style="background: #fff; padding: 30px; border: 1px solid #ddd; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-radius: 8px; flex: 1; height: 100%;">
                        <h3 style="margin-top: 0; margin-bottom: 20px;"><?php esc_html_e('General Settings', 'n8n-chat-widget'); ?></h3>
                        <form action="options.php" method="post" id="n8n-chat-settings-form">
                            <?php
                            settings_fields('n8n_chat_widget_options');
                            // Custom method to render settings fields (see below)
                            $this->render_settings_fields();
                            // We don't add submit_button() here since we have a Save button in the preview
                            ?>
                        </form>
                    </div>
                </div>
                
                <!-- Right column: Preview -->
                <div class="n8n-preview-column" style="flex: 1; display: flex; flex-direction: column;">
                    <div class="n8n-preview-container" style="background: #fff; padding: 30px; border: 1px solid #ddd; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-radius: 8px; flex: 1; height: 100%; display: flex; flex-direction: column;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                            <h3 style="margin: 0;"><?php esc_html_e('Live Preview', 'n8n-chat-widget'); ?></h3>
                            <button type="button" id="preview-save-changes" class="button button-primary" style="min-width: 120px;"><?php esc_html_e('Save Changes', 'n8n-chat-widget'); ?></button>
                        </div>
                        
                        <!-- Widget Preview -->
                        <div class="n8n-preview-wrapper" style="display: flex; flex-direction: column; align-items: center; justify-content: flex-start; flex: 1;">
                            <div class="n8n-chat-widget-preview" style="position: relative; width: 350px; height: 500px; border-radius: 12px; box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15); overflow: hidden; flex-shrink: 0;">
                                <div id="preview-widget-header" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; background-color: <?php echo esc_attr($color); ?>; color: white;">
                                    <div id="preview-widget-title" style="font-weight: bold; font-size: 16px;"><?php echo esc_html($title); ?></div>
                                    <button type="button" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; line-height: 1; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">&times;</button>
                                </div>
                                
                                <?php if (!empty($chat_url)) : ?>
                                <div style="position: relative; height: calc(100% - 60px); overflow: hidden;">
                                    <div id="preview-loading-spinner" class="preview-loading-spinner" style="position: absolute; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background-color: #fff; z-index: 1;">
                                        <div style="width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid <?php echo esc_attr($color); ?>; border-radius: 50%; animation: n8n-chat-widget-spin 1s linear infinite;"></div>
                                    </div>
                                    <iframe id="zoom-preview-iframe" src="<?php echo esc_url($chat_url); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; transform-origin: top left; transform: scale(<?php echo esc_attr($zoom / 100); ?>);"></iframe>
                                </div>
                                <?php else : ?>
                                <div style="height: calc(100% - 60px); padding: 20px; background-color: #f9f9f9; overflow-y: auto;">
                                    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;">
                                        <p style="margin-bottom: 15px; padding: 12px; background-color: #e9e9e9; border-radius: 18px 18px 18px 4px; max-width: 80%;"><strong>User:</strong> Hello, I need some help!</p>
                                        <p style="margin-bottom: 15px; padding: 12px; background-color: #d9f4f4; border-radius: 18px 18px 4px 18px; margin-left: 20%; max-width: 80%;"><strong>Bot:</strong> Hi there! How can I assist you today?</p>
                                        <p style="margin-bottom: 15px; padding: 12px; background-color: #e9e9e9; border-radius: 18px 18px 18px 4px; max-width: 80%;"><strong>User:</strong> I have a question about...</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- URL notice if not set -->
                            <?php if (empty($chat_url)) : ?>
                            <p class="description" style="margin-top: 10px; color: #d63638; text-align: center;"><?php esc_html_e('Please enter an n8n Chat URL to see a live preview.', 'n8n-chat-widget'); ?></p>
                            <?php endif; ?>
                            
                            <!-- Chat button preview -->
                            <div style="margin-top: 30px; display: flex; align-items: center; justify-content: space-between; width: 350px; padding: 20px; background: #f8f8f8; border-radius: 8px; flex-shrink: 0;" class="button-preview-container">
                                <div>
                                    <h4 style="margin-top: 0; margin-bottom: 10px;"><?php esc_html_e('Button Preview', 'n8n-chat-widget'); ?></h4>
                                    <div id="preview-chat-button" style="width: 60px; height: 60px; border-radius: 50%; background-color: <?php echo esc_attr($color); ?>; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                                        <span id="preview-button-icon">
                                        <?php 
                                        if ($icon_type === 'emoji') {
                                            echo esc_html($icon);
                                        } elseif (!empty($svg_icon)) {
                                            $attachment_id = attachment_url_to_postid($svg_icon);
                                            if ($attachment_id) {
                                                echo wp_get_attachment_image($attachment_id, array(24, 24), false, array(
                                                    'style' => 'max-width: 60%; max-height: 60%; filter: brightness(0) invert(1);',
                                                    'alt' => esc_attr__('Chat', 'n8n-chat-widget')
                                                ));
                                            } else {
                                                echo wp_kses_post(n8nchwi_display_svg($svg_icon, array(24, 24), array(
                                                    'style' => 'max-width: 60%; max-height: 60%; filter: brightness(0) invert(1);',
                                                    'alt' => esc_attr__('Chat', 'n8n-chat-widget')
                                                )));
                                            }
                                        } else {
                                            echo '💬';
                                        }
                                        ?>
                                        </span>
                                    </div>
                                </div>
                                <p id="preview-position-text" class="description" style="margin-left: 15px; flex: 1; max-width: 250px;">
                                    <?php 
                                    printf(
                                        /* translators: %s: position of the chat button (left or right) */
                                        esc_html__('This chat button will appear in the bottom %s corner of your website.', 'n8n-chat-widget'),
                                        esc_html($position)
                                    );
                                    ?>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Hidden iframe for preview -->
                        <div id="preview-container" style="position: fixed; top: -9999px; left: -9999px; width: 1px; height: 1px; overflow: hidden;"></div>
                        
                        <!-- Live update script is now properly enqueued via wp_enqueue_script -->
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        // Styles are now properly enqueued via wp_enqueue_style
        ?>
        <?php
    }

    /**
     * Custom method to render settings fields
     */
    private function render_settings_fields() {
        // Get all options
        $url = get_option('n8n_chat_widget_url');
        $enabled = get_option('n8n_chat_widget_enabled', 'yes');
        $position = get_option('n8n_chat_widget_position', 'right');
        $title = get_option('n8n_chat_widget_title', 'Chat Support');
        $color = get_option('n8n_chat_widget_color', '#45d3d3');
        $icon_type = get_option('n8n_chat_widget_icon_type', 'emoji');
        $icon = get_option('n8n_chat_widget_icon', '💬');
        $svg_icon = get_option('n8n_chat_widget_svg_icon', '');
        $zoom = get_option('n8n_chat_widget_zoom', '100');
        $popular_icons = array('💬', '🤖', '💻', '🔔', '📨', '📝', '🎯', '🔍', '📱', '👋');

        // ========== GROUP 1: CONNECTION ==========
        echo '<div class="n8n-settings-group n8n-settings-group-connection">';
        echo '<div class="n8n-settings-group-header" data-group="connection">';
        echo '<span class="dashicons dashicons-admin-links"></span>';
        echo '<h4>' . esc_html__('Connection', 'n8n-chat-widget') . '</h4>';
        echo '<span class="n8n-group-toggle dashicons dashicons-arrow-up-alt2"></span>';
        echo '</div>';
        echo '<div class="n8n-settings-group-content" id="group-connection">';

        // Chat URL field
        echo '<div class="n8n-setting-field n8n-url-field">';
        echo '<label for="n8n_chat_widget_url">' . esc_html__('n8n Chat URL', 'n8n-chat-widget') . '</label>';

        // Connection status indicator
        echo '<div class="n8n-connection-status-wrapper">';
        echo '<span id="n8n-connection-status" class="n8n-connection-status' . (empty($url) ? '' : ' status-unknown') . '">';
        if (!empty($url)) {
            echo '<span class="status-dot"></span>';
            echo '<span class="status-text">' . esc_html__('Not tested', 'n8n-chat-widget') . '</span>';
        }
        echo '</span>';
        echo '</div>';

        echo '<div class="n8n-url-input-wrapper">';
        echo '<input type="url" id="n8n_chat_widget_url" name="n8n_chat_widget_url" value="' . esc_attr($url) . '" class="regular-text" placeholder="https://n8n.example.com/webhook/your-chat-id/chat" />';
        echo '<button type="button" id="n8n-test-connection" class="button button-secondary">' . esc_html__('Test Connection', 'n8n-chat-widget') . '</button>';
        echo '</div>';

        echo '<div id="n8n-connection-message" class="n8n-connection-message"></div>';

        echo '<details class="chat-url-help">';
        echo '<summary>' . esc_html__('Need help getting your Chat URL?', 'n8n-chat-widget') . '</summary>';
        echo '<div class="chat-url-help-content">';
        echo '<p><strong>' . esc_html__('How to get your n8n Chat URL:', 'n8n-chat-widget') . '</strong></p>';
        echo '<ol>';
        echo '<li>' . esc_html__('Set up a workflow in n8n using the Chat Trigger node', 'n8n-chat-widget') . '</li>';
        echo '<li>' . esc_html__('Connect it to an AI agent or chain', 'n8n-chat-widget') . '</li>';
        echo '<li>' . esc_html__('Enable "Make Chat Publicly Available" in the Chat Trigger node', 'n8n-chat-widget') . '</li>';
        echo '<li>' . esc_html__('Set Mode to "Hosted Chat" (recommended)', 'n8n-chat-widget') . '</li>';
        echo '<li>' . esc_html__('Activate your workflow and copy the Chat URL', 'n8n-chat-widget') . '</li>';
        echo '</ol>';
        echo '<p class="help-links">';
        echo '<a href="https://docs.n8n.io/integrations/builtin/core-nodes/n8n-nodes-langchain.chattrigger/" target="_blank"><span class="dashicons dashicons-media-document"></span>' . esc_html__('Documentation', 'n8n-chat-widget') . '</a>';
        echo '<a href="https://www.youtube.com/watch?v=qirFuwSgrfw" target="_blank"><span class="dashicons dashicons-video-alt3"></span>' . esc_html__('Video Tutorial', 'n8n-chat-widget') . '</a>';
        echo '</p>';
        echo '</div>';
        echo '</details>';
        echo '</div>';

        // Enable widget field
        echo '<div class="n8n-setting-field">';
        echo '<label for="n8n_chat_widget_enabled">' . esc_html__('Widget Status', 'n8n-chat-widget') . '</label>';
        echo '<label class="n8n-toggle-wrapper">';
        echo '<input type="checkbox" id="n8n_chat_widget_enabled" name="n8n_chat_widget_enabled" value="yes" ' . checked('yes', $enabled, false) . ' />';
        echo '<span class="n8n-toggle-slider"></span>';
        echo '<span class="n8n-toggle-label">' . esc_html__('Show widget on website', 'n8n-chat-widget') . '</span>';
        echo '</label>';
        echo '</div>';

        echo '</div>'; // End group content
        echo '</div>'; // End connection group

        // ========== GROUP 2: APPEARANCE ==========
        echo '<div class="n8n-settings-group n8n-settings-group-appearance">';
        echo '<div class="n8n-settings-group-header" data-group="appearance">';
        echo '<span class="dashicons dashicons-admin-appearance"></span>';
        echo '<h4>' . esc_html__('Appearance', 'n8n-chat-widget') . '</h4>';
        echo '<span class="n8n-group-toggle dashicons dashicons-arrow-up-alt2"></span>';
        echo '</div>';
        echo '<div class="n8n-settings-group-content" id="group-appearance">';

        // Position field
        echo '<div class="n8n-setting-field">';
        echo '<label>' . esc_html__('Widget Position', 'n8n-chat-widget') . '</label>';
        echo '<div class="n8n-position-selector">';
        echo '<label class="n8n-position-option' . ($position === 'left' ? ' selected' : '') . '">';
        echo '<input type="radio" name="n8n_chat_widget_position" value="left" ' . checked('left', $position, false) . ' />';
        echo '<span class="n8n-position-icon"><span class="dashicons dashicons-align-left"></span></span>';
        echo '<span class="n8n-position-label">' . esc_html__('Left', 'n8n-chat-widget') . '</span>';
        echo '</label>';
        echo '<label class="n8n-position-option' . ($position === 'right' ? ' selected' : '') . '">';
        echo '<input type="radio" name="n8n_chat_widget_position" value="right" ' . checked('right', $position, false) . ' />';
        echo '<span class="n8n-position-icon"><span class="dashicons dashicons-align-right"></span></span>';
        echo '<span class="n8n-position-label">' . esc_html__('Right', 'n8n-chat-widget') . '</span>';
        echo '</label>';
        echo '</div>';
        echo '</div>';

        // Color field
        echo '<div class="n8n-setting-field">';
        echo '<label for="n8n_chat_widget_color">' . esc_html__('Widget Color', 'n8n-chat-widget') . '</label>';
        echo '<input type="text" id="n8n_chat_widget_color" name="n8n_chat_widget_color" value="' . esc_attr($color) . '" class="n8n-color-picker" data-default-color="#45d3d3" />';
        echo '</div>';

        // Icon settings
        echo '<div class="n8n-setting-field">';
        echo '<label>' . esc_html__('Chat Icon', 'n8n-chat-widget') . '</label>';

        // Icon type selector tabs
        echo '<div class="n8n-icon-tabs">';
        echo '<button type="button" class="n8n-icon-tab' . ($icon_type === 'emoji' ? ' active' : '') . '" data-tab="emoji">' . esc_html__('Emoji', 'n8n-chat-widget') . '</button>';
        echo '<button type="button" class="n8n-icon-tab' . ($icon_type === 'svg' ? ' active' : '') . '" data-tab="svg">' . esc_html__('Upload SVG', 'n8n-chat-widget') . '</button>';
        echo '</div>';

        // Hidden input for icon type
        echo '<input type="hidden" name="n8n_chat_widget_icon_type" id="n8n_chat_widget_icon_type" value="' . esc_attr($icon_type) . '" />';

        // Emoji icon section
        echo '<div id="emoji-icon-section" class="n8n-icon-tab-content' . ($icon_type === 'emoji' ? ' active' : '') . '">';
        echo '<div class="n8n-emoji-input-wrapper">';
        echo '<input type="text" id="n8n_chat_widget_icon" name="n8n_chat_widget_icon" value="' . esc_attr($icon) . '" maxlength="2" />';
        echo '</div>';
        echo '<div class="n8n-emoji-grid">';
        foreach ($popular_icons as $emoji) {
            $selected_class = ($emoji === $icon) ? ' selected' : '';
            echo '<button type="button" class="n8n-emoji-option' . $selected_class . '">' . esc_html($emoji) . '</button>';
        }
        echo '</div>';
        echo '</div>';

        // SVG icon section
        echo '<div id="svg-icon-section" class="n8n-icon-tab-content' . ($icon_type === 'svg' ? ' active' : '') . '">';
        echo '<div class="n8n-svg-upload-wrapper">';
        echo '<input type="text" id="n8n_chat_widget_svg_icon" name="n8n_chat_widget_svg_icon" value="' . esc_url($svg_icon) . '" readonly placeholder="' . esc_attr__('No file selected', 'n8n-chat-widget') . '" />';
        echo '<button type="button" id="upload_svg_button" class="button">' . esc_html__('Choose File', 'n8n-chat-widget') . '</button>';
        echo '</div>';

        if (!empty($svg_icon)) {
            echo '<div class="n8n-svg-preview">';
            echo '<div class="n8n-svg-preview-circle" style="background-color: ' . esc_attr($color) . ';">';
            $attachment_id = attachment_url_to_postid($svg_icon);
            if ($attachment_id) {
                echo wp_get_attachment_image($attachment_id, array(24, 24), false, array('alt' => esc_attr__('SVG Icon', 'n8n-chat-widget')));
            } else {
                echo wp_kses_post(n8nchwi_display_svg($svg_icon, array(24, 24), array('alt' => esc_attr__('SVG Icon', 'n8n-chat-widget'))));
            }
            echo '</div>';
            echo '</div>';
        }
        echo '<p class="description">' . esc_html__('Recommended: 24x24px SVG', 'n8n-chat-widget') . '</p>';
        echo '</div>';
        echo '</div>';

        echo '</div>'; // End group content
        echo '</div>'; // End appearance group

        // ========== GROUP 3: DISPLAY ==========
        echo '<div class="n8n-settings-group n8n-settings-group-display">';
        echo '<div class="n8n-settings-group-header" data-group="display">';
        echo '<span class="dashicons dashicons-visibility"></span>';
        echo '<h4>' . esc_html__('Display', 'n8n-chat-widget') . '</h4>';
        echo '<span class="n8n-group-toggle dashicons dashicons-arrow-up-alt2"></span>';
        echo '</div>';
        echo '<div class="n8n-settings-group-content" id="group-display">';

        // Title field
        echo '<div class="n8n-setting-field">';
        echo '<label for="n8n_chat_widget_title">' . esc_html__('Chat Title', 'n8n-chat-widget') . '</label>';
        echo '<input type="text" id="n8n_chat_widget_title" name="n8n_chat_widget_title" value="' . esc_attr($title) . '" class="regular-text" placeholder="' . esc_attr__('Chat Support', 'n8n-chat-widget') . '" />';
        echo '<p class="description">' . esc_html__('Displayed in the chat header', 'n8n-chat-widget') . '</p>';
        echo '</div>';

        // Zoom field with presets
        echo '<div class="n8n-setting-field">';
        echo '<label for="n8n_chat_widget_zoom_slider">' . esc_html__('Content Size', 'n8n-chat-widget') . '</label>';

        // Zoom presets
        echo '<div class="n8n-zoom-presets">';
        echo '<button type="button" class="n8n-zoom-preset' . ($zoom == '75' ? ' active' : '') . '" data-zoom="75">' . esc_html__('Compact', 'n8n-chat-widget') . '</button>';
        echo '<button type="button" class="n8n-zoom-preset' . ($zoom == '100' ? ' active' : '') . '" data-zoom="100">' . esc_html__('Normal', 'n8n-chat-widget') . '</button>';
        echo '<button type="button" class="n8n-zoom-preset' . ($zoom == '125' ? ' active' : '') . '" data-zoom="125">' . esc_html__('Large', 'n8n-chat-widget') . '</button>';
        echo '</div>';

        echo '<div class="n8n-zoom-custom">';
        echo '<span class="n8n-zoom-label">' . esc_html__('Custom:', 'n8n-chat-widget') . '</span>';
        echo '<input type="range" id="n8n_chat_widget_zoom_slider" min="50" max="150" step="5" value="' . esc_attr($zoom) . '" />';
        echo '<input type="number" id="n8n_chat_widget_zoom" name="n8n_chat_widget_zoom" value="' . esc_attr($zoom) . '" min="50" max="150" step="5" />';
        echo '<span class="n8n-zoom-unit">%</span>';
        echo '</div>';
        echo '</div>';

        echo '</div>'; // End group content
        echo '</div>'; // End display group

        // ========== GROUP 4: TARGETING ==========
        $targeting_mode = get_option('n8n_chat_widget_targeting_mode', 'all');
        $targeting_pages = get_option('n8n_chat_widget_targeting_pages', '');
        $hide_on_mobile = get_option('n8n_chat_widget_hide_on_mobile', 'no');

        echo '<div class="n8n-settings-group n8n-settings-group-targeting">';
        echo '<div class="n8n-settings-group-header" data-group="targeting">';
        echo '<span class="dashicons dashicons-filter"></span>';
        echo '<h4>' . esc_html__('Targeting', 'n8n-chat-widget') . '</h4>';
        echo '<span class="n8n-group-toggle dashicons dashicons-arrow-up-alt2"></span>';
        echo '</div>';
        echo '<div class="n8n-settings-group-content" id="group-targeting">';

        // Targeting mode
        echo '<div class="n8n-setting-field">';
        echo '<label>' . esc_html__('Show Widget On', 'n8n-chat-widget') . '</label>';
        echo '<div class="n8n-targeting-mode-selector">';

        echo '<label class="n8n-targeting-option' . ($targeting_mode === 'all' ? ' selected' : '') . '">';
        echo '<input type="radio" name="n8n_chat_widget_targeting_mode" value="all" ' . checked('all', $targeting_mode, false) . ' />';
        echo '<span class="n8n-targeting-icon"><span class="dashicons dashicons-admin-site-alt3"></span></span>';
        echo '<span class="n8n-targeting-label">' . esc_html__('All Pages', 'n8n-chat-widget') . '</span>';
        echo '</label>';

        echo '<label class="n8n-targeting-option' . ($targeting_mode === 'include' ? ' selected' : '') . '">';
        echo '<input type="radio" name="n8n_chat_widget_targeting_mode" value="include" ' . checked('include', $targeting_mode, false) . ' />';
        echo '<span class="n8n-targeting-icon"><span class="dashicons dashicons-yes-alt"></span></span>';
        echo '<span class="n8n-targeting-label">' . esc_html__('Specific Pages Only', 'n8n-chat-widget') . '</span>';
        echo '</label>';

        echo '<label class="n8n-targeting-option' . ($targeting_mode === 'exclude' ? ' selected' : '') . '">';
        echo '<input type="radio" name="n8n_chat_widget_targeting_mode" value="exclude" ' . checked('exclude', $targeting_mode, false) . ' />';
        echo '<span class="n8n-targeting-icon"><span class="dashicons dashicons-dismiss"></span></span>';
        echo '<span class="n8n-targeting-label">' . esc_html__('All Except', 'n8n-chat-widget') . '</span>';
        echo '</label>';

        echo '</div>';
        echo '</div>';

        // Pages textarea (shown when include or exclude is selected)
        echo '<div class="n8n-setting-field n8n-targeting-pages-field" id="targeting-pages-wrapper" style="' . ($targeting_mode === 'all' ? 'display: none;' : '') . '">';
        echo '<label for="n8n_chat_widget_targeting_pages">' . esc_html__('Pages', 'n8n-chat-widget') . '</label>';
        echo '<textarea id="n8n_chat_widget_targeting_pages" name="n8n_chat_widget_targeting_pages" rows="5" class="large-text" placeholder="' . esc_attr__("Enter page IDs or URL paths (one per line)\n\nExamples:\n42\ncontact\nblog/*\nproducts/sale", 'n8n-chat-widget') . '">' . esc_textarea($targeting_pages) . '</textarea>';
        echo '<p class="description">' . esc_html__('Enter page IDs (numbers) or URL paths. Use * for wildcards (e.g., blog/*).', 'n8n-chat-widget') . '</p>';
        echo '</div>';

        // Hide on mobile toggle
        echo '<div class="n8n-setting-field">';
        echo '<label for="n8n_chat_widget_hide_on_mobile">' . esc_html__('Mobile Visibility', 'n8n-chat-widget') . '</label>';
        echo '<label class="n8n-toggle-wrapper">';
        echo '<input type="checkbox" id="n8n_chat_widget_hide_on_mobile" name="n8n_chat_widget_hide_on_mobile" value="yes" ' . checked('yes', $hide_on_mobile, false) . ' />';
        echo '<span class="n8n-toggle-slider"></span>';
        echo '<span class="n8n-toggle-label">' . esc_html__('Hide widget on mobile devices', 'n8n-chat-widget') . '</span>';
        echo '</label>';
        echo '</div>';

        echo '</div>'; // End group content
        echo '</div>'; // End targeting group
    }

    /**
     * Display an admin notice if the chat URL is not set
     */
    public function admin_notice() {
        $screen = get_current_screen();
        
        // Skip if we're already on the settings page
        if ('toplevel_page_n8n-chat-widget' === $screen->id) {
            return;
        }
        
        // Show notice if URL is not set
        if (empty(get_option('n8n_chat_widget_url'))) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <?php esc_html_e('n8n Chat Widget is activated but not configured yet.', 'n8n-chat-widget'); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=n8n-chat-widget')); ?>">
                        <?php esc_html_e('Click here to configure', 'n8n-chat-widget'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Handle form submission and settings update
     */
    public function handle_settings_update() {
        // Check if our form was submitted
        if (isset($_POST['option_page']) && sanitize_text_field(wp_unslash($_POST['option_page'])) === 'n8n_chat_widget_options') {
            // Verify nonce
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'n8n_chat_widget_options-options')) {
                add_settings_error('n8n_chat_widget_messages', 'n8n_chat_widget_errors', __('Security check failed. Please try again.', 'n8n-chat-widget'), 'error');
                return;
            }
            
            // Add a success message if settings were updated
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('N8n Chat Widget settings updated successfully.', 'n8n-chat-widget'); ?></p>
                </div>
                <?php
            });
        }
    }
} 