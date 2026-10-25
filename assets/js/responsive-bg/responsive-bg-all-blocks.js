/* global wp */
(function() {
    if ( ! window.wp ) return;

    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { useEffect, Fragment, createElement: el } = wp.element;
    const { InspectorControls, MediaUpload, MediaUploadCheck } = wp.blockEditor;
    const { PanelBody, Button, ToggleControl, SelectControl, Notice } = wp.components;
    const { __ } = wp.i18n;

    // Add attributes
    function addResponsiveBgAttributes( settings, name ) {
        if ( name !== 'core/group' ) return settings;
        settings.attributes = {
            ...settings.attributes,
            enableResponsiveBg: { type: 'boolean', default: false },
            mobileBgId: { type: 'number', default: 0 },
            mobileBgUrl: { type: 'string', default: '' },
            desktopBgId: { type: 'number', default: 0 },
            desktopBgUrl: { type: 'string', default: '' },
            bgPosition: { type: 'string', default: 'center center' },
            bgSize: { type: 'string', default: 'cover' },
            bgRepeat: { type: 'string', default: 'no-repeat' },
            bgAttachment: { type: 'string', default: 'scroll' },
            bgOverlay: { type: 'boolean', default: false },
            bgOverlayColor: { type: 'string', default: '#000000' },
            bgOverlayOpacity: { type: 'number', default: 40 },
            responsiveBgKey: { type: 'string', default: '' }
        };
        return settings;
    }
    addFilter( 'blocks.registerBlockType', 'understrap-parent/responsive-bg-attrs', addResponsiveBgAttributes );

    // Function to inject editor styles
    function injectEditorStyles( key, mobileBg, desktopBg, bgPosition, bgSize, bgRepeat, bgAttachment, overlayEnabled, overlayColor, overlayOpacity ) {
        const existingStyle = document.getElementById( 'responsive-bg-editor-' + key );
        if ( existingStyle ) {
            existingStyle.remove();
        }

        if ( !mobileBg && !desktopBg ) return;

        const style = document.createElement( 'style' );
        style.id = 'responsive-bg-editor-' + key;
        
        const mobile_img = mobileBg ? `url('${mobileBg}')` : ( desktopBg ? `url('${desktopBg}')` : 'none' );
        const desktop_img = desktopBg ? `url('${desktopBg}')` : mobile_img;
        const overlayAlpha = overlayOpacity / 100;

        // Very specific selectors for the editor - try multiple approaches
        let css = `
        /* Primary selectors */
        .${key}.has-responsive-group-bg,
        .wp-block-group.${key},
        .block-editor-block-list__layout .wp-block-group.${key},
        .block-editor-block-list__layout .${key}.has-responsive-group-bg,
        
        /* Fallback selectors */
        .wp-block-group.has-responsive-group-bg.${key},
        [data-block] .wp-block-group.${key},
        .block-editor-block-list__block .wp-block-group.${key},
        .block-editor-inner-blocks .wp-block-group.${key} {
            background-image: ${mobile_img} !important;
            background-position: ${bgPosition} !important;
            background-size: ${bgSize} !important;
            background-repeat: ${bgRepeat} !important;
            background-attachment: ${bgAttachment} !important;
            position: relative !important;
            min-height: 200px !important;
        }`;

        if ( desktopBg && desktopBg !== mobileBg ) {
            css += `
            @media (min-width: 992px) {
                .${key}.has-responsive-group-bg,
                .wp-block-group.${key},
                .block-editor-block-list__layout .wp-block-group.${key},
                .block-editor-block-list__layout .${key}.has-responsive-group-bg,
                .wp-block-group.has-responsive-group-bg.${key},
                [data-block] .wp-block-group.${key},
                .block-editor-block-list__block .wp-block-group.${key},
                .block-editor-inner-blocks .wp-block-group.${key} {
                    background-image: ${desktop_img} !important;
                }
            }`;
        }

        if ( overlayEnabled ) {
            css += `
            .${key}.has-responsive-group-bg::before,
            .wp-block-group.${key}::before,
            .block-editor-block-list__layout .wp-block-group.${key}::before,
            .block-editor-block-list__layout .${key}.has-responsive-group-bg::before {
                content: "" !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                background: ${overlayColor} !important;
                opacity: ${overlayAlpha} !important;
                pointer-events: none !important;
                z-index: 1 !important;
            }
            .${key}.has-responsive-group-bg > *,
            .wp-block-group.${key} > *,
            .block-editor-block-list__layout .wp-block-group.${key} > *,
            .block-editor-block-list__layout .${key}.has-responsive-group-bg > * {
                position: relative !important;
                z-index: 2 !important;
            }`;
        }

        style.textContent = css;
        document.head.appendChild( style );
        
        // Log for debugging
        console.log( 'Injected editor styles for key:', key );
        console.log( 'Mobile background:', mobile_img );
        console.log( 'Desktop background:', desktop_img );
        console.log( 'CSS generated:', css );
        
        // Also try to apply inline styles as a fallback
        setTimeout(() => {
            const elements = document.querySelectorAll( `.${key}` );
            console.log( 'Found elements with key class:', elements.length );
            elements.forEach( el => {
                if ( el.classList.contains( 'wp-block-group' ) ) {
                    console.log( 'Applying inline styles to element:', el );
                    console.log( 'Element classes:', el.className );
                    el.style.setProperty( 'background-image', mobile_img, 'important' );
                    el.style.setProperty( 'background-position', bgPosition, 'important' );
                    el.style.setProperty( 'background-size', bgSize, 'important' );
                    el.style.setProperty( 'background-repeat', bgRepeat, 'important' );
                    el.style.setProperty( 'min-height', '200px', 'important' );
                    console.log( 'Applied inline background-image:', el.style.backgroundImage );
                }
            });
        }, 200);
    }

    const withResponsiveBgControls = createHigherOrderComponent( ( BlockEdit ) => ( props ) => {
        if ( props.name !== 'core/group' ) return el( BlockEdit, props );

        const {
            attributes: {
                enableResponsiveBg,
                mobileBgUrl,
                desktopBgUrl,
                mobileBgId,
                desktopBgId,
                bgPosition,
                bgSize,
                bgRepeat,
                bgAttachment,
                bgOverlay,
                bgOverlayColor,
                bgOverlayOpacity,
                responsiveBgKey,
                className
            },
            setAttributes,
            clientId
        } = props;

        // Generate key when enabled
        useEffect( () => {
            if ( enableResponsiveBg && ! responsiveBgKey ) {
                setAttributes( { responsiveBgKey: 'rbg-' + Math.random().toString( 36 ).slice( 2, 10 ) } );
            }
        }, [ enableResponsiveBg, responsiveBgKey ] );

        // Update className attribute to include responsive background classes
        useEffect( () => {
            if ( enableResponsiveBg && responsiveBgKey && ( mobileBgUrl || desktopBgUrl ) ) {
                const bgClasses = `has-responsive-group-bg ${responsiveBgKey}`;
                const currentClasses = className || '';
                
                // Check if classes don't already exist
                if ( currentClasses.indexOf( 'has-responsive-group-bg' ) === -1 && 
                     currentClasses.indexOf( responsiveBgKey ) === -1 ) {
                    const newClassName = currentClasses ? `${currentClasses} ${bgClasses}` : bgClasses;
                    setAttributes( { className: newClassName } );
                }

                // Inject editor styles
                setTimeout(() => {
                    injectEditorStyles( 
                        responsiveBgKey, 
                        mobileBgUrl, 
                        desktopBgUrl, 
                        bgPosition, 
                        bgSize, 
                        bgRepeat, 
                        bgAttachment, 
                        bgOverlay, 
                        bgOverlayColor, 
                        bgOverlayOpacity 
                    );
                }, 100);
                
            } else if ( ! enableResponsiveBg && responsiveBgKey ) {
                // Remove classes when disabled
                const currentClasses = className || '';
                const cleanedClassName = currentClasses
                    .replace( /has-responsive-group-bg/g, '' )
                    .replace( new RegExp( responsiveBgKey, 'g' ), '' )
                    .replace( /\s+/g, ' ' )
                    .trim();
                    
                setAttributes( { className: cleanedClassName || undefined } );
                
                // Remove styles
                const existingStyle = document.getElementById( 'responsive-bg-editor-' + responsiveBgKey );
                if ( existingStyle ) {
                    existingStyle.remove();
                }
            }
        }, [ enableResponsiveBg, responsiveBgKey, mobileBgUrl, desktopBgUrl, bgPosition, bgSize, bgRepeat, bgAttachment, bgOverlay, bgOverlayColor, bgOverlayOpacity ] );

        const posOptions = [
            { label: __( 'Center', 'understrap-parent' ), value: 'center center' },
            { label: __( 'Top Left', 'understrap-parent' ), value: 'top left' },
            { label: __( 'Top Center', 'understrap-parent' ), value: 'top center' },
            { label: __( 'Top Right', 'understrap-parent' ), value: 'top right' },
            { label: __( 'Center Left', 'understrap-parent' ), value: 'center left' },
            { label: __( 'Center Right', 'understrap-parent' ), value: 'center right' },
            { label: __( 'Bottom Left', 'understrap-parent' ), value: 'bottom left' },
            { label: __( 'Bottom Center', 'understrap-parent' ), value: 'bottom center' },
            { label: __( 'Bottom Right', 'understrap-parent' ), value: 'bottom right' }
        ];
        const sizeOptions = [
            { label: __( 'Cover', 'understrap-parent' ), value: 'cover' },
            { label: __( 'Contain', 'understrap-parent' ), value: 'contain' },
            { label: __( 'Auto', 'understrap-parent' ), value: 'auto' },
            { label: __( '100% 100%', 'understrap-parent' ), value: '100% 100%' }
        ];
        const repeatOptions = [
            { label: __( 'No Repeat', 'understrap-parent' ), value: 'no-repeat' },
            { label: __( 'Repeat', 'understrap-parent' ), value: 'repeat' },
            { label: __( 'Repeat X', 'understrap-parent' ), value: 'repeat-x' },
            { label: __( 'Repeat Y', 'understrap-parent' ), value: 'repeat-y' }
        ];
        const attachOptions = [
            { label: __( 'Scroll', 'understrap-parent' ), value: 'scroll' },
            { label: __( 'Fixed', 'understrap-parent' ), value: 'fixed' },
            { label: __( 'Local', 'understrap-parent' ), value: 'local' }
        ];

        function mediaPicker( label, id, url, onRemove, onSelect, changeLabel, selectLabel ) {
            return el(
                Fragment,
                null,
                el( 'h4', { style: { margin: '12px 0 6px' } }, label ),
                el(
                    MediaUploadCheck,
                    null,
                    el( MediaUpload, {
                        onSelect,
                        allowedTypes: [ 'image' ],
                        value: id,
                        render: ( { open } ) =>
                            el(
                                'div',
                                null,
                                el(
                                    Button,
                                    {
                                        variant: url ? 'secondary' : 'primary',
                                        onClick: open,
                                        style: { width: '100%', marginBottom: '6px' }
                                    },
                                    url ? changeLabel : selectLabel
                                ),
                                url &&
                                    el(
                                        'div',
                                        { style: { marginBottom: '10px' } },
                                        el( 'img', {
                                            src: url,
                                            alt: '',
                                            style: {
                                                width: '100%',
                                                height: 70,
                                                objectFit: 'cover',
                                                border: '1px solid #ddd',
                                                borderRadius: 4
                                            }
                                        } ),
                                        el(
                                            Button,
                                            {
                                                variant: 'link',
                                                isDestructive: true,
                                                onClick: onRemove,
                                                style: { marginTop: 4, padding: 0 }
                                            },
                                            __( 'Remove', 'understrap-parent' )
                                        )
                                    )
                            )
                    } )
                )
            );
        }

        return el(
            Fragment,
            null,
            el( BlockEdit, props ),
            el(
                InspectorControls,
                null,
                el(
                    PanelBody,
                    { title: __( 'Responsive Background', 'understrap-parent' ), initialOpen: false },
                    el( ToggleControl, {
                        label: __( 'Enable Responsive Background', 'understrap-parent' ),
                        checked: enableResponsiveBg,
                        onChange: ( val ) => {
                            setAttributes( { enableResponsiveBg: val } );
                            if ( ! val ) {
                                // Clean up attributes
                                setAttributes( {
                                    mobileBgId: 0,
                                    mobileBgUrl: '',
                                    desktopBgId: 0,
                                    desktopBgUrl: '',
                                    responsiveBgKey: ''
                                } );
                                
                                // Clean up className
                                const currentClasses = className || '';
                                const cleanedClassName = currentClasses
                                    .replace( /has-responsive-group-bg/g, '' )
                                    .replace( /rbg-[a-zA-Z0-9]+/g, '' )
                                    .replace( /\s+/g, ' ' )
                                    .trim();
                                    
                                setAttributes( { className: cleanedClassName || undefined } );
                                
                                // Remove editor styles
                                if ( responsiveBgKey ) {
                                    const existingStyle = document.getElementById( 'responsive-bg-editor-' + responsiveBgKey );
                                    if ( existingStyle ) {
                                        existingStyle.remove();
                                    }
                                }
                            }
                        }
                    } ),
                    enableResponsiveBg &&
                        el(
                            Fragment,
                            null,
                            el(
                                Notice,
                                { status: 'info', isDismissible: false, style: { marginBottom: '12px' } },
                                __( 'Mobile image used < 992px, desktop image ≥ 992px.', 'understrap-parent' )
                            ),
                            mediaPicker(
                                __( 'Mobile Background', 'understrap-parent' ),
                                mobileBgId,
                                mobileBgUrl,
                                () => setAttributes( { mobileBgId: 0, mobileBgUrl: '' } ),
                                ( media ) => setAttributes( { mobileBgId: media.id, mobileBgUrl: media.url } ),
                                __( 'Change Mobile Image', 'understrap-parent' ),
                                __( 'Select Mobile Image', 'understrap-parent' )
                            ),
                            mediaPicker(
                                __( 'Desktop Background', 'understrap-parent' ),
                                desktopBgId,
                                desktopBgUrl,
                                () => setAttributes( { desktopBgId: 0, desktopBgUrl: '' } ),
                                ( media ) => setAttributes( { desktopBgId: media.id, desktopBgUrl: media.url } ),
                                __( 'Change Desktop Image', 'understrap-parent' ),
                                __( 'Select Desktop Image', 'understrap-parent' )
                            ),
                            ( mobileBgUrl || desktopBgUrl ) &&
                                el(
                                    Fragment,
                                    null,
                                    el( SelectControl, {
                                        label: __( 'Background Size', 'understrap-parent' ),
                                        value: bgSize,
                                        options: sizeOptions,
                                        onChange: ( v ) => setAttributes( { bgSize: v } )
                                    } ),
                                    el( SelectControl, {
                                        label: __( 'Background Position', 'understrap-parent' ),
                                        value: bgPosition,
                                        options: posOptions,
                                        onChange: ( v ) => setAttributes( { bgPosition: v } )
                                    } ),
                                    el( SelectControl, {
                                        label: __( 'Background Repeat', 'understrap-parent' ),
                                        value: bgRepeat,
                                        options: repeatOptions,
                                        onChange: ( v ) => setAttributes( { bgRepeat: v } )
                                    } ),
                                    el( SelectControl, {
                                        label: __( 'Background Attachment', 'understrap-parent' ),
                                        value: bgAttachment,
                                        options: attachOptions,
                                        onChange: ( v ) => setAttributes( { bgAttachment: v } )
                                    } ),
                                    el( ToggleControl, {
                                        label: __( 'Overlay', 'understrap-parent' ),
                                        checked: bgOverlay,
                                        onChange: ( v ) => setAttributes( { bgOverlay: v } )
                                    } ),
                                    bgOverlay &&
                                        el(
                                            Fragment,
                                            null,
                                            el(
                                                'label',
                                                {
                                                    style: {
                                                        display: 'block',
                                                        fontSize: 11,
                                                        fontWeight: 500,
                                                        marginBottom: 4
                                                    }
                                                },
                                                __( 'Overlay Color', 'understrap-parent' )
                                            ),
                                            el( 'input', {
                                                type: 'color',
                                                value: bgOverlayColor,
                                                onChange: ( e ) => setAttributes( { bgOverlayColor: e.target.value } ),
                                                style: {
                                                    width: 50,
                                                    height: 32,
                                                    padding: 0,
                                                    border: '1px solid #ccc',
                                                    borderRadius: 4,
                                                    marginBottom: 10
                                                }
                                            } ),
                                            el(
                                                'label',
                                                {
                                                    style: {
                                                        display: 'block',
                                                        fontSize: 11,
                                                        fontWeight: 500
                                                    }
                                                },
                                                __( 'Overlay Opacity', 'understrap-parent' ) +
                                                    ' ' +
                                                    bgOverlayOpacity +
                                                    '%'
                                            ),
                                            el( 'input', {
                                                type: 'range',
                                                min: 0,
                                                max: 90,
                                                step: 5,
                                                value: bgOverlayOpacity,
                                                onChange: ( e ) =>
                                                    setAttributes( { bgOverlayOpacity: parseInt( e.target.value, 10 ) } ),
                                                style: { width: '100%' }
                                            } )
                                        )
                                )
                        )
                )
            )
        );
    }, 'withResponsiveBgControls' );

    addFilter( 'editor.BlockEdit', 'understrap-parent/responsive-bg-controls', withResponsiveBgControls );

    // No longer need addResponsiveBgSaveProps since we're using className attribute directly
})();