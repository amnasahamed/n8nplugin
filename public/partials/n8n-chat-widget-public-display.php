<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * The public-facing view for the chat widget.
 */
$icon_type = get_option('n8n_chat_widget_icon_type', 'emoji');
$icon = get_option('n8n_chat_widget_icon', '💬');
$svg_icon = get_option('n8n_chat_widget_svg_icon', '');
?>
<div id="n8n-chat-widget-container" class="n8n-chat-widget-container n8n-chat-widget-position-<?php echo esc_attr(get_option('n8n_chat_widget_position', 'right')); ?>" role="region" aria-label="<?php esc_attr_e('Chat widget', 'n8n-chat-widget'); ?>">
    <div id="n8n-chat-widget-popup" class="n8n-chat-widget-popup" role="dialog" aria-labelledby="n8n-chat-widget-title" aria-modal="true">
        <div class="n8n-chat-widget-header">
            <div id="n8n-chat-widget-title" class="n8n-chat-widget-title"><?php echo esc_html(get_option('n8n_chat_widget_title', 'Chat Support')); ?></div>
            <button type="button" id="n8n-chat-widget-close" class="n8n-chat-widget-close" aria-label="<?php esc_attr_e('Close chat', 'n8n-chat-widget'); ?>">&times;</button>
        </div>
        <div class="n8n-chat-widget-frame-container">
            <div id="n8n-chat-widget-loading" class="n8n-chat-widget-loading" role="status" aria-live="polite" aria-label="<?php esc_attr_e('Loading chat', 'n8n-chat-widget'); ?>"></div>
            <iframe id="n8n-chat-widget-iframe" data-src="<?php echo esc_url(get_option('n8n_chat_widget_url')); ?>" frameborder="0" title="<?php esc_attr_e('Chat interface', 'n8n-chat-widget'); ?>"></iframe>
        </div>
    </div>
    <button type="button" id="n8n-chat-widget-button" class="n8n-chat-widget-button" aria-label="<?php esc_attr_e('Open chat', 'n8n-chat-widget'); ?>" aria-expanded="false">
        <?php if ($icon_type === 'emoji'): ?>
            <span class="n8n-chat-widget-icon"><?php echo esc_html($icon); ?></span>
        <?php else: ?>
            <?php if (!empty($svg_icon)): ?>
                <span class="n8n-chat-widget-svg-icon">
                    <?php 
                    // Try to get attachment ID from URL
                    $attachment_id = attachment_url_to_postid($svg_icon);
                    if ($attachment_id) {
                        echo wp_get_attachment_image($attachment_id, array(24, 24), false, array(
                            'alt' => esc_attr__('Chat', 'n8n-chat-widget'),
                            'class' => 'svg-icon'
                        ));
                    } else {
                        // Use the helper function for proper display
                        echo wp_kses_post(n8nchwi_display_svg($svg_icon, array(24, 24), array(
                            'alt' => esc_attr__('Chat', 'n8n-chat-widget'),
                            'class' => 'svg-icon'
                        )));
                    }
                    ?>
                </span>
            <?php else: ?>
                <span class="n8n-chat-widget-icon">💬</span>
            <?php endif; ?>
        <?php endif; ?>
    </button>
</div> 