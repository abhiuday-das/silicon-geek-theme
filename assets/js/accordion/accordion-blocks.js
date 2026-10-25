( function( blocks, element, blockEditor, components ) {
    const { registerBlockType } = blocks;
    const { Fragment, createElement: el } = element;
    const { InspectorControls, useBlockProps, InnerBlocks } = blockEditor;
    const { PanelBody, TextControl, ToggleControl } = components;

    /**
     * Accordion Item Block (Child)
     */
    registerBlockType( 'understrap/accordion-item', {
        title: 'Accordion Item',
        icon: 'list-view',
        category: 'widgets',
        parent: [ 'understrap/accordion' ],
        supports: {
            html: false,
            reusable: false,
            lock: false,
            inserter: true
        },
        attributes: {
            title: {
                type: 'string',
                default: 'Accordion Title'
            },
            isOpen: {
                type: 'boolean',
                default: false
            },
            itemId: {
                type: 'string',
                default: ''
            }
        },

        edit: function( props ) {
            const { attributes, setAttributes, clientId } = props;
            const { title, isOpen, itemId } = attributes;
            
            // Set unique item ID if not set
            if (!itemId) {
                setAttributes({ itemId: 'accordion-item-' + clientId });
            }

            const ALLOWED_BLOCKS = [ 'core/paragraph', 'core/heading', 'core/list', 'core/image' ];
            const TEMPLATE = [
                [ 'core/paragraph', { 
                    content: 'Add your accordion content here...',
                    placeholder: 'Add your accordion content here...'
                }]
            ];

            return el( Fragment, {},
                el( InspectorControls, {},
                    el( PanelBody, { title: 'Accordion Item Settings' },
                        el( TextControl, {
                            label: 'Accordion Title',
                            value: title,
                            onChange: function(value) {
                                setAttributes({ title: value });
                            }
                        }),
                        el( ToggleControl, {
                            label: 'Open by Default',
                            checked: isOpen,
                            onChange: function(value) {
                                setAttributes({ isOpen: value });
                            }
                        })
                    )
                ),
                el( 'div', { 
                    className: 'accordion-item-editor' + (isOpen ? ' is-open' : ''),
                    style: { 
                        border: '1px solid #ddd', 
                        borderRadius: '4px', 
                        marginBottom: '10px',
                        overflow: 'hidden'
                    }
                },
                    el( 'div', { 
                        className: 'accordion-header-preview',
                        style: { 
                            padding: '15px', 
                            backgroundColor: '#f8f9fa', 
                            borderBottom: '1px solid #ddd',
                            fontWeight: 'bold',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between'
                        }
                    },
                        el( 'span', {}, title ),
                        el( 'span', { 
                            className: 'accordion-icon',
                            style: { fontSize: '14px' }
                        }, isOpen ? '−' : '+' )
                    ),
                    el( 'div', { 
                        className: 'accordion-content-preview',
                        style: { 
                            padding: isOpen ? '15px' : '0',
                            height: isOpen ? 'auto' : '0',
                            overflow: 'hidden',
                            backgroundColor: '#fff'
                        }
                    },
                        el( InnerBlocks, {
                            allowedBlocks: ALLOWED_BLOCKS,
                            template: TEMPLATE,
                            templateLock: false,
                            renderAppender: InnerBlocks.DefaultBlockAppender
                        })
                    )
                )
            );
        },

        save: function( props ) {
            const { attributes } = props;
            const { title, isOpen, itemId } = attributes;

            return el( 'div', { 
                className: 'accordion-item',
                'data-item-id': itemId
            },
                el( 'h5', { className: 'accordion-header' },
                    el( 'button', {
                        className: 'accordion-button' + (isOpen ? '' : ' collapsed'),
                        type: 'button',
                        'data-bs-toggle': 'collapse',
                        'data-bs-target': '#' + itemId,
                        'aria-expanded': isOpen ? 'true' : 'false',
                        'aria-controls': itemId
                    }, title )
                ),
                el( 'div', {
                    id: itemId,
                    className: 'accordion-collapse collapse' + (isOpen ? ' show' : ''),
                    'data-bs-parent': '#accordion-parent'
                },
                    el( 'div', { className: 'accordion-body' },
                        el( InnerBlocks.Content )
                    )
                )
            );
        }
    });

    /**
     * Accordion Container Block (Parent)
     */
    registerBlockType( 'understrap/accordion', {
        title: 'Accordion',
        icon: 'list-view',
        category: 'widgets',
        description: 'Create collapsible accordion sections',
        supports: { 
            html: false,
            align: ['wide', 'full'],
            inserter: true
        },
        attributes: {
            accordionId: {
                type: 'string',
                default: ''
            },
            alwaysOpen: {
                type: 'boolean',
                default: false
            }
        },

        edit: function( props ) {
            const { attributes, setAttributes, clientId } = props;
            const { accordionId, alwaysOpen } = attributes;
            
            // Set unique accordion ID if not set
            if (!accordionId) {
                setAttributes({ accordionId: 'accordion-' + clientId });
            }

            const ALLOWED_BLOCKS = [ 'understrap/accordion-item' ];
            const TEMPLATE = [
                [ 'understrap/accordion-item', { title: 'Accordion Item 1' } ],
                [ 'understrap/accordion-item', { title: 'Accordion Item 2' } ],
                [ 'understrap/accordion-item', { title: 'Accordion Item 3' } ]
            ];

            return el( Fragment, {},
                el( InspectorControls, {},
                    el( PanelBody, { title: 'Accordion Settings' },
                        el( ToggleControl, {
                            label: 'Allow Multiple Open',
                            checked: alwaysOpen,
                            onChange: function(value) {
                                setAttributes({ alwaysOpen: value });
                            },
                            help: 'Allow multiple accordion items to be open at the same time'
                        })
                    )
                ),
                el( 'div', { 
                    className: 'wp-block-understrap-accordion accordion-editor',
                    style: { 
                        border: '2px dashed #ddd', 
                        padding: '15px', 
                        borderRadius: '4px' 
                    }
                },
                    el( 'div', { 
                        className: 'accordion-settings-preview',
                        style: { 
                            marginBottom: '15px', 
                            padding: '10px', 
                            backgroundColor: '#f0f0f0', 
                            borderRadius: '4px',
                            fontSize: '12px',
                            color: '#666'
                        }
                    },
                    ),
                    el( InnerBlocks, {
                        allowedBlocks: ALLOWED_BLOCKS,
                        template: TEMPLATE,
                        orientation: 'vertical'
                    })
                )
            );
        },

        save: function( props ) {
            const { attributes } = props;
            const { accordionId, alwaysOpen } = attributes;

            return el( 'div', { 
                className: 'wp-block-understrap-accordion accordion',
                id: 'accordion-parent',
                'data-accordion-id': accordionId,
                'data-always-open': alwaysOpen
            },
                el( InnerBlocks.Content )
            );
        }
    });

})( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components );