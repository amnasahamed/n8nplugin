<?php
/**
 * Uninstall script for n8n Chat Widget
 *
 * This file runs when the plugin is deleted from WordPress.
 * It cleans up all plugin data from the database.
 *
 * @package N8N_Chat_Widget
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up plugin options from the database
 */
function n8nchwi_uninstall_cleanup() {
    // List of all options used by this plugin
    $options = array(
        'n8n_chat_widget_url',
        'n8n_chat_widget_enabled',
        'n8n_chat_widget_position',
        'n8n_chat_widget_title',
        'n8n_chat_widget_color',
        'n8n_chat_widget_icon',
        'n8n_chat_widget_icon_type',
        'n8n_chat_widget_svg_icon',
        'n8n_chat_widget_zoom',
    );

    // Delete each option
    foreach ($options as $option) {
        delete_option($option);
    }

    // For multisite, clean up options from all sites
    if (is_multisite()) {
        global $wpdb;

        // Get all blog IDs
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);

            // Delete options for this site
            foreach ($options as $option) {
                delete_option($option);
            }

            restore_current_blog();
        }
    }
}

// Run cleanup
n8nchwi_uninstall_cleanup();
