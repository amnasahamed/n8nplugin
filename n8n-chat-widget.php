<?php
/**
 * Plugin Name: n8n Chat Widget
 * Description: Add a customizable n8n chat widget to your website frontend
 * Version: 1.0.0
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
define('N8N_CHAT_WIDGET_VERSION', '1.0.0');
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
    // Add default options
    add_option('n8n_chat_widget_url', '');
    add_option('n8n_chat_widget_enabled', 'yes');
    add_option('n8n_chat_widget_position', 'right');
    add_option('n8n_chat_widget_title', 'Chat Support');
    add_option('n8n_chat_widget_color', '#45d3d3');
    add_option('n8n_chat_widget_icon', '💬');
    add_option('n8n_chat_widget_icon_type', 'emoji'); // emoji or svg
    add_option('n8n_chat_widget_svg_icon', '');
    add_option('n8n_chat_widget_zoom', '100');

    // Page targeting options
    add_option('n8n_chat_widget_targeting_mode', 'all'); // all, include, exclude
    add_option('n8n_chat_widget_targeting_pages', ''); // comma-separated page IDs or URLs
    add_option('n8n_chat_widget_hide_on_mobile', 'no');
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
            'enabled' => get_option('n8n_chat_widget_enabled', 'yes'),
            'url' => get_option('n8n_chat_widget_url', ''),
            'position' => get_option('n8n_chat_widget_position', 'right'),
            'title' => get_option('n8n_chat_widget_title', 'Chat Support'),
            'color' => get_option('n8n_chat_widget_color', '#45d3d3'),
            'icon' => get_option('n8n_chat_widget_icon', '💬'),
            'icon_type' => get_option('n8n_chat_widget_icon_type', 'emoji'),
            'svg_icon' => get_option('n8n_chat_widget_svg_icon', ''),
            'zoom' => get_option('n8n_chat_widget_zoom', '100'),
            'targeting_mode' => get_option('n8n_chat_widget_targeting_mode', 'all'),
            'targeting_pages' => get_option('n8n_chat_widget_targeting_pages', ''),
            'hide_on_mobile' => get_option('n8n_chat_widget_hide_on_mobile', 'no'),
        );
    }

    return $options;
}

/**
 * Check if widget should display on current page
 *
 * @return bool Whether to show the widget
 */
function n8nchwi_should_display() {
    $options = n8nchwi_get_options();

    // Check if widget is enabled
    if ($options['enabled'] !== 'yes') {
        return false;
    }

    // Check if URL is set
    if (empty($options['url'])) {
        return false;
    }

    // Check mobile hiding (basic check - JS will handle actual mobile detection)
    if ($options['hide_on_mobile'] === 'yes' && wp_is_mobile()) {
        return false;
    }

    // Check page targeting
    $targeting_mode = $options['targeting_mode'];

    if ($targeting_mode === 'all') {
        return true;
    }

    // Get current URL path
    $current_url = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
    $current_page_id = get_queried_object_id();

    // Parse targeting pages
    $targeting_pages = array_map('trim', explode("\n", $options['targeting_pages']));
    $targeting_pages = array_filter($targeting_pages); // Remove empty lines

    if (empty($targeting_pages)) {
        // If no pages specified, show on all pages (regardless of mode)
        return true;
    }

    $page_matched = false;

    foreach ($targeting_pages as $target) {
        // Check if it's a page ID (numeric)
        if (is_numeric($target)) {
            if ((int)$target === $current_page_id) {
                $page_matched = true;
                break;
            }
        } else {
            // It's a URL pattern
            $target = trim($target, '/');
            $current_path = trim(wp_parse_url($current_url, PHP_URL_PATH), '/');

            // Check for exact match or wildcard match
            if ($target === $current_path) {
                $page_matched = true;
                break;
            }

            // Support wildcard patterns (e.g., "blog/*")
            if (strpos($target, '*') !== false) {
                $pattern = str_replace('*', '.*', preg_quote($target, '/'));
                if (preg_match('/^' . $pattern . '$/i', $current_path)) {
                    $page_matched = true;
                    break;
                }
            }

            // Support "contains" matching
            if (strpos($current_path, $target) !== false) {
                $page_matched = true;
                break;
            }
        }
    }

    // Return based on targeting mode
    if ($targeting_mode === 'include') {
        return $page_matched;
    } elseif ($targeting_mode === 'exclude') {
        return !$page_matched;
    }

    return true;
}

/**
 * Enqueue frontend scripts and styles
 */
function n8nchwi_enqueue_scripts() {
    // Check if widget should display on this page
    if (!n8nchwi_should_display()) {
        return;
    }

    $options = n8nchwi_get_options();

    // Only enqueue if the widget is enabled
    if ($options['enabled'] === 'yes') {
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
            'zoom' => $zoom
        ));

        // Set CSS custom properties for theme colors
        $color = esc_attr($options['color']);
        $hover_color = esc_attr(n8nchwi_adjust_color_brightness($options['color'], -15));

        $custom_css = ":root {
            --n8n-widget-color: {$color};
            --n8n-widget-color-hover: {$hover_color};
        }";
        wp_add_inline_style('n8n-chat-widget-style', $custom_css);
    }
}
add_action('wp_enqueue_scripts', 'n8nchwi_enqueue_scripts');

/**
 * Add the chat widget to the footer
 */
function n8nchwi_add_to_footer() {
    // Check if widget should display on this page
    if (!n8nchwi_should_display()) {
        return;
    }

    $options = n8nchwi_get_options();

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

// Add plugin action links
function n8nchwi_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=n8n-chat-widget') . '">' . __('Settings', 'n8n-chat-widget') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'n8nchwi_plugin_action_links'); 