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

    // Welcome message options
    add_option('n8n_chat_widget_welcome_enabled', 'no');
    add_option('n8n_chat_widget_welcome_message', 'Hi there! How can I help you today?');
    add_option('n8n_chat_widget_welcome_delay', '3');

    // Business hours options
    add_option('n8n_chat_widget_schedule_enabled', 'no');
    add_option('n8n_chat_widget_schedule_timezone', 'site'); // site or UTC
    add_option('n8n_chat_widget_schedule_days', 'mon,tue,wed,thu,fri'); // comma-separated
    add_option('n8n_chat_widget_schedule_start', '09:00');
    add_option('n8n_chat_widget_schedule_end', '17:00');

    // Analytics option
    add_option('n8n_chat_widget_analytics_enabled', 'yes');

    // Create analytics table
    n8nchwi_create_analytics_table();
}

/**
 * Create analytics database table
 */
function n8nchwi_create_analytics_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'n8n_chat_analytics';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        event_type varchar(50) NOT NULL,
        event_date date NOT NULL,
        event_count int(11) NOT NULL DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY event_date_type (event_date, event_type),
        KEY event_type (event_type),
        KEY event_date (event_date)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
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
            'welcome_enabled' => get_option('n8n_chat_widget_welcome_enabled', 'no'),
            'welcome_message' => get_option('n8n_chat_widget_welcome_message', 'Hi there! How can I help you today?'),
            'welcome_delay' => get_option('n8n_chat_widget_welcome_delay', '3'),
            'schedule_enabled' => get_option('n8n_chat_widget_schedule_enabled', 'no'),
            'schedule_timezone' => get_option('n8n_chat_widget_schedule_timezone', 'site'),
            'schedule_days' => get_option('n8n_chat_widget_schedule_days', 'mon,tue,wed,thu,fri'),
            'schedule_start' => get_option('n8n_chat_widget_schedule_start', '09:00'),
            'schedule_end' => get_option('n8n_chat_widget_schedule_end', '17:00'),
            'analytics_enabled' => get_option('n8n_chat_widget_analytics_enabled', 'yes'),
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

    // Check business hours schedule
    if ($options['schedule_enabled'] === 'yes') {
        if (!n8nchwi_is_within_schedule($options)) {
            return false;
        }
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
 * Check if current time is within business hours schedule
 *
 * @param array $options Widget options
 * @return bool Whether current time is within schedule
 */
function n8nchwi_is_within_schedule($options) {
    // Get timezone
    if ($options['schedule_timezone'] === 'site') {
        $timezone = wp_timezone();
    } else {
        $timezone = new DateTimeZone('UTC');
    }

    // Get current time in the configured timezone
    $now = new DateTime('now', $timezone);
    $current_day = strtolower($now->format('D')); // mon, tue, wed, etc.
    $current_time = $now->format('H:i');

    // Check if current day is in schedule
    $schedule_days = array_map('trim', explode(',', strtolower($options['schedule_days'])));
    if (!in_array($current_day, $schedule_days)) {
        return false;
    }

    // Check if current time is within schedule
    $start_time = $options['schedule_start'];
    $end_time = $options['schedule_end'];

    // Handle overnight schedules (e.g., 22:00 to 06:00)
    if ($start_time > $end_time) {
        // Overnight schedule
        return ($current_time >= $start_time || $current_time < $end_time);
    } else {
        // Normal schedule
        return ($current_time >= $start_time && $current_time < $end_time);
    }
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
            'zoom' => $zoom,
            'welcomeEnabled' => esc_attr($options['welcome_enabled']),
            'welcomeMessage' => esc_html($options['welcome_message']),
            'welcomeDelay' => intval($options['welcome_delay']),
            'analyticsEnabled' => esc_attr($options['analytics_enabled']),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'analyticsNonce' => wp_create_nonce('n8nchwi_analytics')
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
 * AJAX handler to record analytics event
 */
function n8nchwi_record_analytics() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'n8nchwi_analytics')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
    }

    // Get event type
    $event_type = isset($_POST['event_type']) ? sanitize_text_field(wp_unslash($_POST['event_type'])) : '';
    $valid_events = array('widget_load', 'chat_open', 'chat_close', 'welcome_click', 'welcome_dismiss');

    if (!in_array($event_type, $valid_events)) {
        wp_send_json_error(array('message' => 'Invalid event type.'));
    }

    // Check if analytics is enabled
    if (get_option('n8n_chat_widget_analytics_enabled', 'yes') !== 'yes') {
        wp_send_json_success(array('message' => 'Analytics disabled.'));
        return;
    }

    // Record event
    global $wpdb;
    $table_name = $wpdb->prefix . 'n8n_chat_analytics';
    $today = gmdate('Y-m-d');

    // Insert or update (increment count if exists)
    $result = $wpdb->query(
        $wpdb->prepare(
            "INSERT INTO $table_name (event_type, event_date, event_count)
            VALUES (%s, %s, 1)
            ON DUPLICATE KEY UPDATE event_count = event_count + 1",
            $event_type,
            $today
        )
    );

    if ($result !== false) {
        wp_send_json_success(array('message' => 'Event recorded.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to record event.'));
    }
}
add_action('wp_ajax_n8nchwi_record_analytics', 'n8nchwi_record_analytics');
add_action('wp_ajax_nopriv_n8nchwi_record_analytics', 'n8nchwi_record_analytics');

/**
 * Get analytics data for dashboard
 *
 * @param int $days Number of days to retrieve
 * @return array Analytics data
 */
function n8nchwi_get_analytics_data($days = 30) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'n8n_chat_analytics';

    // Get date range
    $end_date = gmdate('Y-m-d');
    $start_date = gmdate('Y-m-d', strtotime("-{$days} days"));

    // Get aggregated data
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT event_type, SUM(event_count) as total
            FROM $table_name
            WHERE event_date BETWEEN %s AND %s
            GROUP BY event_type",
            $start_date,
            $end_date
        ),
        ARRAY_A
    );

    // Format results
    $data = array(
        'widget_load' => 0,
        'chat_open' => 0,
        'chat_close' => 0,
        'welcome_click' => 0,
        'welcome_dismiss' => 0,
    );

    foreach ($results as $row) {
        $data[$row['event_type']] = intval($row['total']);
    }

    // Get daily data for chart
    $daily_results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT event_date, event_type, event_count
            FROM $table_name
            WHERE event_date BETWEEN %s AND %s
            ORDER BY event_date ASC",
            $start_date,
            $end_date
        ),
        ARRAY_A
    );

    // Format daily data
    $daily_data = array();
    foreach ($daily_results as $row) {
        $date = $row['event_date'];
        if (!isset($daily_data[$date])) {
            $daily_data[$date] = array(
                'widget_load' => 0,
                'chat_open' => 0,
            );
        }
        if (isset($daily_data[$date][$row['event_type']])) {
            $daily_data[$date][$row['event_type']] = intval($row['event_count']);
        }
    }

    return array(
        'totals' => $data,
        'daily' => $daily_data,
        'days' => $days,
        'start_date' => $start_date,
        'end_date' => $end_date,
    );
}

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