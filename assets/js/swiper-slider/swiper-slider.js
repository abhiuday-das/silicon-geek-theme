(function (blocks, element, blockEditor, components, data, i18n) {
  const { registerBlockType } = blocks;
  const { Fragment, createElement: el, useState, useEffect } = element;
  const { InspectorControls, useBlockProps } = blockEditor;
  const {
    PanelBody,
    SelectControl,
    RangeControl,
    ToggleControl,
    TextControl,
    Button,
    CheckboxControl,
    Placeholder,
  } = components;
  const { useSelect } = data;
  const { __ } = i18n;

  registerBlockType("understrap/swiper-slider", {
    title: __("Swiper Slider", "understrap"),
    icon: "slides",
    category: "design",
    description: __(
      "Advanced slider with post query and static slides",
      "understrap",
    ),
    keywords: ["slider", "swiper", "carousel", "posts"],

    supports: {
      align: ["wide", "full"],
      html: false,
    },

    attributes: {
      slideSource: { type: "string", default: "posts" },
      postType: { type: "string", default: "post" },
      postsPerPage: { type: "number", default: 6 },
      orderBy: { type: "string", default: "date" },
      order: { type: "string", default: "DESC" },
      taxonomies: { type: "object", default: {} },
      staticSlides: { type: "array", default: [] },
      slidesPerView: { type: "number", default: 1 },
      spaceBetween: { type: "number", default: 30 },
      loop: { type: "boolean", default: true },
      autoplay: { type: "boolean", default: false },
      autoplayDelay: { type: "number", default: 3000 },
      speed: { type: "number", default: 300 },
      navigation: { type: "boolean", default: true },
      pagination: { type: "boolean", default: true },
      paginationType: { type: "string", default: "bullets" },
      scrollbar: { type: "boolean", default: false },
      effect: { type: "string", default: "slide" },
      direction: { type: "string", default: "horizontal" },
      centeredSlides: { type: "boolean", default: false },
      grabCursor: { type: "boolean", default: true },
      freeMode: { type: "boolean", default: false },
      breakpointMobile: { type: "number", default: 1 },
      breakpointTablet: { type: "number", default: 2 },
      breakpointDesktop: { type: "number", default: 3 },
    },

    edit: function (props) {
      const { attributes, setAttributes } = props;
      const {
        slideSource,
        postType,
        postsPerPage,
        orderBy,
        order,
        taxonomies,
        staticSlides,
        slidesPerView,
        spaceBetween,
        loop,
        autoplay,
        autoplayDelay,
        speed,
        navigation,
        pagination,
        paginationType,
        scrollbar,
        effect,
        direction,
        centeredSlides,
        grabCursor,
        freeMode,
        breakpointMobile,
        breakpointTablet,
        breakpointDesktop,
      } = attributes;

      const [postsData, setPostsData] = useState([]);
      const [isLoadingPosts, setIsLoadingPosts] = useState(false);
      const [taxonomyTerms, setTaxonomyTerms] = useState({});

      // Get post types with REST base
      const postTypes = useSelect(function (select) {
        const types = select("core").getPostTypes({ per_page: -1 });
        return types
          ? types.filter(function (type) {
              return type.viewable && type.rest_base;
            })
          : [];
      }, []);

      // Get the REST base for current post type
      const currentPostTypeObject = postTypes.find(function (type) {
        return type.slug === postType;
      });
      const restBase = currentPostTypeObject
        ? currentPostTypeObject.rest_base
        : postType;

      // Fetch posts when settings change
      useEffect(
        function () {
          if (slideSource !== "posts" || !restBase) {
            setPostsData([]);
            return;
          }

          setIsLoadingPosts(true);

          const query = {
            per_page: postsPerPage,
            order: order.toLowerCase(),
            orderby: orderBy,
            _embed: true,
          };

          Object.keys(taxonomies).forEach(function (tax) {
            if (taxonomies[tax] && taxonomies[tax].length > 0) {
              query[tax] = taxonomies[tax].join(",");
            }
          });

          const apiPath = "/wp/v2/" + restBase;
          console.log("Fetching from:", apiPath, "with query:", query);

          wp.apiFetch({
            path: wp.url.addQueryArgs(apiPath, query),
          })
            .then(function (posts) {
              console.log("Successfully fetched posts:", posts);
              setPostsData(posts);
              setIsLoadingPosts(false);
            })
            .catch(function (error) {
              console.error(
                "Error fetching posts from " + apiPath + ":",
                error,
              );
              setPostsData([]);
              setIsLoadingPosts(false);
            });
        },
        [
          slideSource,
          postType,
          restBase,
          postsPerPage,
          orderBy,
          order,
          taxonomies,
        ],
      );

      // Get taxonomies for selected post type
      const taxonomiesList = useSelect(
        function (select) {
          if (!postType) return [];
          const allTaxonomies = select("core").getTaxonomies({ per_page: -1 });
          return allTaxonomies
            ? allTaxonomies.filter(function (tax) {
                return (
                  tax.types.includes(postType) &&
                  tax.visibility &&
                  tax.visibility.publicly_queryable
                );
              })
            : [];
        },
        [postType],
      );

      // Load taxonomy terms
      useEffect(
        function () {
          if (taxonomiesList.length > 0) {
            taxonomiesList.forEach(function (taxonomy) {
              wp.apiFetch({
                path: "/wp/v2/" + taxonomy.rest_base + "?per_page=100",
              }).then(function (terms) {
                setTaxonomyTerms(function (prev) {
                  const newTerms = Object.assign({}, prev);
                  newTerms[taxonomy.slug] = terms;
                  return newTerms;
                });
              });
            });
          }
        },
        [taxonomiesList],
      );

      // Use postsData instead of the useSelect posts
      const posts = postsData;

      const addStaticSlide = function () {
        const newSlides = staticSlides.concat([
          { id: Date.now(), content: "" },
        ]);
        setAttributes({ staticSlides: newSlides });
      };

      const removeStaticSlide = function (index) {
        const newSlides = staticSlides.filter(function (_, i) {
          return i !== index;
        });
        setAttributes({ staticSlides: newSlides });
      };

      const updateStaticSlide = function (index, content) {
        const newSlides = staticSlides.slice();
        newSlides[index].content = content;
        setAttributes({ staticSlides: newSlides });
      };

      const blockProps = useBlockProps({
        className: "swiper-slider-block-editor",
      });

      // Add loading animation style
      useEffect(function () {
        const styleId = "swiper-slider-loading-animation";
        if (!document.getElementById(styleId)) {
          const style = document.createElement("style");
          style.id = styleId;
          style.textContent =
            "@keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(359deg); } }";
          document.head.appendChild(style);
        }
      }, []);

      return el(
        Fragment,
        {},
        el(
          InspectorControls,
          {},
          // Slide Source
          el(
            PanelBody,
            { title: __("Slide Source", "understrap"), initialOpen: true },
            el(SelectControl, {
              label: __("Source Type", "understrap"),
              value: slideSource,
              options: [
                { label: "Posts", value: "posts" },
                { label: "Static Slides", value: "static" },
              ],
              onChange: function (value) {
                setAttributes({ slideSource: value });
              },
            }),
          ),

          // Post Query Settings
          slideSource === "posts" &&
            el(
              PanelBody,
              { title: __("Post Query", "understrap"), initialOpen: true },
              el(SelectControl, {
                label: __("Post Type", "understrap"),
                value: postType,
                options: postTypes.map(function (type) {
                  return { label: type.name, value: type.slug };
                }),
                onChange: function (value) {
                  setAttributes({ postType: value, taxonomies: {} });
                },
              }),

              el(RangeControl, {
                label: __("Posts Per Page", "understrap"),
                value: postsPerPage,
                onChange: function (value) {
                  setAttributes({ postsPerPage: value });
                },
                min: 1,
                max: 50,
              }),

              el(SelectControl, {
                label: __("Order By", "understrap"),
                value: orderBy,
                options: [
                  { label: "Date", value: "date" },
                  { label: "Title", value: "title" },
                  { label: "Modified", value: "modified" },
                  { label: "Random", value: "rand" },
                  { label: "Menu Order", value: "menu_order" },
                ],
                onChange: function (value) {
                  setAttributes({ orderBy: value });
                },
              }),

              el(SelectControl, {
                label: __("Order", "understrap"),
                value: order,
                options: [
                  { label: "Descending", value: "DESC" },
                  { label: "Ascending", value: "ASC" },
                ],
                onChange: function (value) {
                  setAttributes({ order: value });
                },
              }),

              // Taxonomy Filters
              taxonomiesList.length > 0 &&
                el(
                  "div",
                  { style: { marginTop: "20px" } },
                  el(
                    "h3",
                    {
                      style: {
                        fontSize: "13px",
                        fontWeight: "600",
                        marginBottom: "10px",
                      },
                    },
                    __("Filter by Taxonomy", "understrap"),
                  ),
                  taxonomiesList.map(function (taxonomy) {
                    return el(
                      "div",
                      {
                        key: taxonomy.slug,
                        style: {
                          marginBottom: "15px",
                          padding: "10px",
                          background: "#f8f9fa",
                          borderRadius: "4px",
                        },
                      },
                      el(
                        "strong",
                        { style: { display: "block", marginBottom: "8px" } },
                        taxonomy.name,
                      ),
                      taxonomyTerms[taxonomy.slug] &&
                        taxonomyTerms[taxonomy.slug].map(function (term) {
                          return el(CheckboxControl, {
                            key: term.id,
                            label: term.name,
                            checked:
                              taxonomies[taxonomy.slug] &&
                              taxonomies[taxonomy.slug].includes(term.id),
                            onChange: function (checked) {
                              const currentTerms =
                                taxonomies[taxonomy.slug] || [];
                              const newTerms = checked
                                ? currentTerms.concat([term.id])
                                : currentTerms.filter(function (id) {
                                    return id !== term.id;
                                  });

                              const newTaxonomies = Object.assign(
                                {},
                                taxonomies,
                              );
                              newTaxonomies[taxonomy.slug] = newTerms;
                              setAttributes({ taxonomies: newTaxonomies });
                            },
                          });
                        }),
                    );
                  }),
                ),
            ),

          // Static Slides
          slideSource === "static" &&
            el(
              PanelBody,
              { title: __("Static Slides", "understrap"), initialOpen: true },
              el(
                "p",
                { style: { fontSize: "13px", color: "#757575" } },
                __("Add custom HTML slides", "understrap"),
              ),
              el(
                Button,
                {
                  isPrimary: true,
                  onClick: addStaticSlide,
                  style: { marginBottom: "15px" },
                },
                __("Add Slide", "understrap"),
              ),

              staticSlides.map(function (slide, index) {
                return el(
                  "div",
                  {
                    key: slide.id,
                    style: {
                      marginBottom: "15px",
                      padding: "10px",
                      border: "1px solid #ddd",
                      borderRadius: "4px",
                      background: "#fff",
                    },
                  },
                  el(
                    "h4",
                    { style: { margin: "0 0 10px 0", fontSize: "13px" } },
                    __("Slide", "understrap") + " " + (index + 1),
                  ),
                  el(TextControl, {
                    label: __("Content (HTML)", "understrap"),
                    value: slide.content,
                    onChange: function (value) {
                      updateStaticSlide(index, value);
                    },
                    help: __("Enter HTML content", "understrap"),
                  }),
                  el(
                    Button,
                    {
                      isDestructive: true,
                      isSmall: true,
                      onClick: function () {
                        removeStaticSlide(index);
                      },
                    },
                    __("Remove", "understrap"),
                  ),
                );
              }),
            ),

          // Swiper Settings
          el(
            PanelBody,
            { title: __("Swiper Settings", "understrap"), initialOpen: false },
            el(RangeControl, {
              label: __("Slides Per View", "understrap"),
              value: slidesPerView,
              onChange: function (value) {
                setAttributes({ slidesPerView: value });
              },
              min: 1,
              max: 10,
              step: 0.5,
            }),

            el(RangeControl, {
              label: __("Space Between (px)", "understrap"),
              value: spaceBetween,
              onChange: function (value) {
                setAttributes({ spaceBetween: value });
              },
              min: 0,
              max: 100,
            }),

            el(RangeControl, {
              label: __("Speed (ms)", "understrap"),
              value: speed,
              onChange: function (value) {
                setAttributes({ speed: value });
              },
              min: 100,
              max: 3000,
              step: 100,
            }),

            el(SelectControl, {
              label: __("Effect", "understrap"),
              value: effect,
              options: [
                { label: "Slide", value: "slide" },
                { label: "Fade", value: "fade" },
                { label: "Cube", value: "cube" },
                { label: "Coverflow", value: "coverflow" },
                { label: "Flip", value: "flip" },
              ],
              onChange: function (value) {
                setAttributes({ effect: value });
              },
            }),

            el(SelectControl, {
              label: __("Direction", "understrap"),
              value: direction,
              options: [
                { label: "Horizontal", value: "horizontal" },
                { label: "Vertical", value: "vertical" },
              ],
              onChange: function (value) {
                setAttributes({ direction: value });
              },
            }),

            el(ToggleControl, {
              label: __("Loop", "understrap"),
              checked: loop,
              onChange: function (value) {
                setAttributes({ loop: value });
              },
            }),

            el(ToggleControl, {
              label: __("Centered Slides", "understrap"),
              checked: centeredSlides,
              onChange: function (value) {
                setAttributes({ centeredSlides: value });
              },
            }),

            el(ToggleControl, {
              label: __("Grab Cursor", "understrap"),
              checked: grabCursor,
              onChange: function (value) {
                setAttributes({ grabCursor: value });
              },
            }),

            el(ToggleControl, {
              label: __("Free Mode", "understrap"),
              checked: freeMode,
              onChange: function (value) {
                setAttributes({ freeMode: value });
              },
            }),
          ),

          // Autoplay
          el(
            PanelBody,
            { title: __("Autoplay", "understrap"), initialOpen: false },
            el(ToggleControl, {
              label: __("Enable Autoplay", "understrap"),
              checked: autoplay,
              onChange: function (value) {
                setAttributes({ autoplay: value });
              },
            }),

            autoplay &&
              el(RangeControl, {
                label: __("Delay (ms)", "understrap"),
                value: autoplayDelay,
                onChange: function (value) {
                  setAttributes({ autoplayDelay: value });
                },
                min: 1000,
                max: 10000,
                step: 500,
              }),
          ),

          // Controls
          el(
            PanelBody,
            { title: __("Controls", "understrap"), initialOpen: false },
            el(ToggleControl, {
              label: __("Navigation Arrows", "understrap"),
              checked: navigation,
              onChange: function (value) {
                setAttributes({ navigation: value });
              },
            }),

            el(ToggleControl, {
              label: __("Pagination", "understrap"),
              checked: pagination,
              onChange: function (value) {
                setAttributes({ pagination: value });
              },
            }),

            pagination &&
              el(SelectControl, {
                label: __("Pagination Type", "understrap"),
                value: paginationType,
                options: [
                  { label: "Bullets", value: "bullets" },
                  { label: "Fraction", value: "fraction" },
                  { label: "Progressbar", value: "progressbar" },
                ],
                onChange: function (value) {
                  setAttributes({ paginationType: value });
                },
              }),

            el(ToggleControl, {
              label: __("Scrollbar", "understrap"),
              checked: scrollbar,
              onChange: function (value) {
                setAttributes({ scrollbar: value });
              },
            }),
          ),

          // Responsive Breakpoints
          el(
            PanelBody,
            {
              title: __("Responsive Breakpoints", "understrap"),
              initialOpen: false,
            },
            el(RangeControl, {
              label: __("Mobile (320px) - Slides", "understrap"),
              value: breakpointMobile,
              onChange: function (value) {
                setAttributes({ breakpointMobile: value });
              },
              min: 1,
              max: 5,
              step: 0.5,
            }),

            el(RangeControl, {
              label: __("Mobile (640px) - Slides", "understrap"),
              value: breakpointMobile,
              onChange: function (value) {
                setAttributes({ breakpointMobile: value });
              },
              min: 1,
              max: 5,
              step: 0.5,
            }),

            el(RangeControl, {
              label: __("Tablet (768px) - Slides", "understrap"),
              value: breakpointTablet,
              onChange: function (value) {
                setAttributes({ breakpointTablet: value });
              },
              min: 1,
              max: 6,
              step: 0.5,
            }),

            el(RangeControl, {
              label: __("Desktop (1024px+) - Slides", "understrap"),
              value: breakpointDesktop,
              onChange: function (value) {
                setAttributes({ breakpointDesktop: value });
              },
              min: 1,
              max: 10,
              step: 0.5,
            }),
          ),
        ),

        // Editor Preview - Slider-like layout
        el(
          "div",
          blockProps,
          el(
            "div",
            {
              style: {
                position: "relative",
                background: "#fff",
                border: "1px solid #ddd",
                borderRadius: "4px",
                overflow: "hidden",
                minHeight: "300px",
              },
            },
            // Preview slides
            el(
              "div",
              {
                style: {
                  display: "flex",
                  gap: spaceBetween + "px",
                  padding: "20px",
                  overflow: "hidden",
                },
              },
              slideSource === "posts"
                ? isLoadingPosts
                  ? el(
                      "div",
                      {
                        style: {
                          width: "100%",
                          textAlign: "center",
                          padding: "60px 20px",
                        },
                      },
                      el("div", {
                        className: "dashicons dashicons-update",
                        style: {
                          fontSize: "48px",
                          color: "#007bff",
                          marginBottom: "20px",
                          animation: "rotation 2s infinite linear",
                        },
                      }),
                      el("p", {}, __("Loading posts...", "understrap")),
                    )
                  : posts && posts.length > 0
                    ? posts
                        .slice(0, Math.min(slidesPerView, posts.length))
                        .map(function (post, index) {
                          const hasContent =
                            post.content && post.content.rendered;

                          return el(
                            "div",
                            {
                              key: post.id,
                              style: {
                                flex:
                                  "0 0 calc(" +
                                  100 / slidesPerView +
                                  "% - " +
                                  (spaceBetween * (slidesPerView - 1)) /
                                    slidesPerView +
                                  "px)",
                                background: "#fff",
                                border: "1px solid #e0e0e0",
                                borderRadius: "4px",
                                padding: "0",
                                minHeight: "250px",
                                overflow: "hidden",
                                display: "flex",
                                flexDirection: "column",
                              },
                            },
                            hasContent
                              ? el("div", {
                                  style: {
                                    width: "100%",
                                    height: "100%",
                                    overflow: "auto",
                                    maxHeight: "500px",
                                    padding: "20px",
                                  },
                                  dangerouslySetInnerHTML: {
                                    __html: post.content.rendered,
                                  },
                                })
                              : el(
                                  "div",
                                  {
                                    style: {
                                      padding: "20px",
                                      textAlign: "center",
                                      display: "flex",
                                      flexDirection: "column",
                                      alignItems: "center",
                                      justifyContent: "center",
                                      flex: 1,
                                    },
                                  },
                                  el("div", {
                                    className: "dashicons dashicons-admin-post",
                                    style: {
                                      fontSize: "48px",
                                      color: "#999",
                                      marginBottom: "15px",
                                    },
                                  }),
                                  el(
                                    "h4",
                                    {
                                      style: {
                                        margin: "0 0 10px 0",
                                        fontSize: "16px",
                                        fontWeight: "600",
                                        color: "#333",
                                      },
                                    },
                                    post.title && post.title.rendered
                                      ? post.title.rendered
                                      : "Post #" + post.id,
                                  ),
                                  el(
                                    "p",
                                    {
                                      style: {
                                        margin: "0",
                                        fontSize: "12px",
                                        color: "#666",
                                      },
                                    },
                                    __(
                                      "Full content will display on frontend",
                                      "understrap",
                                    ),
                                  ),
                                ),
                          );
                        })
                    : el(
                        "div",
                        {
                          style: {
                            width: "100%",
                            textAlign: "center",
                            padding: "60px 20px",
                          },
                        },
                        el("div", {
                          className: "dashicons dashicons-warning",
                          style: {
                            fontSize: "48px",
                            color: "#dc3545",
                            marginBottom: "20px",
                          },
                        }),
                        el(
                          "h3",
                          {
                            style: {
                              margin: "0 0 10px 0",
                              fontSize: "16px",
                              color: "#666",
                            },
                          },
                          __("No posts found", "understrap"),
                        ),
                        el(
                          "p",
                          {
                            style: {
                              margin: "0",
                              fontSize: "13px",
                              color: "#999",
                            },
                          },
                          __(
                            "Try adjusting your query settings or add some posts",
                            "understrap",
                          ),
                        ),
                      )
                : slideSource === "static" && staticSlides.length > 0
                  ? staticSlides
                      .slice(0, Math.min(slidesPerView, staticSlides.length))
                      .map(function (slide, index) {
                        return el(
                          "div",
                          {
                            key: slide.id,
                            style: {
                              flex:
                                "0 0 calc(" +
                                100 / slidesPerView +
                                "% - " +
                                (spaceBetween * (slidesPerView - 1)) /
                                  slidesPerView +
                                "px)",
                              background: "#f8f9fa",
                              border: "2px solid #e0e0e0",
                              borderRadius: "4px",
                              padding: "20px",
                              minHeight: "250px",
                              display: "flex",
                              flexDirection: "column",
                              justifyContent: "center",
                              alignItems: "center",
                              textAlign: "center",
                            },
                          },
                          el("div", {
                            className: "dashicons dashicons-slides",
                            style: {
                              fontSize: "48px",
                              color: "#999",
                              marginBottom: "15px",
                            },
                          }),
                          el(
                            "p",
                            {
                              style: {
                                margin: "0",
                                fontSize: "13px",
                                color: "#666",
                                wordBreak: "break-word",
                              },
                            },
                            slide.content ||
                              __("Static slide content", "understrap"),
                          ),
                        );
                      })
                  : el(
                      "div",
                      {
                        style: {
                          width: "100%",
                          textAlign: "center",
                          padding: "60px 20px",
                        },
                      },
                      el("div", {
                        className: "dashicons dashicons-slides",
                        style: {
                          fontSize: "64px",
                          color: "#ccc",
                          marginBottom: "20px",
                        },
                      }),
                      el(
                        "h3",
                        {
                          style: {
                            margin: "0 0 10px 0",
                            fontSize: "18px",
                            color: "#666",
                          },
                        },
                        __("No slides to preview", "understrap"),
                      ),
                      el(
                        "p",
                        {
                          style: {
                            margin: "0",
                            fontSize: "14px",
                            color: "#999",
                          },
                        },
                        slideSource === "posts"
                          ? __(
                              "Configure post query to load slides",
                              "understrap",
                            )
                          : __("Add static slides to preview", "understrap"),
                      ),
                    ),
            ),

            // Navigation arrows preview
            navigation &&
              el(
                "div",
                {
                  style: {
                    position: "absolute",
                    top: "50%",
                    left: "10px",
                    transform: "translateY(-50%)",
                    width: "44px",
                    height: "44px",
                    background: "rgba(255,255,255,0.9)",
                    borderRadius: "50%",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    boxShadow: "0 2px 8px rgba(0,0,0,0.15)",
                    cursor: "not-allowed",
                    opacity: "0.7",
                  },
                },
                el("span", {
                  className: "dashicons dashicons-arrow-left-alt2",
                  style: { fontSize: "20px", color: "#333" },
                }),
              ),

            navigation &&
              el(
                "div",
                {
                  style: {
                    position: "absolute",
                    top: "50%",
                    right: "10px",
                    transform: "translateY(-50%)",
                    width: "44px",
                    height: "44px",
                    background: "rgba(255,255,255,0.9)",
                    borderRadius: "50%",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    boxShadow: "0 2px 8px rgba(0,0,0,0.15)",
                    cursor: "not-allowed",
                    opacity: "0.7",
                  },
                },
                el("span", {
                  className: "dashicons dashicons-arrow-right-alt2",
                  style: { fontSize: "20px", color: "#333" },
                }),
              ),

            // Pagination preview
            pagination &&
              el(
                "div",
                {
                  style: {
                    position: "absolute",
                    bottom: "15px",
                    left: "50%",
                    transform: "translateX(-50%)",
                    display: "flex",
                    gap: "8px",
                    padding: "5px 10px",
                    background: "rgba(255,255,255,0.9)",
                    borderRadius: "20px",
                    boxShadow: "0 2px 8px rgba(0,0,0,0.1)",
                  },
                },
                paginationType === "bullets"
                  ? [1, 2, 3].map(function (dot, index) {
                      return el("span", {
                        key: index,
                        style: {
                          width: "10px",
                          height: "10px",
                          borderRadius: "50%",
                          background: index === 0 ? "#007bff" : "#ccc",
                          display: "inline-block",
                          transition: "all 0.3s",
                        },
                      });
                    })
                  : paginationType === "fraction"
                    ? el(
                        "span",
                        {
                          style: {
                            fontSize: "14px",
                            color: "#333",
                            fontWeight: "500",
                          },
                        },
                        "1 / " +
                          (slideSource === "posts"
                            ? posts.length
                            : staticSlides.length),
                      )
                    : el(
                        "div",
                        {
                          style: {
                            width: "100px",
                            height: "4px",
                            background: "#e0e0e0",
                            borderRadius: "2px",
                            overflow: "hidden",
                          },
                        },
                        el("div", {
                          style: {
                            width: "30%",
                            height: "100%",
                            background: "#007bff",
                          },
                        }),
                      ),
              ),

            // Settings info overlay
            el(
              "div",
              {
                style: {
                  position: "absolute",
                  top: "10px",
                  right: "10px",
                  background: "rgba(0,0,0,0.75)",
                  color: "#fff",
                  padding: "8px 12px",
                  borderRadius: "4px",
                  fontSize: "11px",
                  fontFamily: "monospace",
                },
              },
              el("div", {}, __("Effect: ", "understrap") + effect),
              el("div", {}, __("Slides: ", "understrap") + slidesPerView),
              autoplay &&
                el(
                  "div",
                  {},
                  __("Autoplay: ", "understrap") + autoplayDelay / 1000 + "s",
                ),
            ),
          ),
        ),
      );
    },

    save: function () {
      return null; // Dynamic block
    },
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.blockEditor,
  window.wp.components,
  window.wp.data,
  window.wp.i18n,
);
