/**
 * n8n Chat Widget Gutenberg Block
 */
(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { useBlockProps, InspectorControls } = wp.blockEditor;
    const { PanelBody, ToggleControl, TextControl, ColorPicker } = wp.components;
    const { __ } = wp.i18n;
    const { createElement, Fragment } = wp.element;

    registerBlockType('n8n-chat-widget/chat-button', {
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { showOnThisPage, customTitle, customColor } = attributes;

            const blockProps = useBlockProps({
                className: 'n8n-chat-widget-block'
            });

            return createElement(
                Fragment,
                null,
                createElement(
                    InspectorControls,
                    null,
                    createElement(
                        PanelBody,
                        { title: __('Chat Widget Settings', 'n8n-chat-widget'), initialOpen: true },
                        createElement(ToggleControl, {
                            label: __('Show widget on this page', 'n8n-chat-widget'),
                            checked: showOnThisPage,
                            onChange: function(value) {
                                setAttributes({ showOnThisPage: value });
                            },
                            help: showOnThisPage
                                ? __('The chat widget will appear on this page.', 'n8n-chat-widget')
                                : __('The chat widget will be hidden on this page.', 'n8n-chat-widget')
                        }),
                        createElement(TextControl, {
                            label: __('Custom Title (optional)', 'n8n-chat-widget'),
                            value: customTitle,
                            onChange: function(value) {
                                setAttributes({ customTitle: value });
                            },
                            placeholder: __('Override global title', 'n8n-chat-widget')
                        }),
                        createElement(
                            'div',
                            { className: 'n8n-block-color-picker' },
                            createElement(
                                'label',
                                { className: 'components-base-control__label' },
                                __('Custom Color (optional)', 'n8n-chat-widget')
                            ),
                            createElement(ColorPicker, {
                                color: customColor,
                                onChange: function(value) {
                                    setAttributes({ customColor: value });
                                },
                                enableAlpha: false
                            }),
                            customColor && createElement(
                                'button',
                                {
                                    className: 'components-button is-secondary is-small',
                                    onClick: function() {
                                        setAttributes({ customColor: '' });
                                    },
                                    style: { marginTop: '8px' }
                                },
                                __('Reset to default', 'n8n-chat-widget')
                            )
                        )
                    )
                ),
                createElement(
                    'div',
                    blockProps,
                    createElement(
                        'div',
                        { className: 'n8n-chat-widget-block-preview' },
                        createElement(
                            'div',
                            { className: 'n8n-block-icon' },
                            createElement('span', { className: 'dashicons dashicons-format-chat' })
                        ),
                        createElement(
                            'div',
                            { className: 'n8n-block-info' },
                            createElement(
                                'strong',
                                null,
                                __('n8n Chat Widget', 'n8n-chat-widget')
                            ),
                            createElement(
                                'span',
                                { className: showOnThisPage ? 'status-enabled' : 'status-disabled' },
                                showOnThisPage
                                    ? __('Enabled on this page', 'n8n-chat-widget')
                                    : __('Disabled on this page', 'n8n-chat-widget')
                            ),
                            customTitle && createElement(
                                'span',
                                { className: 'custom-title' },
                                __('Title: ', 'n8n-chat-widget') + customTitle
                            ),
                            customColor && createElement(
                                'span',
                                { className: 'custom-color' },
                                __('Custom color: ', 'n8n-chat-widget'),
                                createElement('span', {
                                    className: 'color-swatch',
                                    style: { backgroundColor: customColor }
                                })
                            )
                        )
                    )
                )
            );
        },

        save: function() {
            // Server-side rendered
            return null;
        }
    });
})(window.wp);
