/**
 * Read More / Read Less Toggle Block - Editor
 * 
 * Gutenberg block with two InnerBlocks sections for collapsible content.
 */

(function() {
    const { registerBlockType } = wp.blocks;
    const { InnerBlocks, InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, TextControl, ToggleControl } = wp.components;
    const { createElement: el } = wp.element;
    const { __ } = wp.i18n;

    /**
     * Register Read More Toggle Block
     */
    registerBlockType('ts-block-theme/read-more-toggle', {
        title: __('Read More Toggle', 'ts-block-theme'),
        icon: 'visibility',
        category: 'text',
        description: __('Collapsible content section with read more/less toggle', 'ts-block-theme'),
        keywords: [__('read more', 'ts-block-theme'), __('toggle', 'ts-block-theme'), __('collapse', 'ts-block-theme')],
        supports: {
            align: true,
            html: false,
            color: {
                background: true,
                text: true
            },
            spacing: {
                padding: true,
                margin: true
            }
        },
        attributes: {
            readMoreText: {
                type: 'string',
                default: 'Read More'
            },
            readLessText: {
                type: 'string',
                default: 'Read Less'
            },
            expanded: {
                type: 'boolean',
                default: false
            }
        },

        /**
         * Edit function - Editor interface
         */
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { readMoreText, readLessText, expanded } = attributes;
            const blockProps = useBlockProps({
                className: 'read-more-toggle-editor'
            });

            // Template for InnerBlocks - Two sections
            const TEMPLATE = [
                ['core/group', {
                    className: 'read-more-visible-section',
                    metadata: { name: 'Always Visible Content' }
                }, [
                    ['core/heading', { 
                        level: 3, 
                        placeholder: __('Add heading here...', 'ts-block-theme'),
                        content: __('Always Visible Content', 'ts-block-theme')
                    }],
                    ['core/paragraph', { 
                        placeholder: __('Add content that is always visible...', 'ts-block-theme')
                    }]
                ]],
                ['core/group', {
                    className: 'read-more-hidden-section',
                    metadata: { name: 'Hidden Content (Expandable)' }
                }, [
                    ['core/heading', { 
                        level: 3, 
                        placeholder: __('Add heading here...', 'ts-block-theme'),
                        content: __('Hidden Content (Expandable)', 'ts-block-theme')
                    }],
                    ['core/paragraph', { 
                        placeholder: __('Add content that will be hidden until "Read More" is clicked...', 'ts-block-theme')
                    }]
                ]]
            ];

            return el('div', blockProps,
                // Inspector Controls (Sidebar)
                el(InspectorControls, null,
                    el(PanelBody, {
                        title: __('Toggle Settings', 'ts-block-theme'),
                        initialOpen: true
                    },
                        el(TextControl, {
                            label: __('Read More Button Text', 'ts-block-theme'),
                            value: readMoreText,
                            onChange: function(value) {
                                setAttributes({ readMoreText: value });
                            },
                            help: __('Text for the expand button', 'ts-block-theme')
                        }),
                        el(TextControl, {
                            label: __('Read Less Button Text', 'ts-block-theme'),
                            value: readLessText,
                            onChange: function(value) {
                                setAttributes({ readLessText: value });
                            },
                            help: __('Text for the collapse button', 'ts-block-theme')
                        }),
                        el(ToggleControl, {
                            label: __('Expanded by Default', 'ts-block-theme'),
                            checked: expanded,
                            onChange: function(value) {
                                setAttributes({ expanded: value });
                            },
                            help: __('Show content expanded when page loads', 'ts-block-theme')
                        })
                    )
                ),

                // Editor Preview
                el('div', { className: 'read-more-toggle-preview' },
                    // InnerBlocks with template
                    el('div', { className: 'read-more-toggle-content' },
                        el(InnerBlocks, {
                            template: TEMPLATE,
                            templateLock: false,
                            allowedBlocks: null // Allow all blocks
                        })
                    ),

                    // Preview of toggle buttons
                    el('div', { 
                        className: 'read-more-toggle-buttons-preview',
                        style: {
                            marginTop: '20px',
                            padding: '12px',
                            backgroundColor: '#f9f9f9',
                            border: '1px dashed #ccc',
                            textAlign: 'center'
                        }
                    },
                        el('p', { 
                            style: { 
                                margin: '0 0 10px 0', 
                                fontSize: '12px',
                                color: '#666'
                            }
                        }, __('Toggle Button Preview:', 'ts-block-theme')),
                        el('button', {
                            className: 'read-more-button-preview',
                            style: {
                                padding: '8px 16px',
                                fontSize: '14px',
                                cursor: 'default',
                                backgroundColor: '#2271b1',
                                color: '#fff',
                                border: 'none',
                                borderRadius: '4px'
                            },
                            disabled: true
                        }, readMoreText + ' +'),
                        el('span', { style: { margin: '0 10px', color: '#ccc' } }, '/'),
                        el('button', {
                            className: 'read-less-button-preview',
                            style: {
                                padding: '8px 16px',
                                fontSize: '14px',
                                cursor: 'default',
                                backgroundColor: '#2271b1',
                                color: '#fff',
                                border: 'none',
                                borderRadius: '4px'
                            },
                            disabled: true
                        }, readLessText + ' −')
                    )
                )
            );
        },

        /**
         * Save function - Uses InnerBlocks.Content
         */
        save: function(props) {
            const blockProps = useBlockProps.save({
                className: 'read-more-toggle-wrapper'
            });

            return el('div', blockProps,
                el(InnerBlocks.Content)
            );
        }
    });
})();
