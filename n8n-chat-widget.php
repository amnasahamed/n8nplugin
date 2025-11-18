<?php
/**
 * Plugin Name: n8n Chat Widget
 * Description: Add a customizable n8n chat widget to your website frontend with world-class design and functionality
 * Version: 2.0.0
 * Author: farhansrambiyan
 * Author URI: https://far.hn/
 * Text Domain: n8n-chat-widget
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Tested up to: 6.8
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('N8N_CHAT_WIDGET_VERSION', '2.0.0');
define('N8N_CHAT_WIDGET_PATH', plugin_dir_path(__FILE__));
define('N8N_CHAT_WIDGET_URL', plugin_dir_url(__FILE__));

// Include the admin settings page
require_once N8N_CHAT_WIDGET_PATH . 'admin/class-n8n-chat-widget-admin.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'n8nchwi_activate');
register_deactivation_hook(__FILE__, 'n8nchwi_deactivate');

/**
 * Plugin activation function
 */
function n8nchwi_activate() {
    // Core settings
    add_option('n8n_chat_widget_url', '');
    add_option('n8n_chat_widget_enabled', 'yes');
    add_option('n8n_chat_widget_position', 'right');
    add_option('n8n_chat_widget_title', 'Chat Support');
    add_option('n8n_chat_widget_color', '#854fff');
    add_option('n8n_chat_widget_icon', '💬');
    add_option('n8n_chat_widget_icon_type', 'emoji');
    add_option('n8n_chat_widget_svg_icon', '');
    add_option('n8n_chat_widget_zoom', '100');

    // Theme preset
    add_option('n8n_chat_widget_theme_preset', 'purple');

    // Welcome message settings
    add_option('n8n_chat_widget_welcome_enabled', 'no');
    add_option('n8n_chat_widget_welcome_message', 'Hi there! How can we help you today?');
    add_option('n8n_chat_widget_welcome_delay', '3');

    // Behavior settings
    add_option('n8n_chat_widget_auto_open', 'no');
    add_option('n8n_chat_widget_auto_open_delay', '5');
    add_option('n8n_chat_widget_sound_enabled', 'no');
    add_option('n8n_chat_widget_remember_state', 'yes');

    // Style settings
    add_option('n8n_chat_widget_button_style', 'circle');
    add_option('n8n_chat_widget_popup_style', 'modern');
    add_option('n8n_chat_widget_animation_style', 'bounce');

    // Advanced positioning
    add_option('n8n_chat_widget_margin_bottom', '20');
    add_option('n8n_chat_widget_margin_side', '20');
    add_option('n8n_chat_widget_mobile_margin_bottom', '15');
    add_option('n8n_chat_widget_mobile_margin_side', '15');

    // Page controls
    add_option('n8n_chat_widget_display_mode', 'all');
    add_option('n8n_chat_widget_include_pages', '');
    add_option('n8n_chat_widget_exclude_pages', '');
    add_option('n8n_chat_widget_show_on_mobile', 'yes');

    // Branding
    add_option('n8n_chat_widget_powered_by', 'no');
    add_option('n8n_chat_widget_powered_text', 'Powered by n8n');
    add_option('n8n_chat_widget_header_logo', '');

    // Advanced
    add_option('n8n_chat_widget_custom_css', '');
    add_option('n8n_chat_widget_close_on_outside', 'yes');
    add_option('n8n_chat_widget_close_on_escape', 'yes');
}

/**
 * Plugin deactivation function
 */
function n8nchwi_deactivate() {
    // Don't delete options to preserve settings
}

/**
 * Initialize the admin settings
 */
function n8nchwi_init_admin() {
    $admin = new N8NCHWI_Admin();
    $admin->init();
}
add_action('init', 'n8nchwi_init_admin');

/**
 * Get all widget options at once (cached for performance)
 *
 * @return array All widget options with defaults
 */
