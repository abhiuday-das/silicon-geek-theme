( function ( blocks, element, blockEditor, components, apiFetch ) {
    const { registerBlockType } = blocks;
    const { Fragment, createElement: el, useState, useEffect } = element;
    const { InspectorControls } = blockEditor;
    const { PanelBody, SelectControl, TextControl } = components;

    registerBlockType( 'ts-theme/category-filter', {
        title: 'Category Filter',
        description: 'Display a category/taxonomy filter with URL-based filtering',
        icon: 'filter',
        category: 'design',
        keywords: ['filter', 'category', 'taxonomy', 'sort'],
        supports: {
            html: false,
            align: ['wide', 'full']
        },
        attributes: {
            postType: { type: 'string', default: '' },
            taxonomy: { type: 'string', default: '' },
            display: { type: 'string', default: 'buttons' },
            showAllLabel: { type: 'string', default: 'All' },
            alignment: { type: 'string', default: 'left' },
        },

        edit: function ( props ) {
            const { attributes, setAttributes } = props;
            const { postType, taxonomy, display, showAllLabel, alignment } = attributes;

            const [ postTypes, setPostTypes ] = useState( [] );
            const [ taxonomies, setTaxonomies ] = useState( [] );
            const [ terms, setTerms ] = useState( [] );
            const [ loadingTerms, setLoadingTerms ] = useState( false );
            const [ error, setError ] = useState( '' );

            // Fetch available post types
            useEffect( () => {
                let mounted = true;
                apiFetch( { path: '/wp/v2/types' } ).then( ( types ) => {
                    if ( ! mounted ) return;
                    const list = Object.values( types ).map( ( t ) => ( { label: t.name, value: t.slug } ) );
                    setPostTypes( list );
                } ).catch( () => {
                    if ( ! mounted ) return;
                    setPostTypes( [ { label: 'post', value: 'post' } ] );
                } );
                return () => { mounted = false; };
            }, [] );

            // Fetch taxonomies for selected post type
            useEffect( () => {
                setTaxonomies( [] );
                setTerms( [] );
                setError( '' );
                if ( ! postType ) return;

                let mounted = true;
                apiFetch( { path: '/wp/v2/taxonomies?type=' + encodeURIComponent( postType ) } ).then( ( taxes ) => {
                    if ( ! mounted ) return;
                    const list = Object.values( taxes ).map( ( tx ) => ( { label: tx.name, value: tx.slug } ) );
                    setTaxonomies( list );
                } ).catch( () => {
                    if ( ! mounted ) return;
                    setError( 'Unable to load taxonomies' );
                } );
                return () => { mounted = false; };
            }, [ postType ] );

            // Fetch terms for selected taxonomy for preview
            useEffect( () => {
                setTerms( [] );
                setError( '' );
                if ( ! taxonomy ) return;

                setLoadingTerms( true );
                let mounted = true;

                // WP REST path for terms of a taxonomy is /wp/v2/{taxonomy}
                const path = '/wp/v2/' + encodeURIComponent( taxonomy ) + '?per_page=100';
                apiFetch( { path } ).then( ( termList ) => {
                    if ( ! mounted ) return;
                    setTerms( termList );
                    setLoadingTerms( false );
                } ).catch( () => {
                    if ( ! mounted ) return;
                    setError( 'Unable to load terms for preview' );
                    setLoadingTerms( false );
                } );

            return () => { mounted = false; };
        }, [ taxonomy ] );

        return el( Fragment, {},
            el( InspectorControls, {},
                el( PanelBody, { title: 'Filter Settings', initialOpen: true },
                    el( SelectControl, {
                        label: 'Post Type',
                        help: 'Select the post type to filter',
                        value: postType,
                        options: [ { label: '-- Select Post Type --', value: '' }, ...postTypes ],
                        onChange: ( val ) => setAttributes( { postType: val, taxonomy: '' } )
                    } ),
                    el( SelectControl, {
                        label: 'Taxonomy',
                        help: 'Select the taxonomy (category, tag, etc.)',
                        value: taxonomy,
                        options: [ { label: '-- Select Taxonomy --', value: '' }, ...taxonomies ],
                        onChange: ( val ) => setAttributes( { taxonomy: val } ),
                        disabled: ! postType
                    } ),
                    el( SelectControl, {
                        label: 'Display Style',
                        value: display,
                        options: [
                            { label: 'Buttons', value: 'buttons' },
                            { label: 'Dropdown', value: 'select' }
                        ],
                        onChange: ( val ) => setAttributes( { display: val } )
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
                    } ),
                    el( TextControl, {
                        label: '"All" Button Label',
                        value: showAllLabel,
                        onChange: ( val ) => setAttributes( { showAllLabel: val } ),
                        help: 'Text for the \'show all\' option'
                    } )
                )
            ),
            ! postType || ! taxonomy ? 
                el( 'div', { 
                    className: 'category-filter-editor-preview', 
                    style: { padding: '20px', border: '2px dashed #ddd', borderRadius: '4px', backgroundColor: '#f9f9f9', textAlign: 'center' } 
                },
                    el( 'p', { style: { margin: 0, color: '#666' } }, 
                        '📋 Category Filter - Configure in block settings →'
                    )
                )
            :
                display === 'select' ?
                    el( 'div', {
                        style: {
                            display: 'flex',
                            justifyContent: alignment === 'center' ? 'center' : alignment === 'right' ? 'flex-end' : 'flex-start'
                        }
                    },
                        el( 'select', { disabled: true, className: 'form-select', style: { maxWidth: '300px' } },
                            el( 'option', {}, showAllLabel || 'All' ),
                            terms && terms.map( ( t ) => 
                                el( 'option', { key: t.id }, t.name )
                            )
                        )
                    )
                :
                    el( 'div', { 
                        style: { 
                            display: 'flex', 
                            gap: '8px', 
                            flexWrap: 'wrap',
                            justifyContent: alignment === 'center' ? 'center' : alignment === 'right' ? 'flex-end' : 'flex-start'
                        } 
                    },
                        el( 'button', { disabled: true, className: 'btn btn-outline-primary' }, showAllLabel || 'All' ),
                        terms && terms.map( ( t ) =>
                            el( 'button', { key: t.id, disabled: true, className: 'btn btn-outline-primary' }, t.name )
                        )
                    )
        );
    },

    save: function () {
        // Server-side rendered block
        return null;
    }
} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.apiFetch );
