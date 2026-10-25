(function (blocks, element, blockEditor, components, i18n) {
  const { registerBlockType } = blocks;
  const { __ } = i18n;
  const { Fragment, createElement: el } = element;

  const { InspectorControls, useBlockProps, MediaUpload } = blockEditor;

  const {
    PanelBody,
    TextControl,
    Button,
    ColorPalette,
    ToggleControl,
    RangeControl,
    SelectControl,
  } = components;

  const blockConfig = {
    attributes: {
      items: { type: "array", default: [] },
      bgColor: { type: "string", default: "" },
      textColor: { type: "string", default: "#131313" },
      imageHeight: { type: "string", default: "400px" },
      imageBorderRadius: { type: "string", default: "0" },
      textAlign: { type: "string", default: "center" },

      sliderEnable: { type: "boolean", default: true },
      desktopSlidesPerView: { type: "number", default: 3 },
      tabletSlidesPerView: { type: "number", default: 2 },
      mobileSlidesPerView: { type: "number", default: 1.2 },
      spaceBetween: { type: "number", default: 30 },
      autoplay: { type: "boolean", default: false },
      autoplayDelay: { type: "number", default: 3000 },
      loop: { type: "boolean", default: false },
      pagination: { type: "boolean", default: true },
      navigation: { type: "boolean", default: true },
      centerItems: { type: "boolean", default: false },

      // Mobile-specific settings
      mobileImageHeight: { type: "string", default: "72px" },
      mobileImageWidth: { type: "string", default: "70px" },
      mobileImageBorderRadius: { type: "string", default: "6px" },

      // Desktop card settings
      desktopCardBorderRadius: { type: "string", default: "8px" },
      desktopCardBackground: { type: "string", default: "#f8f9fa" },

      // Mobile card settings
      mobileCardBorderRadius: { type: "string", default: "16px" },
      mobileCardBackground: { type: "string", default: "#f8f9fa" },
      mobileCardPadding: { type: "string", default: "12px" },
      mobileCardMarginBottom: { type: "string", default: "16px" },
    },

    edit: ({ attributes, setAttributes }) => {
      const {
        items,
        bgColor,
        textColor,
        imageHeight,
        imageBorderRadius,
        textAlign,
        sliderEnable,
        desktopSlidesPerView,
        tabletSlidesPerView,
        mobileSlidesPerView,
        spaceBetween,
        autoplay,
        autoplayDelay,
        loop,
        pagination,
        navigation,
        centerItems,
        mobileImageHeight,
        mobileImageWidth,
        mobileImageBorderRadius,
        desktopCardBorderRadius,
        desktopCardBackground,
        mobileCardBorderRadius,
        mobileCardBackground,
        mobileCardPadding,
        mobileCardMarginBottom,
      } = attributes;

      const blockProps = useBlockProps({
        style: {
          backgroundColor: bgColor || "#f8f9fa",
          color: textColor,
          padding: "40px 20px",
        },
      });

      const updateItem = (index, field, value) => {
        const newItems = [...items];
        newItems[index] = { ...newItems[index], [field]: value };
        setAttributes({ items: newItems });
      };

      const addItem = () => {
        setAttributes({
          items: [
            ...items,
            {
              image: "",
              mobileImage: "",
              title: "New Preventative",
              link: "",
              buttonText: "LEARN MORE",
            },
          ],
        });
      };

      const removeItem = (index) => {
        setAttributes({
          items: items.filter((_, i) => i !== index),
        });
      };

      const itemWidth =
        items.length && desktopSlidesPerView > 0
          ? `calc(${100 / desktopSlidesPerView}% - ${spaceBetween}px)`
          : "100%";

      return el(
        Fragment,
        {},

        /* SIDEBAR PANELS */
        el(
          InspectorControls,
          {},

          /* Preventative Items Panel */
          el(
            PanelBody,
            {
              title: __("Preventative Items", "understrap"),
              initialOpen: true,
            },

            items.length === 0 &&
              el(
                "p",
                { style: { color: "#d63638", fontWeight: "bold" } },
                __("⚠️ No items added yet.", "understrap"),
              ),

            items.map((item, index) =>
              el(
                "div",
                {
                  key: index,
                  style: {
                    marginBottom: "20px",
                    borderBottom: "1px solid #ddd",
                    paddingBottom: "15px",
                  },
                },

                el("h4", {}, `Preventative Item ${index + 1}`),

                el(MediaUpload, {
                  onSelect: (media) => updateItem(index, "image", media.url),
                  allowedTypes: ["image"],
                  value: item.image,
                  render: ({ open }) =>
                    item.image
                      ? el(
                          Fragment,
                          {},
                          el("img", {
                            src: item.image,
                            style: {
                              width: "100%",
                              height: "150px",
                              objectFit: "cover",
                              marginBottom: "8px",
                            },
                          }),
                          el(
                            Button,
                            { onClick: open, isSecondary: true },
                            __("Change Desktop Image", "understrap"),
                          ),
                        )
                      : el(
                          Button,
                          { onClick: open, isPrimary: true },
                          __("Upload Desktop Image", "understrap"),
                        ),
                }),

                /* Mobile Image Upload */
                el(
                  "div",
                  { style: { marginTop: "10px" } },
                  el(
                    "label",
                    {
                      style: {
                        display: "block",
                        fontWeight: "bold",
                        marginBottom: "5px",
                      },
                    },
                    __("Mobile Image (Optional)", "understrap"),
                  ),
                  el(MediaUpload, {
                    onSelect: (media) =>
                      updateItem(index, "mobileImage", media.url),
                    allowedTypes: ["image"],
                    value: item.mobileImage,
                    render: ({ open }) =>
                      item.mobileImage
                        ? el(
                            Fragment,
                            {},
                            el("img", {
                              src: item.mobileImage,
                              style: {
                                width: "100%",
                                height: "120px",
                                objectFit: "cover",
                                marginBottom: "8px",
                                borderRadius: "6px",
                              },
                            }),
                            el(
                              Button,
                              { onClick: open, isSecondary: true },
                              __("Change Mobile Image", "understrap"),
                            ),
                            el(
                              Button,
                              {
                                onClick: () =>
                                  updateItem(index, "mobileImage", ""),
                                isDestructive: true,
                                style: { marginLeft: "8px" },
                              },
                              __("Remove", "understrap"),
                            ),
                          )
                        : el(
                            Button,
                            { onClick: open, isSecondary: true },
                            __("Upload Mobile Image", "understrap"),
                          ),
                  }),
                ),

                el(TextControl, {
                  label: __("Title", "understrap"),
                  value: item.title || "",
                  onChange: (v) => updateItem(index, "title", v),
                }),

                el(TextControl, {
                  label: __("Link URL", "understrap"),
                  value: item.link || "",
                  onChange: (v) => updateItem(index, "link", v),
                }),

                el(TextControl, {
                  label: __("Button Text", "understrap"),
                  value: item.buttonText || "LEARN MORE",
                  onChange: (v) => updateItem(index, "buttonText", v),
                }),

                el(
                  Button,
                  {
                    isDestructive: true,
                    onClick: () => removeItem(index),
                  },
                  __("Remove Item", "understrap"),
                ),
              ),
            ),

            el(
              Button,
              { isPrimary: true, onClick: addItem },
              __("+ Add Preventative Item", "understrap"),
            ),
          ),

          /* Desktop Styling Panel */
          el(
            PanelBody,
            { title: __("Desktop Styling", "understrap"), initialOpen: false },

            el("p", {}, __("Block Background Color")),
            el(ColorPalette, {
              value: bgColor,
              onChange: (v) => setAttributes({ bgColor: v || "" }),
            }),

            el("p", {}, __("Text Color")),
            el(ColorPalette, {
              value: textColor,
              onChange: (v) => setAttributes({ textColor: v || "#131313" }),
            }),

            el(TextControl, {
              label: __("Desktop Image Height"),
              value: imageHeight,
              onChange: (v) => setAttributes({ imageHeight: v }),
            }),

            el(TextControl, {
              label: __("Desktop Image Border Radius"),
              value: imageBorderRadius,
              onChange: (v) => setAttributes({ imageBorderRadius: v }),
            }),

            el(TextControl, {
              label: __("Desktop Card Border Radius"),
              value: desktopCardBorderRadius,
              onChange: (v) => setAttributes({ desktopCardBorderRadius: v }),
            }),

            el("p", {}, __("Desktop Card Background Color")),
            el(ColorPalette, {
              value: desktopCardBackground,
              onChange: (v) =>
                setAttributes({ desktopCardBackground: v || "#f8f9fa" }),
            }),

            el(SelectControl, {
              label: __("Text Alignment"),
              value: textAlign,
              options: [
                { label: "Left", value: "left" },
                { label: "Center", value: "center" },
                { label: "Right", value: "right" },
              ],
              onChange: (v) => setAttributes({ textAlign: v }),
            }),
          ),

          /* Mobile Styling Panel */
          el(
            PanelBody,
            { title: __("Mobile Styling", "understrap"), initialOpen: false },

            el(TextControl, {
              label: __("Mobile Image Height"),
              value: mobileImageHeight,
              onChange: (v) => setAttributes({ mobileImageHeight: v }),
            }),

            el(TextControl, {
              label: __("Mobile Image Width"),
              value: mobileImageWidth,
              onChange: (v) => setAttributes({ mobileImageWidth: v }),
            }),

            el(TextControl, {
              label: __("Mobile Image Border Radius"),
              value: mobileImageBorderRadius,
              onChange: (v) => setAttributes({ mobileImageBorderRadius: v }),
            }),

            el(TextControl, {
              label: __("Mobile Card Border Radius"),
              value: mobileCardBorderRadius,
              onChange: (v) => setAttributes({ mobileCardBorderRadius: v }),
            }),

            el("p", {}, __("Mobile Card Background Color")),
            el(ColorPalette, {
              value: mobileCardBackground,
              onChange: (v) =>
                setAttributes({ mobileCardBackground: v || "#f8f9fa" }),
            }),

            el(TextControl, {
              label: __("Mobile Card Padding"),
              value: mobileCardPadding,
              onChange: (v) => setAttributes({ mobileCardPadding: v }),
            }),

            el(TextControl, {
              label: __("Mobile Card Margin Bottom"),
              value: mobileCardMarginBottom,
              onChange: (v) => setAttributes({ mobileCardMarginBottom: v }),
            }),
          ),

          /* Slider Settings Panel */
          el(
            PanelBody,
            { title: __("Slider Settings", "understrap"), initialOpen: false },

            el(ToggleControl, {
              label: __("Enable Slider on Desktop"),
              checked: sliderEnable,
              onChange: (v) => setAttributes({ sliderEnable: v }),
            }),

            el(RangeControl, {
              label: __("Desktop Slides Per View"),
              value: desktopSlidesPerView,
              onChange: (v) => setAttributes({ desktopSlidesPerView: v }),
              min: 1,
              max: 6,
            }),

            el(TextControl, {
              label: __("Tablet Slides Per View"),
              type: "number",
              step: "0.1",
              min: "1",
              max: "5",
              value: tabletSlidesPerView,
              onChange: (v) =>
                setAttributes({ tabletSlidesPerView: parseFloat(v) || 2 }),
            }),

            el(TextControl, {
              label: __("Mobile Slides Per View"),
              help: __("Supports decimals like 1.2, 1.5", "understrap"),
              type: "number",
              step: "0.1",
              min: "0.5",
              max: "5",
              value: mobileSlidesPerView,
              onChange: (v) =>
                setAttributes({ mobileSlidesPerView: parseFloat(v) || 1.2 }),
            }),

            el(RangeControl, {
              label: __("Space Between Items (px)"),
              value: spaceBetween,
              onChange: (v) => setAttributes({ spaceBetween: v }),
              min: 0,
              max: 60,
            }),

            el(ToggleControl, {
              label: __("Autoplay"),
              checked: autoplay,
              onChange: (v) => setAttributes({ autoplay: v }),
            }),

            autoplay &&
              el(RangeControl, {
                label: __("Autoplay Delay (ms)"),
                value: autoplayDelay,
                onChange: (v) => setAttributes({ autoplayDelay: v }),
                min: 500,
                max: 10000,
                step: 500,
              }),

            el(ToggleControl, {
              label: __("Loop"),
              checked: loop,
              onChange: (v) => setAttributes({ loop: v }),
            }),

            el(ToggleControl, {
              label: __("Show Pagination"),
              checked: pagination,
              onChange: (v) => setAttributes({ pagination: v }),
            }),

            el(ToggleControl, {
              label: __("Show Navigation Arrows"),
              checked: navigation,
              onChange: (v) => setAttributes({ navigation: v }),
            }),

            el(ToggleControl, {
              label: __("Center Items"),
              help: __("Add flexbox centering to slider wrapper", "understrap"),
              checked: centerItems,
              onChange: (v) => setAttributes({ centerItems: v }),
            }),
          ),
        ),

        /* PREVIEW */
        el(
          "div",
          blockProps,
          el(
            "div",
            {
              style: {
                display: "flex",
                flexWrap: "wrap",
                gap: `${spaceBetween}px`,
                justifyContent: centerItems ? "center" : "flex-start",
              },
            },
            items.map((item, i) =>
              el(
                "div",
                {
                  key: i,
                  style: {
                    flex: `0 0 ${itemWidth}`,
                    maxWidth: "380px",
                  },
                },
                item.link
                  ? el(
                      "a",
                      {
                        href: "#",
                        style: {
                          background: desktopCardBackground,
                          borderRadius: desktopCardBorderRadius,
                          overflow: "hidden",
                          boxShadow: "0 4px 12px rgba(0,0,0,.08)",
                          textAlign,
                          display: "block",
                          textDecoration: "none",
                          color: "inherit",
                        },
                      },
                      el("div", {
                        style: {
                          height: imageHeight,
                          backgroundImage: item.image
                            ? `url(${item.image})`
                            : "none",
                          backgroundColor: "#eee",
                          backgroundSize: "cover",
                          backgroundPosition: "center",
                          borderRadius: imageBorderRadius,
                        },
                      }),
                      el(
                        "h3",
                        { style: { padding: "20px" } },
                        item.title || "Untitled",
                      ),
                      el(
                        "span",
                        {
                          className: "our-preventative-button",
                          style: {
                            display: "inline-block",
                            padding: "10px 20px",
                            marginBottom: "20px",
                          },
                        },
                        item.buttonText || "LEARN MORE",
                      ),
                    )
                  : el(
                      "div",
                      {
                        style: {
                          background: desktopCardBackground,
                          borderRadius: desktopCardBorderRadius,
                          overflow: "hidden",
                          boxShadow: "0 4px 12px rgba(0,0,0,.08)",
                          textAlign,
                        },
                      },
                      el("div", {
                        style: {
                          height: imageHeight,
                          backgroundImage: item.image
                            ? `url(${item.image})`
                            : "none",
                          backgroundColor: "#eee",
                          backgroundSize: "cover",
                          backgroundPosition: "center",
                          borderRadius: imageBorderRadius,
                        },
                      }),
                      el(
                        "h3",
                        { style: { padding: "20px" } },
                        item.title || "Untitled",
                      ),
                    ),
              ),
            ),
          ),
        ),
      );
    },

    save: () => null,
  };

  registerBlockType("understrap/our-preventative", {
    title: __("Our Preventative Grid", "understrap"),
    icon: "grid-view",
    category: "widgets",
    description: __(
      "Display preventative care with images, titles, and learn more links",
      "understrap",
    ),
    ...blockConfig,
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.blockEditor,
  window.wp.components,
  window.wp.i18n,
);