function n8nchwi_get_options() {
    static $options = null;

    if ($options === null) {
        $options = array(
            // Core settings
            'enabled' => get_option('n8n_chat_widget_enabled', 'yes'),
            'url' => get_option('n8n_chat_widget_url', ''),
            'position' => get_option('n8n_chat_widget_position', 'right'),
            'title' => get_option('n8n_chat_widget_title', 'Chat Support'),
            'color' => get_option('n8n_chat_widget_color', '#854fff'),
            'icon' => get_option('n8n_chat_widget_icon', '💬'),
            'icon_type' => get_option('n8n_chat_widget_icon_type', 'emoji'),
            'svg_icon' => get_option('n8n_chat_widget_svg_icon', ''),
            'zoom' => get_option('n8n_chat_widget_zoom', '100'),

            // Theme preset
            'theme_preset' => get_option('n8n_chat_widget_theme_preset', 'purple'),

            // Welcome message
            'welcome_enabled' => get_option('n8n_chat_widget_welcome_enabled', 'no'),
            'welcome_message' => get_option('n8n_chat_widget_welcome_message', 'Hi there! How can we help you today?'),
            'welcome_delay' => get_option('n8n_chat_widget_welcome_delay', '3'),

            // Behavior
            'auto_open' => get_option('n8n_chat_widget_auto_open', 'no'),
            'auto_open_delay' => get_option('n8n_chat_widget_auto_open_delay', '5'),
            'sound_enabled' => get_option('n8n_chat_widget_sound_enabled', 'no'),
            'remember_state' => get_option('n8n_chat_widget_remember_state', 'yes'),

            // Style
            'button_style' => get_option('n8n_chat_widget_button_style', 'circle'),
            'popup_style' => get_option('n8n_chat_widget_popup_style', 'modern'),
            'animation_style' => get_option('n8n_chat_widget_animation_style', 'bounce'),

            // Advanced positioning
            'margin_bottom' => get_option('n8n_chat_widget_margin_bottom', '20'),
            'margin_side' => get_option('n8n_chat_widget_margin_side', '20'),
            'mobile_margin_bottom' => get_option('n8n_chat_widget_mobile_margin_bottom', '15'),
            'mobile_margin_side' => get_option('n8n_chat_widget_mobile_margin_side', '15'),

            // Page controls
            'display_mode' => get_option('n8n_chat_widget_display_mode', 'all'),
            'include_pages' => get_option('n8n_chat_widget_include_pages', ''),
            'exclude_pages' => get_option('n8n_chat_widget_exclude_pages', ''),
            'show_on_mobile' => get_option('n8n_chat_widget_show_on_mobile', 'yes'),

            // Branding
            'powered_by' => get_option('n8n_chat_widget_powered_by', 'no'),
            'powered_text' => get_option('n8n_chat_widget_powered_text', 'Powered by n8n'),
            'header_logo' => get_option('n8n_chat_widget_header_logo', ''),

            // Advanced
            'custom_css' => get_option('n8n_chat_widget_custom_css', ''),
            'close_on_outside' => get_option('n8n_chat_widget_close_on_outside', 'yes'),
            'close_on_escape' => get_option('n8n_chat_widget_close_on_escape', 'yes'),
        );
    }

    return $options;
}

/**
 * Get theme presets
 */
function n8nchwi_get_theme_presets() {
    return array(
        'purple' => array(
            'name' => __('Royal Purple', 'n8n-chat-widget'),
            'color' => '#854fff',
            'gradient' => 'linear-gradient(135deg, #854fff 0%, #6b3fd4 100%)',
        ),
        'blue' => array(
            'name' => __('Ocean Blue', 'n8n-chat-widget'),
            'color' => '#2563eb',
            'gradient' => 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
        ),
        'teal' => array(
            'name' => __('Fresh Teal', 'n8n-chat-widget'),
            'color' => '#14b8a6',
            'gradient' => 'linear-gradient(135deg, #14b8a6 0%, #0d9488 100%)',
        ),
        'rose' => array(
            'name' => __('Soft Rose', 'n8n-chat-widget'),
            'color' => '#f43f5e',
            'gradient' => 'linear-gradient(135deg, #f43f5e 0%, #e11d48 100%)',
        ),
        'orange' => array(
            'name' => __('Vibrant Orange', 'n8n-chat-widget'),
            'color' => '#f97316',
            'gradient' => 'linear-gradient(135deg, #f97316 0%, #ea580c 100%)',
        ),
        'emerald' => array(
            'name' => __('Emerald Green', 'n8n-chat-widget'),
            'color' => '#10b981',
            'gradient' => 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
        ),
        'indigo' => array(
            'name' => __('Deep Indigo', 'n8n-chat-widget'),
            'color' => '#6366f1',
            'gradient' => 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)',
        ),
        'slate' => array(
            'name' => __('Professional Slate', 'n8n-chat-widget'),
            'color' => '#475569',
            'gradient' => 'linear-gradient(135deg, #475569 0%, #334155 100%)',
        ),
        'custom' => array(
            'name' => __('Custom Color', 'n8n-chat-widget'),
            'color' => '',
            'gradient' => '',
        ),
    );
}

/**
 * Check if widget should display on current page
 */
