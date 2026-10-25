( function( blocks, element, blockEditor, components ) {
	const { registerBlockType } = blocks;
	const { createElement: el, Fragment } = element;
	const { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } = blockEditor;
	const { PanelBody, Button, TextControl, RangeControl } = components;

	registerBlockType( 'understrap/image-position', {
		title: 'Image with Absolute Position',
		icon: 'format-image',
		category: 'media',
		attributes: {
			imageUrl: { type: 'string', default: '' },
			imageAlt: { type: 'string', default: '' },
			width: { type: 'string', default: '20rem' },
			height: { type: 'string', default: '20rem' },
			top: { type: 'string', default: '' },
			bottom: { type: 'string', default: '' },
			left: { type: 'string', default: '' },
			right: { type: 'string', default: '' },
			zIndex: { type: 'number', default: 1 },
		},

		edit: ( props ) => {
			const { attributes, setAttributes } = props;
			const { imageUrl, imageAlt, width, height, top, bottom, left, right, zIndex } = attributes;

			const blockProps = useBlockProps({
				className: 'image-absolute-block',
				style: {
					
					border: '0.1rem dashed #ccc',
					background: '#fafafa',
					overflow: 'hidden',
					position: 'absolute',
					display: 'inline-block',
					top: top || 'auto',
					bottom: bottom || 'auto',
					left: left || 'auto',
					right: right || 'auto',
					width,
					height
				}
			});

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Image Settings', initialOpen: true },
						// Image select
						el(MediaUploadCheck, {},
							el(MediaUpload, {
								onSelect: (media) => setAttributes({
									imageUrl: media.url,
									imageAlt: media.alt,
								}),
								allowedTypes: ['image'],
								render: ({ open }) =>
									el(Button, { onClick: open, variant: 'primary', style: { marginBottom: '10px' } },
										imageUrl ? 'Replace Image' : 'Select Image'
									)
							})
						),
						// Dimensions
						el(TextControl, { label: 'Width', value: width, onChange: (val) => setAttributes({ width: val }) }),
						el(TextControl, { label: 'Height', value: height, onChange: (val) => setAttributes({ height: val }) }),
						// Positioning
						el(TextControl, { label: 'Top', value: top, onChange: (val) => setAttributes({ top: val }) }),
						el(TextControl, { label: 'Bottom', value: bottom, onChange: (val) => setAttributes({ bottom: val }) }),
						el(TextControl, { label: 'Left', value: left, onChange: (val) => setAttributes({ left: val }) }),
						el(TextControl, { label: 'Right', value: right, onChange: (val) => setAttributes({ right: val }) }),
						// Z-index
						el(RangeControl, { label: 'Z-Index', value: zIndex, onChange: (val) => setAttributes({ zIndex: val }), min: -50, max: 50 }),
					)
				),
				imageUrl
					? el('img', {
						src: imageUrl,
						alt: imageAlt,
						style: {
							position: 'absolute',
							top: top || 'auto',
							bottom: bottom || 'auto',
							left: left || 'auto',
							right: right || 'auto',
							width,
							height,
							zIndex,
						}
					})
					: el('p', {}, 'Select an image in the sidebar.')
			);
		},

		save: ( props ) => {
			const { imageUrl, imageAlt, width, height, top, bottom, left, right, zIndex } = props.attributes;

			return imageUrl && el('img', {
				src: imageUrl,
				alt: imageAlt,
				style: {
					position: 'absolute',
					top: top || 'auto',
					bottom: bottom || 'auto',
					left: left || 'auto',
					right: right || 'auto',
					width,
					height,
					zIndex,
				}
			});
		}
	});
} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components
);
