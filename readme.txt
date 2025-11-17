=== n8n Chat Widget ===
Contributors: farhansrambiyan
Tags: chat, n8n, widget, support, customer service
Requires at least: 5.0
Tested up to: 6.8.1
Stable tag: 1.0.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a customizable n8n chat widget to your website frontend. It allows visitors to interact with n8n chat workflows directly from your website through a popup interface.

== Description ==

This plugin adds a customizable n8n chat widget to your website frontend. It allows visitors to interact with n8n chat workflows directly from your website through a popup interface.

= Features =

* Easily integrate n8n chat workflows into your website
* Customizable chat widget appearance:
  * Choose the widget position (left or right)
  * Set custom widget title
  * Change the primary color
  * Select custom emoji icon or upload SVG icon
  * Adjust content zoom level (50% - 150%)
* Mobile-responsive design
* Smooth animations
* Lightweight implementation

= Requirements =

* WordPress 5.0 or higher
* n8n with an existing chat workflow

== Installation ==

1. Upload the `n8n-chat-widget` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to n8n Chat Widget in the main admin menu to configure

== Configuration ==

1. **n8n Chat URL**: Enter the full URL of your n8n chat webhook (e.g., `https://n8n.example.com/webhook/your-chat-id/chat`)
2. **Enable Chat Widget**: Toggle to enable or disable the chat widget on your website
3. **Widget Position**: Choose between right or left side positioning
4. **Chat Widget Title**: Set the title that appears in the chat header
5. **Widget Color**: Select a custom color for the widget button and header
6. **Chat Icon**: Choose an emoji or upload an SVG icon for the chat button
7. **Chat Content Zoom**: Adjust the zoom level of the chat content

== External Services ==

This plugin connects to an external service (n8n) to provide chat functionality:

= n8n Chat Service =
* **What is it?**: n8n is a workflow automation platform that can be configured to provide AI-powered chat functionality.
* **What data is sent**: When a user interacts with the chat widget, their messages are sent to your n8n instance via the webhook URL you provide in the settings.
* **When data is sent**: Data is only sent when a user actively engages with the chat widget by opening it and sending messages.
* **Terms and Privacy**: This plugin requires you to have your own n8n instance or use n8n Cloud. For more information about n8n's terms and privacy policy, please visit:
  * [n8n Terms of Service](https://n8n.io/legal/terms/)
  * [n8n Privacy Policy](https://n8n.io/legal/privacy/)

Note: You are responsible for ensuring that your use of n8n complies with all applicable laws and regulations regarding user data and privacy.

== Frequently Asked Questions ==

= Can I customize the appearance of the chat widget? =

Yes, you can customize the widget title, position, color, and icon. You can also adjust the zoom level to make the content larger or smaller.

= Does this plugin work with any n8n chat workflows? =

Yes, this plugin works with any n8n chat workflow that is publicly accessible via a webhook URL.

== Screenshots ==

1. The chat widget on the frontend
2. Admin settings page

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release 