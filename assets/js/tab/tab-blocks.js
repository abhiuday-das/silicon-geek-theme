( function( blocks, element, blockEditor, components, i18n ) {
    const { registerBlockType } = blocks;
    const { Fragment, createElement: el } = element;
    const { InnerBlocks, InspectorControls } = blockEditor;
    const { PanelBody, TextControl, ToggleControl, SelectControl } = components;
    const { __ } = i18n;

    /**
     * Tab Item Block (Child)
     */
    registerBlockType( 'understrap/tab-item', {
        title: __('Tab Item', 'understrap'),
        icon: 'admin-page',
        category: 'widgets',
        parent: [ 'understrap/tabs' ],
        supports: { 
            html: true,
            reusable: false,
            lock: false,
            inserter: true
        },
        attributes: {
            tabTitle: {
                type: 'string',
                default: 'Tab Title'
            },
            tabDescription: {
                type: 'string',
                default: 'Tab Description'
            },
            enableLearnMore: {
                type: 'boolean',
                default: true
            },
            learnMoreText: {
                type: 'string',
                default: 'Learn More'
            },
            learnMoreUrl: {
                type: 'string',
                default: ''
            },
            tabId: {
                type: 'string',
                default: ''
            },
            isActive: {
                type: 'boolean',
                default: false
            }
        },

        edit: function( props ) {
            const { attributes, setAttributes, clientId } = props;
            const { tabTitle, tabDescription, enableLearnMore, learnMoreText, learnMoreUrl, tabId, isActive } = attributes;
            
            // Set unique tab ID if not set
            if (!tabId) {
                setAttributes({ tabId: 'tab-' + clientId });
            }

            const ALLOWED_BLOCKS = ['core/html'];
            const TEMPLATE = [
                ['core/html', { content: '<!-- Add your HTML/JSON content here -->' }]
            ];

            return el( Fragment, {},
                el( InspectorControls, {},
                    el( PanelBody, { title: __('Tab Settings', 'understrap') },
                        el( TextControl, {
                            label: __('Tab Title', 'understrap'),
                            value: tabTitle,
                            onChange: function(value) {
                                setAttributes({ tabTitle: value });
                            }
                        }),
                        el( TextControl, {
                            label: __('Tab Description', 'understrap'),
                            value: tabDescription,
                            onChange: function(value) {
                                setAttributes({ tabDescription: value });
                            }
                        }),
                        el( ToggleControl, {
                            label: __('Enable Learn More Link', 'understrap'),
                            checked: enableLearnMore,
                            onChange: function(value) {
                                setAttributes({ enableLearnMore: value });
                            }
                        }),
                        enableLearnMore && el( TextControl, {
                            label: __('Learn More Text', 'understrap'),
                            value: learnMoreText,
                            onChange: function(value) {
                                setAttributes({ learnMoreText: value });
                            }
                        }),
                        enableLearnMore && el( TextControl, {
                            label: __('Learn More URL', 'understrap'),
                            value: learnMoreUrl,
                            type: 'url',
                            onChange: function(value) {
                                setAttributes({ learnMoreUrl: value });
                            }
                        }),
                        el( ToggleControl, {
                            label: __('Active Tab', 'understrap'),
                            checked: isActive,
                            onChange: function(value) {
                                setAttributes({ isActive: value });
                            }
                        })
                    )
                ),
                el( 'div', { 
                    className: 'tab-pane editor-tab-pane' + (isActive ? ' active show' : ''),
                    style: { padding: '20px', border: '2px dashed #ddd', marginBottom: '10px' }
                },
                    el( 'div', { className: 'tab-header-preview', style: { marginBottom: '15px', fontWeight: 'bold', color: '#0073aa' } },
                        __('Tab: ', 'understrap') + tabTitle + (isActive ? ' (Active)' : ''),
                        tabDescription ? el( 'div', { style: { fontSize: '12px', fontWeight: 'normal', color: '#666' } }, tabDescription ) : null
                    ),
                    el( InnerBlocks, {
                        allowedBlocks: ALLOWED_BLOCKS,
                        template: TEMPLATE,
                        templateInsertUpdatesSelection: false,
                        templateLock: false,
                        renderAppender: InnerBlocks.DefaultBlockAppender
                    })
                )
            );
        },

        save: function( props ) {
            const { attributes } = props;
            const { tabTitle, tabDescription, enableLearnMore, learnMoreText, learnMoreUrl, tabId, isActive } = attributes;

            return el( 'div', { 
                className: 'tab-pane fade' + (isActive ? ' show active' : ''),
                id: tabId,
                role: 'tabpanel',
                'aria-labelledby': tabId + '-tab',
                'data-tab-title': tabTitle,
                'data-tab-description': tabDescription,
                'data-enable-learn-more': enableLearnMore ? 'true' : 'false',
                'data-learn-more-text': learnMoreText,
                'data-learn-more-url': learnMoreUrl
            },
                el( InnerBlocks.Content )
            );
        }
    });

    /**
     * Tabs Container Block (Parent)
     */
    registerBlockType( 'understrap/tabs', {
        title: __('Tabs', 'understrap'),
        icon: 'admin-page',
        category: 'widgets',
        description: __('Create tabbed content sections', 'understrap'),
        supports: { 
            html: true,
            align: ['wide', 'full'],
            inserter: true
        },
        attributes: {
            tabsStyle: {
                type: 'string',
                default: 'nav-tabs'
            },
            justified: {
                type: 'boolean',
                default: false
            },
            vertical: {
                type: 'boolean',
                default: true
            },
            fill: {
                type: 'boolean',
                default: false
            },
            tabsId: {
                type: 'string',
                default: ''
            }
        },

        edit: function( props ) {
            const { attributes, setAttributes, clientId } = props;
            const { tabsStyle, justified, vertical, fill, tabsId } = attributes;
            
            // Set unique tabs ID if not set
            if (!tabsId) {
                setAttributes({ tabsId: 'tabs-' + clientId });
            }

            const tabsStyleOptions = [
                { label: __('Tabs', 'understrap'), value: 'nav-tabs' },
                { label: __('Pills', 'understrap'), value: 'nav-pills' },
                { label: __('Underline', 'understrap'), value: 'nav-underline' }
            ];

            const ALLOWED_BLOCKS = [ 'understrap/tab-item' ];
            const TEMPLATE = [
                [ 'understrap/tab-item', { tabTitle: 'Tab 1', isActive: true } ],
                [ 'understrap/tab-item', { tabTitle: 'Tab 2' } ],
                [ 'understrap/tab-item', { tabTitle: 'Tab 3' } ],
                [ 'understrap/tab-item', { tabTitle: 'Tab 4' } ],
                [ 'understrap/tab-item', { tabTitle: 'Tab 5' } ],
                [ 'understrap/tab-item', { tabTitle: 'Tab 6' } ]
            ];

            return el( Fragment, {},
                el( InspectorControls, {},
                    el( PanelBody, { title: __('Tabs Settings', 'understrap') },
                        el( SelectControl, {
                            label: __('Tabs Style', 'understrap'),
                            value: tabsStyle,
                            options: tabsStyleOptions,
                            onChange: function(value) {
                                setAttributes({ tabsStyle: value });
                            }
                        }),
                        el( ToggleControl, {
                            label: __('Justified', 'understrap'),
                            checked: justified,
                            onChange: function(value) {
                                setAttributes({ justified: value });
                            }
                        }),
                        el( ToggleControl, {
                            label: __('Vertical Layout', 'understrap'),
                            checked: vertical,
                            onChange: function(value) {
                                setAttributes({ vertical: value });
                            }
                        }),
                        el( ToggleControl, {
                            label: __('Fill Available Space', 'understrap'),
                            checked: fill,
                            onChange: function(value) {
                                setAttributes({ fill: value });
                            }
                        })
                    )
                ),
                el( 'div', { 
                    className: 'wp-block-understrap-tabs editor-tabs' + (vertical ? ' tabs-vertical' : ''),
                    style: { border: '1px solid #ddd', padding: '15px', borderRadius: '4px' }
                },
                    el( 'div', { 
                        className: 'editor-tabs-preview',
                        style: { marginBottom: '15px', padding: '10px', backgroundColor: '#f8f9fa', borderRadius: '4px' }
                    },
                        el( 'strong', {}, __('Tabs Block Settings:', 'understrap') ),
                        el( 'br' ),
                        el( 'small', {}, 
                            __('Style: ', 'understrap') + tabsStyle + 
                            (justified ? __(', Justified', 'understrap') : '') +
                            (vertical ? __(', Vertical', 'understrap') : '') +
                            (fill ? __(', Fill', 'understrap') : '')
                        )
                    ),
                    el( InnerBlocks, {
                        allowedBlocks: ALLOWED_BLOCKS,
                        template: TEMPLATE,
                        orientation: vertical ? 'horizontal' : 'vertical'
                    })
                )
            );
        },

        save: function( props ) {
            const { attributes } = props;
            const { tabsStyle, justified, vertical, fill, tabsId } = attributes;

            return el( 'div', { 
                className: 'wp-block-understrap-tabs' + (vertical ? ' tabs-vertical' : ''),
                'data-tabs-style': tabsStyle,
                'data-justified': justified,
                'data-vertical': vertical,
                'data-fill': fill,
                id: tabsId
            },
                el( 'div', { 
                    className: 'tab-content',
                    id: tabsId + '-content'
                },
                    el( InnerBlocks.Content )
                )
            );
        }
    });

})( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n );
