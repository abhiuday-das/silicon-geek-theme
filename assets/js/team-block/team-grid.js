(function (blocks, element, blockEditor, components, i18n, data) {
  const { registerBlockType } = blocks;
  const { __ } = i18n;
  const { Fragment, createElement: el } = element;
  const { InspectorControls, useBlockProps } = blockEditor;
  const { PanelBody, ColorPalette, Spinner } = components;
  const { useSelect } = data;

  registerBlockType("understrap/team-grid", {
    title: __("Team Grid", "understrap"),
    icon: "groups",
    category: "widgets",
    description: __(
      "Display all team members in a responsive grid with modals",
      "understrap",
    ),

    attributes: {
      bgColor: { type: "string", default: "" },
      textColor: { type: "string", default: "#000000" },
    },

    edit: ({ attributes, setAttributes }) => {
      const { bgColor, textColor } = attributes;

      // Fetch team members from WordPress
      const { teamMembers, isResolving } = useSelect((select) => {
        const { getEntityRecords, isResolving: checkResolving } =
          select("core");

        const query = {
          per_page: 100,
          status: "publish",
          _embed: true,
        };

        return {
          teamMembers: getEntityRecords("postType", "team", query),
          isResolving: checkResolving("core", "getEntityRecords", [
            "postType",
            "team",
            query,
          ]),
        };
      }, []);

      const blockProps = useBlockProps({
        style: {
          backgroundColor: bgColor || "#f8f9fa",
          color: textColor,
          padding: "40px 20px",
          minHeight: "300px",
        },
      });

      // Loading state
      if (isResolving) {
        return el(
          Fragment,
          {},
          el(
            InspectorControls,
            {},
            el(
              PanelBody,
              { title: __("Styling", "understrap"), initialOpen: true },
              el(
                "p",
                { style: { fontWeight: "bold" } },
                __("Background Color", "understrap"),
              ),
              el(ColorPalette, {
                value: bgColor,
                onChange: (v) => setAttributes({ bgColor: v || "" }),
              }),
              el(
                "p",
                { style: { fontWeight: "bold", marginTop: "20px" } },
                __("Text Color", "understrap"),
              ),
              el(ColorPalette, {
                value: textColor,
                onChange: (v) => setAttributes({ textColor: v || "#000000" }),
              }),
            ),
          ),
          el(
            "div",
            blockProps,
            el(
              "div",
              { style: { textAlign: "center", padding: "40px" } },
              el(Spinner),
              el(
                "p",
                { style: { marginTop: "10px" } },
                __("Loading team members...", "understrap"),
              ),
            ),
          ),
        );
      }

      // Debug: Log what we received
      console.log("Team Members:", teamMembers);

      // No team members found
      if (!teamMembers || teamMembers.length === 0) {
        return el(
          Fragment,
          {},
          el(
            InspectorControls,
            {},
            el(
              PanelBody,
              { title: __("Styling", "understrap"), initialOpen: true },
              el(
                "p",
                { style: { fontWeight: "bold" } },
                __("Background Color", "understrap"),
              ),
              el(ColorPalette, {
                value: bgColor,
                onChange: (v) => setAttributes({ bgColor: v || "" }),
              }),
              el(
                "p",
                { style: { fontWeight: "bold", marginTop: "20px" } },
                __("Text Color", "understrap"),
              ),
              el(ColorPalette, {
                value: textColor,
                onChange: (v) => setAttributes({ textColor: v || "#000000" }),
              }),
            ),
          ),
          el(
            "div",
            blockProps,
            el(
              "div",
              { style: { textAlign: "center" } },
              el(
                "div",
                { style: { fontSize: "48px", marginBottom: "20px" } },
                "👥",
              ),
              el(
                "h3",
                { style: { color: "#0066a1", marginBottom: "10px" } },
                __("Team Grid Block", "understrap"),
              ),
              el(
                "p",
                {
                  style: {
                    padding: "20px",
                    background: "#fff",
                    borderRadius: "6px",
                    border: "2px dashed #f0ad4e",
                  },
                },
                __("⚠️ No team members found.", "understrap"),
                el("br"),
                el("br"),
                __("📝 To add team members:", "understrap"),
                el("br"),
                __("1. Go to Team Members → Add New", "understrap"),
                el("br"),
                __("2. Set the member's name as the title", "understrap"),
                el("br"),
                __("3. Upload a featured image", "understrap"),
                el("br"),
                __(
                  '4. Add the "Team Member Details" block and fill in the information',
                  "understrap",
                ),
              ),
            ),
          ),
        );
      }

      // Render team members preview
      return el(
        Fragment,
        {},

        // Sidebar Settings
        el(
          InspectorControls,
          {},
          el(
            PanelBody,
            { title: __("Styling", "understrap"), initialOpen: true },

            el(
              "p",
              { style: { fontWeight: "bold" } },
              __("Background Color", "understrap"),
            ),
            el(ColorPalette, {
              value: bgColor,
              onChange: (v) => setAttributes({ bgColor: v || "" }),
            }),

            el(
              "p",
              { style: { fontWeight: "bold", marginTop: "20px" } },
              __("Text Color", "understrap"),
            ),
            el(ColorPalette, {
              value: textColor,
              onChange: (v) => setAttributes({ textColor: v || "#000000" }),
            }),
          ),
        ),

        // Block Editor Preview with actual team members
        el(
          "div",
          blockProps,

          el(
            "div",
            { style: { maxWidth: "1200px", margin: "0 auto" } },

            // Header
            el(
              "div",
              {
                style: {
                  textAlign: "center",
                  marginBottom: "30px",
                  padding: "15px",
                  background: "rgba(0, 102, 161, 0.1)",
                  borderRadius: "8px",
                  border: "2px dashed #0066a1",
                },
              },
              el(
                "h3",
                { style: { color: "#0066a1", marginBottom: "5px" } },
                __("Team Grid Preview", "understrap"),
              ),
              el(
                "p",
                { style: { fontSize: "13px", color: "#666", margin: "0" } },
                `${teamMembers.length} team member${teamMembers.length !== 1 ? "s" : ""} found`,
              ),
            ),

            // Team Grid
            el(
              "div",
              {
                style: {
                  display: "grid",
                  gridTemplateColumns: "repeat(auto-fit, minmax(300px, 1fr))",
                  gap: "20px",
                },
              },

              teamMembers.map((member) => {
                const featuredImage =
                  member._embedded?.["wp:featuredmedia"]?.[0]?.source_url;
                const name = member.title?.rendered || "Untitled";

                // Parse blocks to get team details
                let jobTitle = "DDS";
                let shortBio = "";

                if (member.content && member.content.raw) {
                  try {
                    const blocks = wp.blocks.parse(member.content.raw);
                    const detailsBlock = blocks.find(
                      (block) => block.name === "understrap/team-details",
                    );
                    if (detailsBlock && detailsBlock.attributes) {
                      jobTitle = detailsBlock.attributes.jobTitle || "DDS";
                      shortBio = detailsBlock.attributes.shortBio || "";
                    }
                  } catch (e) {
                    console.error("Error parsing blocks:", e);
                  }
                }

                return el(
                  "div",
                  {
                    key: member.id,
                    style: {
                      background: "#fff",
                      borderRadius: "8px",
                      overflow: "hidden",
                      border: "2px solid #e0e0e0",
                      boxShadow: "0 2px 8px rgba(0,0,0,0.1)",
                    },
                  },

                  // Image
                  el(
                    "div",
                    {
                      style: {
                        height: "200px",
                        backgroundImage: featuredImage
                          ? `url(${featuredImage})`
                          : "none",
                        backgroundSize: "cover",
                        backgroundPosition: "center",
                        backgroundColor: featuredImage ? "transparent" : "#ccc",
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                      },
                    },
                    !featuredImage &&
                      el(
                        "span",
                        { style: { fontSize: "48px", opacity: "0.5" } },
                        "👤",
                      ),
                  ),

                  // Content
                  el(
                    "div",
                    { style: { padding: "20px" } },

                    el(
                      "h4",
                      {
                        style: {
                          color: "#0066a1",
                          margin: "0 0 5px 0",
                          fontSize: "20px",
                          fontWeight: "700",
                        },
                      },
                      name,
                    ),

                    el(
                      "p",
                      {
                        style: {
                          fontWeight: "bold",
                          margin: "0 0 10px 0",
                          fontSize: "16px",
                        },
                      },
                      jobTitle,
                    ),

                    shortBio &&
                      el("div", {
                        style: {
                          color: "#666",
                          fontSize: "14px",
                          lineHeight: "1.6",
                          maxHeight: "80px",
                          overflow: "hidden",
                          position: "relative",
                        },
                        dangerouslySetInnerHTML: { __html: shortBio },
                      }),

                    el(
                      "div",
                      {
                        style: {
                          marginTop: "15px",
                          paddingTop: "15px",
                          borderTop: "1px solid #e0e0e0",
                        },
                      },
                      el(
                        "span",
                        {
                          style: {
                            color: "#0066a1",
                            fontWeight: "bold",
                            fontSize: "12px",
                            textTransform: "uppercase",
                            letterSpacing: "0.5px",
                          },
                        },
                        "READ MORE →",
                      ),
                    ),
                  ),
                );
              }),
            ),

            // Footer note
            el(
              "div",
              {
                style: {
                  marginTop: "30px",
                  padding: "15px",
                  background: "#f0f0f0",
                  borderRadius: "6px",
                  textAlign: "center",
                  fontSize: "13px",
                  color: "#666",
                },
              },
              "💡 ",
              __(
                "This is a preview. The full interactive grid with modals will appear on the frontend.",
                "understrap",
              ),
            ),
          ),
        ),
      );
    },

    save: () => null, // Rendered server-side
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.blockEditor,
  window.wp.components,
  window.wp.i18n,
  window.wp.data,
);