function n8nchwi_should_display() {
    $options = n8nchwi_get_options();

    // Check if enabled
    if ($options['enabled'] !== 'yes') {
        return false;
    }

    // Check mobile display
    if ($options['show_on_mobile'] === 'no' && wp_is_mobile()) {
        return false;
    }

    // Get current page/post ID
    $current_id = get_queried_object_id();

    // Check display mode
    switch ($options['display_mode']) {
        case 'include':
            // Only show on specified pages
            $include_pages = array_filter(array_map('trim', explode(',', $options['include_pages'])));
            if (!empty($include_pages) && !in_array($current_id, $include_pages)) {
                return false;
            }
            break;

        case 'exclude':
            // Hide on specified pages
            $exclude_pages = array_filter(array_map('trim', explode(',', $options['exclude_pages'])));
            if (!empty($exclude_pages) && in_array($current_id, $exclude_pages)) {
                return false;
            }
            break;

        case 'all':
        default:
            // Show on all pages
            break;
    }

    return true;
}

/**
 * Enqueue frontend scripts and styles
 */
function n8nchwi_enqueue_scripts() {
    $options = n8nchwi_get_options();

    // Only enqueue if the widget should display
    if (!n8nchwi_should_display() || empty($options['url'])) {
        return;
    }

    wp_enqueue_style('n8n-chat-widget-style', N8N_CHAT_WIDGET_URL . 'assets/css/n8n-chat-widget.css', array(), N8N_CHAT_WIDGET_VERSION);
    wp_enqueue_script('n8n-chat-widget-script', N8N_CHAT_WIDGET_URL . 'assets/js/n8n-chat-widget.js', array('jquery'), N8N_CHAT_WIDGET_VERSION, true);

    // Validate and normalize zoom setting
    $zoom = max(50, min(150, intval($options['zoom'])));

    // Pass the chat settings to JavaScript
    wp_localize_script('n8n-chat-widget-script', 'n8nchwiData', array(
        'chatUrl' => esc_url($options['url']),
        'position' => esc_attr($options['position']),
        'title' => esc_attr($options['title']),
        'color' => esc_attr($options['color']),
        'icon' => esc_attr($options['icon']),
        'iconType' => esc_attr($options['icon_type']),
        'svgIcon' => esc_attr($options['svg_icon']),
        'zoom' => $zoom,
        'welcomeEnabled' => $options['welcome_enabled'],
        'welcomeMessage' => esc_attr($options['welcome_message']),
        'welcomeDelay' => intval($options['welcome_delay']),
        'autoOpen' => $options['auto_open'],
        'autoOpenDelay' => intval($options['auto_open_delay']),
        'soundEnabled' => $options['sound_enabled'],
        'rememberState' => $options['remember_state'],
        'buttonStyle' => esc_attr($options['button_style']),
        'popupStyle' => esc_attr($options['popup_style']),
        'animationStyle' => esc_attr($options['animation_style']),
        'closeOnOutside' => $options['close_on_outside'],
        'closeOnEscape' => $options['close_on_escape'],
        'poweredBy' => $options['powered_by'],
        'poweredText' => esc_attr($options['powered_text']),
    ));

    // Get theme preset
    $presets = n8nchwi_get_theme_presets();
    $preset = isset($presets[$options['theme_preset']]) ? $presets[$options['theme_preset']] : $presets['purple'];

    // Use preset color or custom color
    $color = ($options['theme_preset'] === 'custom') ? $options['color'] : $preset['color'];
    $hover_color = n8nchwi_adjust_color_brightness($color, -15);

    // Build gradient
    $gradient = ($options['theme_preset'] === 'custom')
        ? "linear-gradient(135deg, {$color} 0%, {$hover_color} 100%)"
        : $preset['gradient'];

    // Build custom CSS
    $custom_css = ":root {
        --n8n-widget-color: {$color};
        --n8n-widget-color-hover: {$hover_color};
        --n8n-widget-gradient: {$gradient};
        --n8n-widget-margin-bottom: {$options['margin_bottom']}px;
        --n8n-widget-margin-side: {$options['margin_side']}px;
    }";

    // Add mobile-specific margins
    $custom_css .= "@media (max-width: 480px) {
        :root {
            --n8n-widget-margin-bottom: {$options['mobile_margin_bottom']}px;
            --n8n-widget-margin-side: {$options['mobile_margin_side']}px;
        }
    }";

    // Add user custom CSS
    if (!empty($options['custom_css'])) {
        $custom_css .= "\n" . wp_strip_all_tags($options['custom_css']);
    }

    wp_add_inline_style('n8n-chat-widget-style', $custom_css);
}
add_action('wp_enqueue_scripts', 'n8nchwi_enqueue_scripts');

