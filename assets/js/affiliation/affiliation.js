( function( blocks, element, blockEditor, components, data ) {
    const { registerBlockType } = blocks;
    const { Fragment, createElement: el } = element;
    const { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } = blockEditor;
    const { PanelBody, Button, ToggleControl, RangeControl, TextControl } = components;

    registerBlockType( 'understrap/affiliation', {
        title: 'Affiliation Logos',
        icon: 'images-alt2',
        category: 'widgets',
        description: 'Display affiliation logos in a responsive swiper carousel',
        supports: {
            html: false,
            align: [ 'wide', 'full' ],
            spacing: {
                padding: true,
                margin: true,
            },
            color: {
                background: true,
            },
        },
        attributes: {
            images: { type: 'array', default: [] },
            slidesPerViewDesktop: { type: 'number', default: 4 },
            slidesPerViewMobile: { type: 'number', default: 1 },
            spaceBetween: { type: 'number', default: 30 },
            loop: { type: 'boolean', default: true },
            autoplay: { type: 'boolean', default: true },
            autoplayDelay: { type: 'number', default: 3000 },
        },

        edit: function( props ) {
            const { attributes, setAttributes } = props;
            const { 
                images, 
                slidesPerViewDesktop, 
                slidesPerViewMobile, 
                spaceBetween, 
                loop, 
                autoplay, 
                autoplayDelay 
            } = attributes;

            const blockProps = useBlockProps({
                className: 'affiliation-block-editor',
            });

            // Add new image
            const addImage = ( newImage ) => {
                const imageObj = {
                    id: newImage.id,
                    src: newImage.url,
                    alt: newImage.alt || '',
                    url: ''
                };
                setAttributes({ images: [ ...images, imageObj ] });
            };

            // Update image URL
            const updateImageUrl = ( index, url ) => {
                const updatedImages = [ ...images ];
                updatedImages[ index ].url = url;
                setAttributes({ images: updatedImages });
            };

            // Update image alt text
            const updateImageAlt = ( index, alt ) => {
                const updatedImages = [ ...images ];
                updatedImages[ index ].alt = alt;
                setAttributes({ images: updatedImages });
            };

            // Remove image
            const removeImage = ( index ) => {
                const updatedImages = images.filter( ( image, i ) => i !== index );
                setAttributes({ images: updatedImages });
            };

            // Move image up
            const moveImageUp = ( index ) => {
                if ( index === 0 ) return;
                const updatedImages = [ ...images ];
                [ updatedImages[ index - 1 ], updatedImages[ index ] ] = 
                [ updatedImages[ index ], updatedImages[ index - 1 ] ];
                setAttributes({ images: updatedImages });
            };

            // Move image down
            const moveImageDown = ( index ) => {
                if ( index === images.length - 1 ) return;
                const updatedImages = [ ...images ];
                [ updatedImages[ index ], updatedImages[ index + 1 ] ] = 
                [ updatedImages[ index + 1 ], updatedImages[ index ] ];
                setAttributes({ images: updatedImages });
            };

            return el( Fragment, {},
                el( InspectorControls, {},
                    el( PanelBody, { title: 'Swiper Settings', initialOpen: true },
                        el( RangeControl, {
                            label: 'Slides per view (Desktop)',
                            value: slidesPerViewDesktop,
                            onChange: ( value ) => setAttributes({ slidesPerViewDesktop: value }),
                            min: 1,
                            max: 8,
                            step: 1,
                        }),
                        el( RangeControl, {
                            label: 'Slides per view (Mobile)',
                            value: slidesPerViewMobile,
                            onChange: ( value ) => setAttributes({ slidesPerViewMobile: value }),
                            min: 1,
                            max: 3,
                            step: 1,
                        }),
                        el( RangeControl, {
                            label: 'Space between slides',
                            value: spaceBetween,
                            onChange: ( value ) => setAttributes({ spaceBetween: value }),
                            min: 0,
                            max: 100,
                            step: 5,
                        }),
                        el( ToggleControl, {
                            label: 'Loop slides',
                            checked: loop,
                            onChange: ( value ) => setAttributes({ loop: value }),
                        }),
                        el( ToggleControl, {
                            label: 'Autoplay',
                            checked: autoplay,
                            onChange: ( value ) => setAttributes({ autoplay: value }),
                        }),
                        autoplay && el( RangeControl, {
                            label: 'Autoplay delay (ms)',
                            value: autoplayDelay,
                            onChange: ( value ) => setAttributes({ autoplayDelay: value }),
                            min: 1000,
                            max: 10000,
                            step: 500,
                        })
                    )
                ),

                el( 'div', blockProps,
                    el( 'div', { className: 'affiliation-editor-header' },
                        el( 'h3', {}, 'Affiliation Logos' ),
                        el( MediaUploadCheck, {},
                            el( MediaUpload, {
                                onSelect: addImage,
                                allowedTypes: [ 'image' ],
                                multiple: false,
                                render: ( { open } ) => el( Button, {
                                    onClick: open,
                                    className: 'button button-primary',
                                    style: { marginBottom: '20px' }
                                }, 'Add Image' )
                            })
                        )
                    ),

                    images.length === 0 && el( 'div', { 
                        className: 'affiliation-placeholder',
                        style: {
                            padding: '40px',
                            textAlign: 'center',
                            border: '2px dashed #ccc',
                            borderRadius: '8px',
                            color: '#666'
                        }
                    }, 'No affiliation images added yet. Click "Add Image" to get started.' ),

                    images.length > 0 && el( 'div', { className: 'affiliation-images-grid' },
                        images.map( ( image, index ) => 
                            el( 'div', { 
                                key: image.id,
                                className: 'affiliation-image-item',
                                style: {
                                    border: '1px solid #ddd',
                                    borderRadius: '8px',
                                    padding: '15px',
                                    marginBottom: '15px',
                                    backgroundColor: '#f9f9f9'
                                }
                            },
                                el( 'div', { 
                                    style: { 
                                        display: 'flex', 
                                        alignItems: 'flex-start',
                                        gap: '15px'
                                    }
                                },
                                    el( 'img', {
                                        src: image.src,
                                        alt: image.alt,
                                        style: {
                                            width: '243px',
                                            height: '243px',
                                            objectFit: 'contain',
                                            border: '1px solid #ddd',
                                            borderRadius: '4px'
                                        }
                                    }),
                                    el( 'div', { style: { flex: '1' } },
                                        el( TextControl, {
                                            label: 'Alt Text',
                                            value: image.alt,
                                            onChange: ( value ) => updateImageAlt( index, value ),
                                            placeholder: 'Enter alt text for accessibility'
                                        }),
                                        el( TextControl, {
                                            label: 'Link URL (optional)',
                                            value: image.url,
                                            onChange: ( value ) => updateImageUrl( index, value ),
                                            placeholder: 'https://example.com'
                                        })
                                    ),
                                    el( 'div', { 
                                        style: { 
                                            display: 'flex', 
                                            flexDirection: 'column',
                                            gap: '5px'
                                        }
                                    },
                                        el( Button, {
                                            onClick: () => moveImageUp( index ),
                                            disabled: index === 0,
                                            isSmall: true,
                                            icon: 'arrow-up-alt2'
                                        }),
                                        el( Button, {
                                            onClick: () => moveImageDown( index ),
                                            disabled: index === images.length - 1,
                                            isSmall: true,
                                            icon: 'arrow-down-alt2'
                                        }),
                                        el( Button, {
                                            onClick: () => removeImage( index ),
                                            isDestructive: true,
                                            isSmall: true,
                                            icon: 'trash'
                                        })
                                    )
                                )
                            )
                        )
                    ),

                    images.length > 0 && el( 'div', { 
                        className: 'affiliation-preview',
                        style: {
                            marginTop: '20px',
                            padding: '20px',
                            backgroundColor: '#fff',
                            border: '1px solid #ddd',
                            borderRadius: '8px'
                        }
                    },
                        el( 'h4', { style: { marginBottom: '15px' } }, 'Preview:' ),
                        el( 'div', { 
                            className: 'affiliation-preview-grid',
                            style: {
                                display: 'grid',
                                gridTemplateColumns: `repeat(${Math.min(slidesPerViewDesktop, images.length)}, 1fr)`,
                                gap: `${spaceBetween}px`,
                                alignItems: 'center'
                            }
                        },
                            images.slice( 0, slidesPerViewDesktop ).map( ( image, index ) =>
                                el( 'div', { key: index, className: 'preview-image' },
                                    el( 'img', {
                                        src: image.src,
                                        alt: image.alt,
                                        style: {
                                            width: '243px',
                                            height: '243px',
                                            objectFit: 'contain',
                                            border: '0px solid #eee',
                                            borderRadius: '0px',
                                            padding: '0px',
                                            backgroundColor: 'transparent'
                                        }
                                    })
                                )
                            )
                        ),
                        el( 'p', { 
                            style: { 
                                marginTop: '10px', 
                                fontSize: '12px', 
                                color: '#666' 
                            }
                        }, `Desktop: ${slidesPerViewDesktop} slides, Mobile: ${slidesPerViewMobile} slide${slidesPerViewMobile > 1 ? 's' : ''}` )
                    )
                )
            );
        },

        save: function() {
            return null; // Server-side rendering
        },
    });
}(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.data
));