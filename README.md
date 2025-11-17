# n8n Chat Widget

A WordPress plugin that adds a customizable n8n chat widget to your website frontend.

## Changes Made to Fix WordPress Repository Issues

1. **Added Direct File Access Prevention**
   - Added `if (!defined('ABSPATH')) exit;` to public/partials/n8n-chat-widget-public-display.php

2. **Fixed JS and CSS Enqueuing**
   - Replaced inline styles and scripts with properly enqueued files
   - Created separate CSS file in admin/css/n8n-chat-widget-admin.css
   - Created separate JS files for preview functionality
   - Used wp_add_inline_style for dynamic styles

3. **Added Documentation for 3rd Party Service**
   - Added "External Services" section to readme.txt
   - Documented what data is sent to n8n, when, and why
   - Added links to n8n's terms of service and privacy policy

4. **Fixed Generic Function/Class Names**
   - Changed class name from `N8N_Chat_Widget_Admin` to `N8NCHWI_Admin`
   - Added `n8nchwi_` prefix to all functions:
     - n8nchwi_activate
     - n8nchwi_deactivate
     - n8nchwi_init_admin
     - n8nchwi_enqueue_scripts
     - n8nchwi_add_to_footer
     - n8nchwi_adjust_color_brightness
     - n8nchwi_display_svg
     - n8nchwi_plugin_action_links
   - Changed JavaScript localization variable names to use consistent prefixing

## Features

- Easily integrate n8n chat workflows into your website
- Customizable chat widget appearance:
  - Choose the widget position (left or right)
  - Set custom widget title
  - Change the primary color
  - Select custom emoji icon or upload an SVG icon
  - Adjust content zoom level (50% - 150%)
- Mobile-responsive design
- Smooth animations
- Lightweight implementation

## Requirements

- WordPress 5.0 or higher
- n8n with an existing chat workflow

## Installation

1. Upload the `n8n-chat-widget` folder to the `/wp-content/plugins/`