/**
 * Add the chat widget to the footer
 */
function n8nchwi_add_to_footer() {
    $options = n8nchwi_get_options();

    // Only add if the widget should display and URL is set
    if (!n8nchwi_should_display() || empty($options['url'])) {
        return;
    }

    // Pass options to template to avoid additional get_option() calls
    $n8nchwi_options = $options;
    include N8N_CHAT_WIDGET_PATH . 'public/partials/n8n-chat-widget-public-display.php';
}
add_action('wp_footer', 'n8nchwi_add_to_footer');

/**
 * Helper function to adjust color brightness
 */
function n8nchwi_adjust_color_brightness($hex, $steps) {
    // Steps should be between -255 and 255. Negative = darker, positive = lighter
    $steps = max(-255, min(255, $steps));

    // Format the hex color string
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
    }

    // Get decimal values
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    // Adjust
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));

    // Convert back to hex
    $r_hex = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
    $g_hex = str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
    $b_hex = str_pad(dechex($b), 2, '0', STR_PAD_LEFT);

    return '#' . $r_hex . $g_hex . $b_hex;
}

/**
 * Helper function to display SVG images in a WordPress-compliant way
 */
function n8nchwi_display_svg($svg_url, $size = array(24, 24), $attr = array()) {
    if (empty($svg_url)) {
        return '';
    }

    // Set default attributes
    $default_attr = array(
        'alt' => esc_attr__('Icon', 'n8n-chat-widget'),
        'class' => 'svg-icon',
    );
    $attr = wp_parse_args($attr, $default_attr);

    // Try to get the attachment ID
    $attachment_id = attachment_url_to_postid($svg_url);

    // If we have a valid attachment ID, use wp_get_attachment_image - always preferred method
    if ($attachment_id) {
        return wp_get_attachment_image($attachment_id, $size, false, $attr);
    }

    // For external or unknown SVGs
    // First, create a wrapper span for the SVG (used for proper styling)
    $wrapper_open = '<span class="n8n-svg-wrapper">';
    $wrapper_close = '</span>';

    // Build the image attributes array for wp_kses
    $img_atts = array(
        'src' => esc_url($svg_url),
        'width' => esc_attr($size[0]),
        'height' => esc_attr($size[1]),
    );

    // Add all other attributes
    foreach ($attr as $name => $value) {
        $img_atts[$name] = esc_attr($value);
    }

    // Generate the img tag using WordPress functions
    $img_html = '';
    $img_html .= '<img';
    foreach ($img_atts as $name => $value) {
        $img_html .= ' ' . $name . '="' . $value . '"';
    }
    $img_html .= '>';

    // Use WordPress' sanitization function
    $allowed_html = array(
        'img' => array(
            'src' => array(),
            'width' => array(),
            'height' => array(),
            'alt' => array(),
            'class' => array(),
            'style' => array(),
            'id' => array(),
        ),
    );

    // Build the final output
    $html = $wrapper_open . wp_kses($img_html, $allowed_html) . $wrapper_close;

    return $html;
}

/**
 * Export settings as JSON
 */
function n8nchwi_export_settings() {
    $options = n8nchwi_get_options();
    return wp_json_encode($options, JSON_PRETTY_PRINT);
}

/**
 * Import settings from JSON
 */
