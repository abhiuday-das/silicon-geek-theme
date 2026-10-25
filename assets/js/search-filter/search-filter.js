( function ( blocks, element, blockEditor, components ) {
    const { registerBlockType } = blocks;
    const { Fragment, createElement: el } = element;
    const { InspectorControls } = blockEditor;
    const { PanelBody, TextControl, SelectControl } = components;

    registerBlockType( 'ts-theme/search-filter', {
        title: 'Search Filter',
        description: 'Display a search form with URL-based filtering',
        icon: 'search',
        category: 'design',
        keywords: ['search', 'filter', 'find'],
        supports: {
            html: false,
            align: ['wide', 'full']
        },
        attributes: {
            placeholder: { type: 'string', default: 'Search...' },
            buttonText: { type: 'string', default: 'Search' },
            alignment: { type: 'string', default: 'left' },
            postType: { type: 'string', default: 'post' },
        },

        edit: function ( props ) {
            const { attributes, setAttributes } = props;
            const { placeholder, buttonText, alignment, postType } = attributes;

            return el( Fragment, {},
                el( InspectorControls, {},
                    el( PanelBody, { title: 'Search Settings', initialOpen: true },
                        el( TextControl, {
                            label: 'Placeholder Text',
                            value: placeholder,
                            onChange: ( val ) => setAttributes( { placeholder: val } ),
                            help: 'Text shown in the search input'
                        } ),
                        el( TextControl, {
                            label: 'Button Text',
                            value: buttonText,
                            onChange: ( val ) => setAttributes( { buttonText: val } ),
                            help: 'Text shown on the search button'
                        } ),
                        el( TextControl, {
                            label: 'Post Type',
                            value: postType,
                            onChange: ( val ) => setAttributes( { postType: val } ),
                            help: 'Post type to search (e.g., post, page)'
                        } ),
                        el( SelectControl, {
                            label: 'Alignment',
                            value: alignment,
                            options: [
                                { label: 'Left', value: 'left' },
                                { label: 'Center', value: 'center' },
                                { label: 'Right', value: 'right' }
                            ],
                            onChange: ( val ) => setAttributes( { alignment: val } )
                        } )
                    )
                ),
                el( 'div', {
                    style: {
                        display: 'flex',
                        justifyContent: alignment === 'center' ? 'center' : alignment === 'right' ? 'flex-end' : 'flex-start'
                    }
                },
                    el( 'div', { className: 'input-group', style: { maxWidth: '500px' } },
                        el( 'input', {
                            type: 'search',
                            className: 'form-control',
                            placeholder: placeholder,
                            disabled: true,
                            value: ''
                        } ),
                        el( 'button', {
                            type: 'button',
                            className: 'btn btn-primary',
                            disabled: true
                        }, buttonText )
                    )
                )
            );
        },

        save: function () {
            // Server-side rendered block
            return null;
        }
    } );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components );
