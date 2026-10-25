( function( blocks, element, blockEditor, components, data ) {
    const { registerBlockType } = blocks;
    const { Fragment, createElement: el } = element;
    const { InspectorControls, useBlockProps } = blockEditor;
    const { PanelBody, RangeControl, ToggleControl, SelectControl } = components;
    const { useSelect } = data;

    registerBlockType( 'understrap/recent-blog', {
        title: 'Recent Blog Posts',
        icon: 'admin-post',
        category: 'widgets',
        description: 'Display recent blog posts in a card layout with Keep Reading link',
        supports: {
            html: false,
            align: [ 'wide', 'full' ],
            spacing: { padding: true, margin: true },
            color: { background: true, text: true },
        },
        attributes: {
            postsToShow: { type: 'number', default: 3 },
            displayFeaturedImage: { type: 'boolean', default: true },
            displayDate: { type: 'boolean', default: true },
            displayAuthor: { type: 'boolean', default: true },
            displayAuthorAvatar: { type: 'boolean', default: true },
            displayCategory: { type: 'boolean', default: true },
            displayExcerpt: { type: 'boolean', default: false },
            excerptLength: { type: 'number', default: 55 },
            orderBy: { type: 'string', default: 'date' },
            order: { type: 'string', default: 'desc' },
            categories: { type: 'array', default: [] },
            layout: { type: 'string', default: 'grid' },
            columns: { type: 'number', default: 3 },
        },

        edit: function( props ) {
            const { attributes, setAttributes } = props;
            const {
                postsToShow,
                displayFeaturedImage,
                displayDate,
                displayAuthor,
                displayAuthorAvatar,
                displayCategory,
                displayExcerpt,
                excerptLength,
                orderBy,
                order,
                categories,
                layout,
                columns,
            } = attributes;

            // Fetch posts
            const posts = useSelect( ( select ) => {
                const query = {
                    per_page: postsToShow,
                    orderby: orderBy,
                    order: order,
                    status: 'publish',
                    _embed: true,
                };

                if ( categories && categories.length > 0 ) {
                    query.categories = categories;
                }

                return select( 'core' ).getEntityRecords( 'postType', 'post', query );
            }, [ postsToShow, orderBy, order, categories ] );

            // Fetch categories
            const categoriesList = useSelect( ( select ) => {
                return select( 'core' ).getEntityRecords( 'taxonomy', 'category', { per_page: -1 } );
            }, [] );

            const blockProps = useBlockProps({
                className: `recent-blog-block layout-${layout} columns-${columns}`,
            });

            // Helper functions
            const getCategoryName = ( post ) => {
                if ( !post._embedded?.['wp:term']?.[0] ) return 'Uncategorized';
                const cats = post._embedded['wp:term'][0];
                return cats.length > 0 ? cats[0].name : 'Uncategorized';
            };

            const getAuthorName = ( post ) => {
                if ( !post._embedded?.author?.[0] ) return 'Unknown Author';
                return post._embedded.author[0].name;
            };

            const getFeaturedImage = ( post ) => {
                const media = post._embedded?.['wp:featuredmedia']?.[0];
                return media ? ( media.media_details?.sizes?.medium?.source_url || media.source_url ) : null;
            };

            const formatDate = ( dateString ) => {
                const date = new Date( dateString );
                return date.toLocaleDateString( 'en-US', {
                    year: 'numeric', month: 'short', day: 'numeric'
                });
            };

            const getExcerpt = ( post ) => {
                if ( !post.excerpt?.rendered ) return '';
                const div = document.createElement( 'div' );
                div.innerHTML = post.excerpt.rendered;
                const text = div.textContent || div.innerText || '';
                const words = text.split( ' ' );
                return words.length > excerptLength ? words.slice( 0, excerptLength ).join( ' ' ) + '...' : text;
            };

            return el(
                Fragment,
                {},
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        { title: 'Blog Settings', initialOpen: true },
                        el( RangeControl, {
                            label: 'Number of posts',
                            value: postsToShow,
                            onChange: ( value ) => setAttributes( { postsToShow: value } ),
                            min: 1,
                            max: 12,
                        }),
                        el( SelectControl, {
                            label: 'Order by',
                            value: orderBy,
                            options: [
                                { label: 'Date', value: 'date' },
                                { label: 'Title', value: 'title' },
                                { label: 'Menu Order', value: 'menu_order' },
                                { label: 'Random', value: 'rand' },
                            ],
                            onChange: ( value ) => setAttributes( { orderBy: value } ),
                        }),
                        el( SelectControl, {
                            label: 'Order',
                            value: order,
                            options: [
                                { label: 'Descending', value: 'desc' },
                                { label: 'Ascending', value: 'asc' },
                            ],
                            onChange: ( value ) => setAttributes( { order: value } ),
                        }),
                    ),
                    el(
                        PanelBody,
                        { title: 'Layout Settings', initialOpen: false },
                        el( SelectControl, {
                            label: 'Layout',
                            value: layout,
                            options: [
                                { label: 'Grid', value: 'grid' },
                                { label: 'List', value: 'list' },
                            ],
                            onChange: ( value ) => setAttributes( { layout: value } ),
                        }),
                        layout === 'grid' && el( RangeControl, {
                            label: 'Columns',
                            value: columns,
                            onChange: ( value ) => setAttributes( { columns: value } ),
                            min: 1,
                            max: 4,
                        }),
                    ),
                    el(
                        PanelBody,
                        { title: 'Display Settings', initialOpen: false },
                        el( ToggleControl, {
                            label: 'Display featured image',
                            checked: displayFeaturedImage,
                            onChange: ( value ) => setAttributes( { displayFeaturedImage: value } ),
                        }),
                        el( ToggleControl, {
                            label: 'Display category',
                            checked: displayCategory,
                            onChange: ( value ) => setAttributes( { displayCategory: value } ),
                        }),
                        el( ToggleControl, {
                            label: 'Display date',
                            checked: displayDate,
                            onChange: ( value ) => setAttributes( { displayDate: value } ),
                        }),
                        el( ToggleControl, {
                            label: 'Display author',
                            checked: displayAuthor,
                            onChange: ( value ) => setAttributes( { displayAuthor: value } ),
                        }),
                        displayAuthor && el( ToggleControl, {
                            label: 'Display author avatar',
                            checked: displayAuthorAvatar,
                            onChange: ( value ) => setAttributes( { displayAuthorAvatar: value } ),
                        }),
                        el( ToggleControl, {
                            label: 'Display excerpt',
                            checked: displayExcerpt,
                            onChange: ( value ) => setAttributes( { displayExcerpt: value } ),
                        }),
                        displayExcerpt && el( RangeControl, {
                            label: 'Excerpt length (words)',
                            value: excerptLength,
                            onChange: ( value ) => setAttributes( { excerptLength: value } ),
                            min: 10,
                            max: 100,
                        }),
                    ),
                ),

                el(
                    'div',
                    blockProps,
                    !posts ? el( 'div', { className: 'loading' }, 'Loading posts...' ) :
                    posts.length === 0 ? el( 'div', { className: 'no-posts' }, 'No posts found.' ) :
                    el( 'div', { className: 'blog-posts-grid' },
                        posts.map( ( post ) => {
                            const featuredImage = getFeaturedImage( post );
                            const categoryName = getCategoryName( post );
                            const authorName = getAuthorName( post );

                            return el( 'article', { key: post.id, className: 'blog-post-card' },
                                displayFeaturedImage && featuredImage && el( 'div', { className: 'post-image' },
                                    el( 'img', {
                                        src: featuredImage,
                                        alt: post.title.rendered,
                                    })
                                ),
                                el( 'div', { className: 'post-content' },
                                    displayCategory && el( 'div', { className: 'post-category' }, categoryName ),
                                    el( 'h3', { className: 'post-title' },
                                        el( 'a', { href: post.link, dangerouslySetInnerHTML: { __html: post.title.rendered } })
                                    ),
                                    displayExcerpt && el( 'div', { className: 'post-excerpt' },
                                        el( 'p', {}, getExcerpt( post ) ),
                                        // 🆕 Keep Reading link
                                        el( 'div', { className: 'keep-reading' },
                                            el( 'a', { href: post.link, className: 'keep-reading-link' }, 'Keep Reading →' )
                                        )
                                    ),
                                    el( 'div', { className: 'post-meta' },
                                        displayAuthor && el( 'span', { className: 'post-author' },
                                            displayAuthorAvatar && el( 'img', {
                                                className: 'author-avatar',
                                                src: post._embedded?.author?.[0]?.avatar_urls['24'] || '',
                                                alt: authorName,
                                                style: { width: '24px', height: '24px', borderRadius: '50%', marginRight: '5px' }
                                            }),
                                            authorName
                                        ),
                                        displayDate && el( 'span', { className: 'post-date' },
                                            formatDate( post.date )
                                        )
                                    )
                                )
                            );
                        })
                    )
                )
            );
        },

        save: function() {
            return null; // Use dynamic render or frontend fetch if needed
        },
    });
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.data );