function n8nchwi_import_settings($json_data) {
    $settings = json_decode($json_data, true);

    if (!is_array($settings)) {
        return false;
    }

    // Map of option keys
    $option_map = array(
        'enabled' => 'n8n_chat_widget_enabled',
        'url' => 'n8n_chat_widget_url',
        'position' => 'n8n_chat_widget_position',
        'title' => 'n8n_chat_widget_title',
        'color' => 'n8n_chat_widget_color',
        'icon' => 'n8n_chat_widget_icon',
        'icon_type' => 'n8n_chat_widget_icon_type',
        'svg_icon' => 'n8n_chat_widget_svg_icon',
        'zoom' => 'n8n_chat_widget_zoom',
        'theme_preset' => 'n8n_chat_widget_theme_preset',
        'welcome_enabled' => 'n8n_chat_widget_welcome_enabled',
        'welcome_message' => 'n8n_chat_widget_welcome_message',
        'welcome_delay' => 'n8n_chat_widget_welcome_delay',
        'auto_open' => 'n8n_chat_widget_auto_open',
        'auto_open_delay' => 'n8n_chat_widget_auto_open_delay',
        'sound_enabled' => 'n8n_chat_widget_sound_enabled',
        'remember_state' => 'n8n_chat_widget_remember_state',
        'button_style' => 'n8n_chat_widget_button_style',
        'popup_style' => 'n8n_chat_widget_popup_style',
        'animation_style' => 'n8n_chat_widget_animation_style',
        'margin_bottom' => 'n8n_chat_widget_margin_bottom',
        'margin_side' => 'n8n_chat_widget_margin_side',
        'mobile_margin_bottom' => 'n8n_chat_widget_mobile_margin_bottom',
        'mobile_margin_side' => 'n8n_chat_widget_mobile_margin_side',
        'display_mode' => 'n8n_chat_widget_display_mode',
        'include_pages' => 'n8n_chat_widget_include_pages',
        'exclude_pages' => 'n8n_chat_widget_exclude_pages',
        'show_on_mobile' => 'n8n_chat_widget_show_on_mobile',
        'powered_by' => 'n8n_chat_widget_powered_by',
        'powered_text' => 'n8n_chat_widget_powered_text',
        'header_logo' => 'n8n_chat_widget_header_logo',
        'custom_css' => 'n8n_chat_widget_custom_css',
        'close_on_outside' => 'n8n_chat_widget_close_on_outside',
        'close_on_escape' => 'n8n_chat_widget_close_on_escape',
    );

    foreach ($settings as $key => $value) {
        if (isset($option_map[$key])) {
            update_option($option_map[$key], $value);
        }
    }

    return true;
}

/**
 * Reset settings to defaults
 */
function n8nchwi_reset_settings() {
    // Delete all options
    $options_to_delete = array(
        'n8n_chat_widget_url',
        'n8n_chat_widget_enabled',
        'n8n_chat_widget_position',
        'n8n_chat_widget_title',
        'n8n_chat_widget_color',
        'n8n_chat_widget_icon',
        'n8n_chat_widget_icon_type',
        'n8n_chat_widget_svg_icon',
        'n8n_chat_widget_zoom',
        'n8n_chat_widget_theme_preset',
        'n8n_chat_widget_welcome_enabled',
        'n8n_chat_widget_welcome_message',
        'n8n_chat_widget_welcome_delay',
        'n8n_chat_widget_auto_open',
        'n8n_chat_widget_auto_open_delay',
        'n8n_chat_widget_sound_enabled',
        'n8n_chat_widget_remember_state',
        'n8n_chat_widget_button_style',
        'n8n_chat_widget_popup_style',
        'n8n_chat_widget_animation_style',
        'n8n_chat_widget_margin_bottom',
        'n8n_chat_widget_margin_side',
        'n8n_chat_widget_mobile_margin_bottom',
        'n8n_chat_widget_mobile_margin_side',
        'n8n_chat_widget_display_mode',
        'n8n_chat_widget_include_pages',
        'n8n_chat_widget_exclude_pages',
        'n8n_chat_widget_show_on_mobile',
        'n8n_chat_widget_powered_by',
        'n8n_chat_widget_powered_text',
        'n8n_chat_widget_header_logo',
        'n8n_chat_widget_custom_css',
        'n8n_chat_widget_close_on_outside',
        'n8n_chat_widget_close_on_escape',
    );

    foreach ($options_to_delete as $option) {
        delete_option($option);
    }

    // Re-add defaults
    n8nchwi_activate();

    return true;
}

// Add plugin action links
function n8nchwi_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=n8n-chat-widget') . '">' . __('Settings', 'n8n-chat-widget') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'n8nchwi_plugin_action_links');

// Handle AJAX actions
add_action('wp_ajax_n8nchwi_export_settings', 'n8nchwi_ajax_export_settings');
add_action('wp_ajax_n8nchwi_import_settings', 'n8nchwi_ajax_import_settings');
add_action('wp_ajax_n8nchwi_reset_settings', 'n8nchwi_ajax_reset_settings');

function n8nchwi_ajax_export_settings() {
    check_ajax_referer('n8nchwi_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    wp_send_json_success(array('settings' => n8nchwi_export_settings()));
}

function n8nchwi_ajax_import_settings() {
    check_ajax_referer('n8nchwi_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $settings = isset($_POST['settings']) ? wp_unslash($_POST['settings']) : '';

    if (n8nchwi_import_settings($settings)) {
        wp_send_json_success('Settings imported successfully');
    } else {
        wp_send_json_error('Invalid settings data');
    }
}

function n8nchwi_ajax_reset_settings() {
    check_ajax_referer('n8nchwi_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    if (n8nchwi_reset_settings()) {
        wp_send_json_success('Settings reset successfully');
    } else {
        wp_send_json_error('Error resetting settings');
    }
}
