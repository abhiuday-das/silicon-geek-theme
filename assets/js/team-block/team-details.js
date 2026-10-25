(function (blocks, element, blockEditor, components, i18n, data) {
  const { registerBlockType } = blocks;
  const { __ } = i18n;
  const { Fragment, createElement: el } = element;
  const {
    InspectorControls,
    useBlockProps,
    RichText,
    MediaUpload,
    MediaUploadCheck,
  } = blockEditor;
  const { PanelBody, TextControl, TextareaControl, Button, RangeControl } =
    components;
  const { useSelect } = data;

  registerBlockType("understrap/team-details", {
    title: __("Team Member Details", "understrap"),
    icon: "id-alt",
    category: "widgets",
    description: __(
      "Add team member information (use inside Team CPT)",
      "understrap",
    ),

    attributes: {
      jobTitle: { type: "string", default: "DDS" },
      fullBio: { type: "string", default: "" },
      videoShortcode: { type: "string", default: "" },
      desktopImageId: { type: "number", default: 0 },
      desktopImageUrl: { type: "string", default: "" },
      mobileImageId: { type: "number", default: 0 },
      mobileImageUrl: { type: "string", default: "" },
      excerptLength: { type: "number", default: 50 },
    },

    edit: ({ attributes, setAttributes }) => {
      const {
        jobTitle,
        fullBio,
        videoShortcode,
        desktopImageId,
        desktopImageUrl,
        mobileImageId,
        mobileImageUrl,
        excerptLength,
      } = attributes;

      const desktopImage = useSelect(
        (select) => {
          return desktopImageId
            ? select("core").getMedia(desktopImageId)
            : null;
        },
        [desktopImageId],
      );

      const mobileImage = useSelect(
        (select) => {
          return mobileImageId ? select("core").getMedia(mobileImageId) : null;
        },
        [mobileImageId],
      );

      const blockProps = useBlockProps({
        style: {
          background: "#f5f5f5",
          padding: "30px",
          borderRadius: "8px",
          border: "2px dashed #0066a1",
        },
      });

      // Function to strip HTML and get plain text
      const stripHtml = (html) => {
        const tmp = document.createElement("div");
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || "";
      };

      // Function to generate excerpt
      const generateExcerpt = (text, length) => {
        const plainText = stripHtml(text);
        const words = plainText.trim().split(/\s+/);
        if (words.length <= length) {
          return plainText;
        }
        return words.slice(0, length).join(" ") + "...";
      };

      const currentExcerpt = generateExcerpt(fullBio, excerptLength);

      return el(
        Fragment,
        {},

        // Sidebar Settings
        el(
          InspectorControls,
          {},
          el(
            PanelBody,
            {
              title: __("Team Member Settings", "understrap"),
              initialOpen: true,
            },

            el(TextControl, {
              label: __("Job Title", "understrap"),
              value: jobTitle,
              onChange: (v) => setAttributes({ jobTitle: v }),
              help: __("e.g., DDS, Senior Dentist", "understrap"),
            }),

            el(RangeControl, {
              label: __("Excerpt Word Count", "understrap"),
              value: excerptLength,
              onChange: (v) => setAttributes({ excerptLength: v }),
              min: 10,
              max: 200,
              step: 5,
              help: __(
                "Number of words to show in the card preview",
                "understrap",
              ),
            }),

            el(TextareaControl, {
              label: __("Video Shortcode", "understrap"),
              value: videoShortcode,
              onChange: (v) => setAttributes({ videoShortcode: v }),
              help: __(
                'Paste your video shortcode here (e.g., [video_shortcode id="123"])',
                "understrap",
              ),
              rows: 3,
            }),
          ),

          el(
            PanelBody,
            {
              title: __("Desktop Image", "understrap"),
              initialOpen: true,
            },
            el(
              "div",
              { style: { marginBottom: "15px" } },
              el(
                "p",
                {
                  style: {
                    fontSize: "13px",
                    color: "#666",
                    marginBottom: "10px",
                  },
                },
                __(
                  "Upload a desktop image for the team grid card. This image will be displayed in the main team listing.",
                  "understrap",
                ),
              ),
              el(
                MediaUploadCheck,
                {},
                el(MediaUpload, {
                  onSelect: (media) => {
                    setAttributes({
                      desktopImageId: media.id,
                      desktopImageUrl: media.url,
                    });
                  },
                  allowedTypes: ["image"],
                  value: desktopImageId,
                  render: ({ open }) => {
                    return el(
                      "div",
                      {},
                      desktopImage &&
                        el(
                          "div",
                          { style: { marginBottom: "10px" } },
                          el("img", {
                            src: desktopImage.source_url,
                            alt: __("Desktop image preview", "understrap"),
                            style: {
                              width: "100%",
                              maxWidth: "200px",
                              height: "auto",
                              borderRadius: "4px",
                              border: "2px solid #0066a1",
                            },
                          }),
                        ),
                      el(
                        "div",
                        { style: { display: "flex", gap: "10px" } },
                        el(
                          Button,
                          {
                            onClick: open,
                            variant: desktopImageId ? "secondary" : "primary",
                          },
                          desktopImageId
                            ? __("Change Desktop Image", "understrap")
                            : __("Select Desktop Image", "understrap"),
                        ),
                        desktopImageId &&
                          el(
                            Button,
                            {
                              onClick: () =>
                                setAttributes({
                                  desktopImageId: 0,
                                  desktopImageUrl: "",
                                }),
                              variant: "tertiary",
                              isDestructive: true,
                            },
                            __("Remove", "understrap"),
                          ),
                      ),
                    );
                  },
                }),
              ),
            ),
          ),

          el(
            PanelBody,
            {
              title: __("Mobile Image", "understrap"),
              initialOpen: true,
            },
            el(
              "div",
              { style: { marginBottom: "15px" } },
              el(
                "p",
                {
                  style: {
                    fontSize: "13px",
                    color: "#666",
                    marginBottom: "10px",
                  },
                },
                __(
                  "Upload a mobile-optimized image for the modal popup. If not set, the desktop image will be used.",
                  "understrap",
                ),
              ),
              el(
                MediaUploadCheck,
                {},
                el(MediaUpload, {
                  onSelect: (media) => {
                    setAttributes({
                      mobileImageId: media.id,
                      mobileImageUrl: media.url,
                    });
                  },
                  allowedTypes: ["image"],
                  value: mobileImageId,
                  render: ({ open }) => {
                    return el(
                      "div",
                      {},
                      mobileImage &&
                        el(
                          "div",
                          { style: { marginBottom: "10px" } },
                          el("img", {
                            src: mobileImage.source_url,
                            alt: __("Mobile image preview", "understrap"),
                            style: {
                              width: "100%",
                              maxWidth: "200px",
                              height: "auto",
                              borderRadius: "4px",
                              border: "2px solid #0066a1",
                            },
                          }),
                        ),
                      el(
                        "div",
                        { style: { display: "flex", gap: "10px" } },
                        el(
                          Button,
                          {
                            onClick: open,
                            variant: mobileImageId ? "secondary" : "primary",
                          },
                          mobileImageId
                            ? __("Change Mobile Image", "understrap")
                            : __("Select Mobile Image", "understrap"),
                        ),
                        mobileImageId &&
                          el(
                            Button,
                            {
                              onClick: () =>
                                setAttributes({
                                  mobileImageId: 0,
                                  mobileImageUrl: "",
                                }),
                              variant: "tertiary",
                              isDestructive: true,
                            },
                            __("Remove", "understrap"),
                          ),
                      ),
                    );
                  },
                }),
              ),
            ),
          ),
        ),

        // Block Editor Preview
        el(
          "div",
          blockProps,

          el(
            "div",
            { style: { marginBottom: "15px" } },
            el(
              "span",
              {
                style: {
                  fontWeight: "bold",
                  color: "#0066a1",
                  fontSize: "18px",
                },
              },
              "👤 Team Member Details Block",
            ),
          ),

          el(
            "div",
            { style: { marginBottom: "20px" } },
            el("strong", {}, __("Job Title:", "understrap")),
            el(
              "p",
              { style: { margin: "5px 0", color: "#333" } },
              jobTitle || "DDS",
            ),
          ),

          el(
            "div",
            { style: { marginBottom: "20px" } },
            el("strong", {}, __("Excerpt Length:", "understrap")),
            el(
              "p",
              { style: { margin: "5px 0", color: "#333" } },
              excerptLength + " words",
            ),
          ),

          desktopImage &&
            el(
              "div",
              {
                style: {
                  marginBottom: "20px",
                  padding: "10px",
                  background: "#e8f4f8",
                  borderRadius: "4px",
                },
              },
              el("strong", {}, __("Desktop Image Set:", "understrap")),
              el("br"),
              el("img", {
                src: desktopImage.source_url,
                alt: __("Desktop image", "understrap"),
                style: {
                  width: "100px",
                  height: "auto",
                  marginTop: "8px",
                  borderRadius: "4px",
                  border: "1px solid #0066a1",
                },
              }),
            ),

          mobileImage &&
            el(
              "div",
              {
                style: {
                  marginBottom: "20px",
                  padding: "10px",
                  background: "#e8f4f8",
                  borderRadius: "4px",
                },
              },
              el("strong", {}, __("Mobile Image Set:", "understrap")),
              el("br"),
              el("img", {
                src: mobileImage.source_url,
                alt: __("Mobile image", "understrap"),
                style: {
                  width: "100px",
                  height: "auto",
                  marginTop: "8px",
                  borderRadius: "4px",
                  border: "1px solid #0066a1",
                },
              }),
            ),

          videoShortcode &&
            el(
              "div",
              {
                style: {
                  marginBottom: "20px",
                  padding: "10px",
                  background: "#e8f4f8",
                  borderRadius: "4px",
                },
              },
              el("strong", {}, __("Video Shortcode:", "understrap")),
              el(
                "code",
                {
                  style: {
                    display: "block",
                    marginTop: "5px",
                    fontSize: "12px",
                    wordBreak: "break-all",
                  },
                },
                videoShortcode,
              ),
            ),

          el(
            "div",
            { style: { marginBottom: "20px" } },
            el(
              "strong",
              {},
              __("Card Excerpt Preview (auto-generated):", "understrap"),
            ),
            el(
              "div",
              {
                style: {
                  background: "white",
                  padding: "12px",
                  borderRadius: "4px",
                  border: "1px solid #ddd",
                  marginTop: "8px",
                  color: "#666",
                  fontSize: "14px",
                  fontStyle: "italic",
                },
              },
              currentExcerpt || __("No content yet...", "understrap"),
            ),
          ),

          el(
            "div",
            { style: { marginBottom: "20px" } },
            el(
              "strong",
              {},
              __("Full Biography (Modal Content):", "understrap"),
            ),
            el(RichText, {
              tagName: "div",
              value: fullBio,
              onChange: (v) => setAttributes({ fullBio: v }),
              placeholder: __(
                "Enter full biography to display in popup modal...",
                "understrap",
              ),
              style: {
                background: "white",
                padding: "12px",
                borderRadius: "4px",
                minHeight: "120px",
                border: "1px solid #ddd",
                marginTop: "8px",
              },
            }),
          ),

          el(
            "p",
            {
              style: {
                marginTop: "20px",
                fontSize: "13px",
                color: "#666",
                fontStyle: "italic",
              },
            },
            "💡 ",
            __("Don't forget to:", "understrap"),
            el("br"),
            "1. Set the Desktop Image (used in team grid)",
            el("br"),
            "2. Set Mobile Image (used in modal popup)",
            el("br"),
            "3. Set the title as the team member's name",
          ),
        ),
      );
    },

    save: ({ attributes }) => {
      const {
        jobTitle,
        fullBio,
        videoShortcode,
        desktopImageId,
        desktopImageUrl,
        mobileImageId,
        mobileImageUrl,
        excerptLength,
      } = attributes;

      // Data is saved in block attributes, not in saved content
      return el(
        "div",
        { className: "team-member-data" },
        el("div", { className: "job-title" }, jobTitle),
        el("div", {
          className: "full-bio",
          dangerouslySetInnerHTML: { __html: fullBio },
        }),
        el("div", { className: "video-shortcode" }, videoShortcode),
        el("div", { className: "desktop-image-id" }, desktopImageId),
        el("div", { className: "desktop-image-url" }, desktopImageUrl),
        el("div", { className: "mobile-image-id" }, mobileImageId),
        el("div", { className: "mobile-image-url" }, mobileImageUrl),
        el("div", { className: "excerpt-length" }, excerptLength),
      );
    },
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.blockEditor,
  window.wp.components,
  window.wp.i18n,
  window.wp.data,
);
