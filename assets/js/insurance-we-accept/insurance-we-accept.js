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

  // Define shared block configuration
  const blockConfig = {
    attributes: {
      items: { type: "array", default: [] },
      bgColor: { type: "string", default: "" },
      textColor: { type: "string", default: "#000000" },
      imageHeight: { type: "string", default: "150px" },
      imageBorderRadius: { type: "string", default: "8px" },
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
      } = attributes;

      const blockProps = useBlockProps({
        style: {
          backgroundColor: bgColor || "#f9f9f9",
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
              image1: "",
              image2: "",
              image3: "",
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

        /* SIDEBAR PANELS ------------------------- */
        el(
          InspectorControls,
          {},

          /* Feature Items Panel */
          el(
            PanelBody,
            { title: __("Insurance Columns", "understrap"), initialOpen: true },

            items.length === 0 &&
              el(
                "p",
                { style: { color: "#d63638", fontWeight: "bold" } },
                __(
                  "⚠️ No columns added yet. Click 'Add Insurance Column' below.",
                  "understrap",
                ),
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
                el("h4", {}, __(`Insurance Column ${index + 1}`, "understrap")),

                // Image 1
                el(
                  "div",
                  { style: { marginBottom: "15px" } },
                  el(
                    "label",
                    {
                      style: {
                        fontWeight: "bold",
                        display: "block",
                        marginBottom: "5px",
                      },
                    },
                    __("Image 1", "understrap"),
                  ),
                  el(MediaUpload, {
                    onSelect: (media) => updateItem(index, "image1", media.url),
                    allowedTypes: ["image"],
                    value: item.image1,
                    render: ({ open }) =>
                      item.image1
                        ? el(
                            "div",
                            {},
                            el("img", {
                              src: item.image1,
                              style: {
                                width: "100%",
                                height: "100px",
                                objectFit: "contain",
                                borderRadius: "8px",
                                marginBottom: "10px",
                                border: "1px solid #ddd",
                              },
                            }),
                            el(
                              Button,
                              {
                                onClick: open,
                                isSecondary: true,
                                style: { marginRight: "10px" },
                              },
                              __("Change", "understrap"),
                            ),
                            el(
                              Button,
                              {
                                onClick: () => updateItem(index, "image1", ""),
                                isDestructive: true,
                              },
                              __("Remove", "understrap"),
                            ),
                          )
                        : el(
                            Button,
                            { onClick: open, isPrimary: true },
                            __("Upload Image 1", "understrap"),
                          ),
                  }),
                ),

                // Image 2
                el(
                  "div",
                  { style: { marginBottom: "15px" } },
                  el(
                    "label",
                    {
                      style: {
                        fontWeight: "bold",
                        display: "block",
                        marginBottom: "5px",
                      },
                    },
                    __("Image 2", "understrap"),
                  ),
                  el(MediaUpload, {
                    onSelect: (media) => updateItem(index, "image2", media.url),
                    allowedTypes: ["image"],
                    value: item.image2,
                    render: ({ open }) =>
                      item.image2
                        ? el(
                            "div",
                            {},
                            el("img", {
                              src: item.image2,
                              style: {
                                width: "100%",
                                height: "100px",
                                objectFit: "contain",
                                borderRadius: "8px",
                                marginBottom: "10px",
                                border: "1px solid #ddd",
                              },
                            }),
                            el(
                              Button,
                              {
                                onClick: open,
                                isSecondary: true,
                                style: { marginRight: "10px" },
                              },
                              __("Change", "understrap"),
                            ),
                            el(
                              Button,
                              {
                                onClick: () => updateItem(index, "image2", ""),
                                isDestructive: true,
                              },
                              __("Remove", "understrap"),
                            ),
                          )
                        : el(
                            Button,
                            { onClick: open, isPrimary: true },
                            __("Upload Image 2", "understrap"),
                          ),
                  }),
                ),

                // Image 3
                el(
                  "div",
                  { style: { marginBottom: "15px" } },
                  el(
                    "label",
                    {
                      style: {
                        fontWeight: "bold",
                        display: "block",
                        marginBottom: "5px",
                      },
                    },
                    __("Image 3", "understrap"),
                  ),
                  el(MediaUpload, {
                    onSelect: (media) => updateItem(index, "image3", media.url),
                    allowedTypes: ["image"],
                    value: item.image3,
                    render: ({ open }) =>
                      item.image3
                        ? el(
                            "div",
                            {},
                            el("img", {
                              src: item.image3,
                              style: {
                                width: "100%",
                                height: "100px",
                                objectFit: "contain",
                                borderRadius: "8px",
                                marginBottom: "10px",
                                border: "1px solid #ddd",
                              },
                            }),
                            el(
                              Button,
                              {
                                onClick: open,
                                isSecondary: true,
                                style: { marginRight: "10px" },
                              },
                              __("Change", "understrap"),
                            ),
                            el(
                              Button,
                              {
                                onClick: () => updateItem(index, "image3", ""),
                                isDestructive: true,
                              },
                              __("Remove", "understrap"),
                            ),
                          )
                        : el(
                            Button,
                            { onClick: open, isPrimary: true },
                            __("Upload Image 3", "understrap"),
                          ),
                  }),
                ),

                el(
                  Button,
                  {
                    isDestructive: true,
                    onClick: () => removeItem(index),
                    style: { marginTop: "10px" },
                  },
                  __("Remove Column", "understrap"),
                ),
              ),
            ),

            el(
              Button,
              {
                isPrimary: true,
                onClick: addItem,
                style: { marginTop: "10px" },
              },
              __("+ Add Insurance Column", "understrap"),
            ),
          ),

          /* Styling Panel */
          el(
            PanelBody,
            { title: __("Styling", "understrap"), initialOpen: false },

            el("p", {}, __("Block Background Color")),
            el(ColorPalette, {
              value: bgColor,
              onChange: (v) => setAttributes({ bgColor: v || "" }),
            }),

            el("p", {}, __("Text Color")),
            el(ColorPalette, {
              value: textColor,
              onChange: (v) => setAttributes({ textColor: v || "#000000" }),
            }),

            el(TextControl, {
              label: __("Image Height", "understrap"),
              value: imageHeight,
              onChange: (v) => setAttributes({ imageHeight: v }),
              placeholder: "150px",
            }),

            el(TextControl, {
              label: __("Image Border Radius", "understrap"),
              value: imageBorderRadius,
              onChange: (v) => setAttributes({ imageBorderRadius: v }),
              placeholder: "8px",
            }),

            el(SelectControl, {
              label: __("Text Alignment", "understrap"),
              value: textAlign,
              options: [
                { label: "Left", value: "left" },
                { label: "Center", value: "center" },
                { label: "Right", value: "right" },
              ],
              onChange: (v) => setAttributes({ textAlign: v }),
            }),
          ),

          /* Slider Settings Panel */
          el(
            PanelBody,
            { title: __("Slider Settings", "understrap"), initialOpen: false },

            el(ToggleControl, {
              label: __("Enable Slider on Mobile/Tablet"),
              checked: sliderEnable,
              onChange: (v) => setAttributes({ sliderEnable: v }),
            }),

            el(RangeControl, {
              label: __("Desktop Columns"),
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
          ),
        ),

        /* BACKEND PREVIEW ----------------------------------- */
        el(
          "div",
          blockProps,

          items.length === 0
            ? el(
                "div",
                {
                  style: {
                    padding: "40px",
                    textAlign: "center",
                    background: "#f0f0f0",
                    border: "2px dashed #ccc",
                    borderRadius: "8px",
                  },
                },
                el("h3", {}, __("Insurance We Accept", "understrap")),
                el(
                  "p",
                  {},
                  __(
                    "No columns added yet. Add columns from the sidebar →",
                    "understrap",
                  ),
                ),
              )
            : el(
                "div",
                {
                  className: "backend-preview-wrapper",
                  style: {
                    display: "flex",
                    flexWrap: "wrap",
                    gap: `${spaceBetween}px`,
                  },
                },
                items.map((item, i) => {
                  return el(
                    "div",
                    {
                      key: i,
                      style: {
                        flex: `0 0 ${itemWidth}`,
                        minWidth: "200px",
                      },
                    },

                    el(
                      "div",
                      {
                        className: "feature-card-preview",
                        style: {
                          border: "1px solid #ddd",
                          padding: "15px",
                          borderRadius: "8px",
                        },
                      },

                      // Display all 3 images in column
                      el(
                        "div",
                        {
                          style: {
                            display: "flex",
                            flexDirection: "column",
                            gap: "15px",
                          },
                        },

                        // Image 1
                        item.image1
                          ? el("div", {
                              style: {
                                width: "100%",
                                height: imageHeight,
                                backgroundImage: `url(${item.image1})`,
                                backgroundSize: "contain",
                                backgroundRepeat: "no-repeat",
                                backgroundPosition: "center",
                                borderRadius: imageBorderRadius,
                                border: "1px solid #e0e0e0",
                              },
                            })
                          : el(
                              "div",
                              {
                                style: {
                                  width: "100%",
                                  height: imageHeight,
                                  background: "#f5f5f5",
                                  borderRadius: imageBorderRadius,
                                  display: "flex",
                                  alignItems: "center",
                                  justifyContent: "center",
                                  color: "#999",
                                  fontSize: "12px",
                                },
                              },
                              "Image 1",
                            ),

                        // Image 2
                        item.image2
                          ? el("div", {
                              style: {
                                width: "100%",
                                height: imageHeight,
                                backgroundImage: `url(${item.image2})`,
                                backgroundSize: "contain",
                                backgroundRepeat: "no-repeat",
                                backgroundPosition: "center",
                                borderRadius: imageBorderRadius,
                                border: "1px solid #e0e0e0",
                              },
                            })
                          : el(
                              "div",
                              {
                                style: {
                                  width: "100%",
                                  height: imageHeight,
                                  background: "#f5f5f5",
                                  borderRadius: imageBorderRadius,
                                  display: "flex",
                                  alignItems: "center",
                                  justifyContent: "center",
                                  color: "#999",
                                  fontSize: "12px",
                                },
                              },
                              "Image 2",
                            ),

                        // Image 3
                        item.image3
                          ? el("div", {
                              style: {
                                width: "100%",
                                height: imageHeight,
                                backgroundImage: `url(${item.image3})`,
                                backgroundSize: "contain",
                                backgroundRepeat: "no-repeat",
                                backgroundPosition: "center",
                                borderRadius: imageBorderRadius,
                                border: "1px solid #e0e0e0",
                              },
                            })
                          : el(
                              "div",
                              {
                                style: {
                                  width: "100%",
                                  height: imageHeight,
                                  background: "#f5f5f5",
                                  borderRadius: imageBorderRadius,
                                  display: "flex",
                                  alignItems: "center",
                                  justifyContent: "center",
                                  color: "#999",
                                  fontSize: "12px",
                                },
                              },
                              "Image 3",
                            ),
                      ),
                    ),
                  );
                }),
              ),
        ),
      );
    },

    save: () => null,
  };

  // Register the CURRENT block
  registerBlockType("understrap/insurance-we-accept", {
    title: __("Insurance We Accept", "understrap"),
    icon: "grid-view",
    category: "widgets",
    description: __(
      "Display insurance logos in columns with 3 images each",
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
