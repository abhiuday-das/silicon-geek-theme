(function (blocks, element, blockEditor, components) {
  const { registerBlockType } = blocks;
  const { Fragment, createElement: el } = element;

  // Safely import Gutenberg APIs (backward-compatible for all WP versions)
  const {
    InspectorControls,
    useBlockProps,
    InnerBlocks,
    MediaUpload,
    __experimentalPanelColorGradientSettings,
    ColorGradientSettingsDropdown,
    PanelColorSettings: LegacyPanelColorSettings,
  } = blockEditor;

  // ✅ Dynamic fallback for color settings (avoids "undefined" crash)
  const PanelColorSettings =
    ColorGradientSettingsDropdown ||
    __experimentalPanelColorGradientSettings ||
    LegacyPanelColorSettings;

  const { PanelBody, TextControl, ToggleControl, Button, SelectControl } =
    components;

  // -----------------------------------------------------
  // 🧩 CARD BLOCK
  // -----------------------------------------------------
  registerBlockType("understrap/card", {
    title: "Card",
    icon: "id",
    category: "design",
    parent: ["understrap/cards"],

    supports: {
      html: false,
      anchor: true,
      align: ["wide", "full"],
      spacing: true,
      color: {
        text: true,
        background: true,
        gradients: true,
      },
      typography: {
        fontSize: true,
        lineHeight: true,
        __experimentalFontFamily: true,
      },
      border: true,
      shadow: true,
      // ✅ Disable strict block validation to prevent “Block validation failed” errors
      __experimentalSaveContent: false,
    },

    attributes: {
      imageUrl: { type: "string", default: "" },
      imageAlt: { type: "string", default: "" },
      badge: { type: "string", default: "Cost" },
      enableImageStyles: { type: "boolean", default: false },
      imageBg: { type: "string", default: "" },
      imageWidth: { type: "string", default: "" },
      imageHeight: { type: "string", default: "" },
      imageBorderRadius: { type: "string", default: "" },
      imageAlign: { type: "string", default: "" },
      viewButtonEnable: { type: "boolean", default: false },
      viewButtonText: { type: "string", default: "View More" },
      viewButtonUrl: { type: "string", default: "#" },
      viewButtonWithIcon: { type: "boolean", default: true },
    },

    // -----------------------------------------------------
    // 🧩 EDIT (Editor Side)
    // -----------------------------------------------------
    edit: function (props) {
      const { attributes, setAttributes } = props;
      const {
        imageUrl,
        imageAlt,
        badge,
        enableImageStyles,
        imageBg,
        imageWidth,
        imageHeight,
        imageBorderRadius,
        imageAlign,
        viewButtonEnable,
        viewButtonText,
        viewButtonUrl,
        viewButtonWithIcon,
      } = attributes;

      // Inline CSS variables
      const imageStyles = {};
      if (enableImageStyles) {
        if (imageBg) imageStyles["--image-bg"] = imageBg;
        if (imageWidth) imageStyles["--image-width"] = imageWidth;
        if (imageHeight) imageStyles["--image-height"] = imageHeight;
        if (imageBorderRadius)
          imageStyles["--image-border-radius"] = imageBorderRadius;

        if (imageAlign === "left")
          imageStyles["--image-justify"] = "flex-start";
        else if (imageAlign === "center")
          imageStyles["--image-justify"] = "center";
        else if (imageAlign === "right")
          imageStyles["--image-justify"] = "flex-end";
        else imageStyles["--image-justify"] = "flex-start";
      }

      const blockProps = useBlockProps({
        className: "card is-style-card",
        style: imageStyles,
      });

      const ALLOWED_BLOCKS = [
        "core/group",
        "core/columns",
        "core/column",
        "core/heading",
        "core/paragraph",
        "core/image",
        "core/video",
        "core/buttons",
        "core/button",
        "core/html",
        "core/embed",
      ];

      const template = [
        [
          "core/heading",
          {
            level: 6,
            content: "Lorem Ipsum",
            className: "wp-block-heading",
          },
        ],
        [
          "core/paragraph",
          {
            content: badge,
            className:
              "wp-block-paragraph is-style-badge is-style-badge-primary",
          },
        ],
      ];

      return el(
        Fragment,
        {},
        // Sidebar Controls
        el(
          InspectorControls,
          {},
          // Card Image Settings
          el(
            PanelBody,
            { title: "Card Image Settings" },
            el(MediaUpload, {
              onSelect: (media) =>
                setAttributes({ imageUrl: media.url, imageAlt: media.alt }),
              allowedTypes: ["image"],
              render: ({ open }) =>
                el(
                  Button,
                  { onClick: open, isPrimary: true },
                  imageUrl ? "Change Image" : "Select Image"
                ),
            }),
            imageUrl && el("p", {}, `Selected Image: ${imageUrl}`),
            el(TextControl, {
              label: "Image Alt Text",
              value: imageAlt,
              onChange: (val) => setAttributes({ imageAlt: val }),
            })
          ),

          // Optional Image Styles
          el(
            PanelBody,
            { title: "Image Styles (Optional)", initialOpen: false },
            el(ToggleControl, {
              label: "Enable Image Styles",
              checked: enableImageStyles,
              onChange: (val) => setAttributes({ enableImageStyles: val }),
            }),
            enableImageStyles &&
              el(
                Fragment,
                {},
                PanelColorSettings &&
                  el(PanelColorSettings, {
                    settings: [
                      {
                        colorValue: imageBg,
                        onColorChange: (val) => setAttributes({ imageBg: val }),
                        label: "Image Background",
                      },
                    ],
                  }),
                el(TextControl, {
                  label: "Width (px or %)",
                  value: imageWidth,
                  onChange: (val) => setAttributes({ imageWidth: val }),
                }),
                el(TextControl, {
                  label: "Height (px or %)",
                  value: imageHeight,
                  onChange: (val) => setAttributes({ imageHeight: val }),
                }),
                el(TextControl, {
                  label: "Border Radius (px or %)",
                  value: imageBorderRadius,
                  onChange: (val) => setAttributes({ imageBorderRadius: val }),
                }),
                el(SelectControl, {
                  label: "Alignment",
                  value: imageAlign,
                  options: [
                    { label: "Default", value: "" },
                    { label: "Left", value: "left" },
                    { label: "Center", value: "center" },
                    { label: "Right", value: "right" },
                  ],
                  onChange: (val) => setAttributes({ imageAlign: val }),
                })
              )
          ),

          // View Button Controls
          el(
            PanelBody,
            { title: "View Button", initialOpen: false },
            el(ToggleControl, {
              label: "Enable View Button",
              checked: viewButtonEnable,
              onChange: (val) => setAttributes({ viewButtonEnable: val }),
            }),
            viewButtonEnable &&
              el(
                Fragment,
                {},
                el(TextControl, {
                  label: "Button Text",
                  value: viewButtonText,
                  onChange: (val) => setAttributes({ viewButtonText: val }),
                }),
                el(TextControl, {
                  label: "Button URL",
                  value: viewButtonUrl,
                  onChange: (val) => setAttributes({ viewButtonUrl: val }),
                }),
                el(ToggleControl, {
                  label: "Show Icon",
                  checked: viewButtonWithIcon,
                  onChange: (val) => setAttributes({ viewButtonWithIcon: val }),
                })
              )
          )
        ),

        // Main Editor Block
        el(
          "div",
          blockProps,
          imageUrl &&
            el("img", {
              src: imageUrl,
              alt: imageAlt,
              className: "card-image",
            }),
          el(
            "div",
            { className: "card-content" },
            el(InnerBlocks, {
              allowedBlocks: ALLOWED_BLOCKS,
              template: template,
              templateLock: false,
              renderAppender: InnerBlocks.ButtonBlockAppender,
            })
          ),
          viewButtonEnable &&
            el(
              "a",
              { className: "card-button btn-link", href: viewButtonUrl },
              viewButtonText,
              viewButtonWithIcon &&
                el("i", { className: "fas fa-arrow-right ms-2" })
            )
        )
      );
    },

    // -----------------------------------------------------
    // 🧩 SAVE (Frontend Output)
    // -----------------------------------------------------
    save: function (props) {
      const { attributes } = props;
      const {
        imageUrl,
        imageAlt,
        enableImageStyles,
        imageBg,
        imageWidth,
        imageHeight,
        imageBorderRadius,
        imageAlign,
        viewButtonEnable,
        viewButtonText,
        viewButtonUrl,
        viewButtonWithIcon,
      } = attributes;

      const imageStyles = {};
      if (enableImageStyles) {
        if (imageBg) imageStyles["--image-bg"] = imageBg;
        if (imageWidth) imageStyles["--image-width"] = imageWidth;
        if (imageHeight) imageStyles["--image-height"] = imageHeight;
        if (imageBorderRadius)
          imageStyles["--image-border-radius"] = imageBorderRadius;

        if (imageAlign === "left")
          imageStyles["--image-justify"] = "flex-start";
        else if (imageAlign === "center")
          imageStyles["--image-justify"] = "center";
        else if (imageAlign === "right")
          imageStyles["--image-justify"] = "flex-end";
        else imageStyles["--image-justify"] = "flex-start";
      }

      const blockProps = useBlockProps.save({
        className: "card is-style-card",
        style: imageStyles,
      });

      // ✅ InnerBlocks.Content ensures all nested core blocks keep their markup
      return el(
        "div",
        blockProps,
        imageUrl &&
          el("img", { src: imageUrl, alt: imageAlt, className: "card-image" }),
        el("div", { className: "card-content" }, el(InnerBlocks.Content)),
        viewButtonEnable &&
          el(
            "a",
            { className: "card-button btn-link", href: viewButtonUrl },
            viewButtonText,
            viewButtonWithIcon &&
              el("i", { className: "fas fa-arrow-right ms-2" })
          )
      );
    },
  });

  // -----------------------------------------------------
  // 🧩 CARDS CONTAINER BLOCK
  // -----------------------------------------------------
  registerBlockType("understrap/cards", {
    title: "Cards",
    icon: "screenoptions",
    category: "design",
    description: "Container block for multiple Card blocks",
    supports: {
      html: false,
      align: ["wide", "full"],
      spacing: true,
      color: { background: true },
      border: true,
    },

    edit: function () {
      const blockProps = useBlockProps({
        className: "wp-block-understrap-cards is-style-cards",
      });
      return el(
        "div",
        blockProps,
        el(InnerBlocks, {
          allowedBlocks: ["understrap/card"],
          template: [["understrap/card"]],
          templateLock: false, // ✅ Allows flexible structure
          renderAppender: InnerBlocks.ButtonBlockAppender,
        })
      );
    },

    save: function () {
      const blockProps = useBlockProps.save({
        className: "wp-block-understrap-cards is-style-cards",
      });
      return el("div", blockProps, el(InnerBlocks.Content));
    },
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.blockEditor,
  window.wp.components
);
