(function (blocks, element, blockEditor, components, data, apiFetch) {
    const { registerBlockType } = blocks;
    const { Fragment, createElement: el, useState, useEffect } = element;
    const { InspectorControls, useBlockProps } = blockEditor;
    const { PanelBody, ToggleControl, RangeControl, CheckboxControl } = components;

    registerBlockType('understrap/filter-blog', {
        title: 'Filter Blog',
        icon: 'filter',
        category: 'widgets',
        description: 'Display blog posts with search, category filtering, and pagination',
        supports: {
            html: false,
            align: ['wide', 'full'],
            spacing: {
                padding: true,
                margin: true,
            },
            color: {
                background: true,
                text: true,
            },
        },

        attributes: {
            postsPerPage: { type: 'number', default: 8 },
            displayFeaturedImage: { type: 'boolean', default: true },
            displayDate: { type: 'boolean', default: true },
            displayAuthor: { type: 'boolean', default: true },
            displayCategory: { type: 'boolean', default: true },
            displayExcerpt: { type: 'boolean', default: true },
            excerptLength: { type: 'number', default: 150 },
            showFilterTabs: { type: 'boolean', default: true },
            selectedCategories: { type: 'array', default: [] },
            enableSearch: { type: 'boolean', default: true },
            enableCategoryDropdown: { type: 'boolean', default: true },
        },

        edit: function (props) {
            const { attributes, setAttributes } = props;
            const {
                postsPerPage,
                displayFeaturedImage,
                displayDate,
                displayAuthor,
                displayCategory,
                displayExcerpt,
                excerptLength,
                showFilterTabs,
                selectedCategories,
                enableSearch,
                enableCategoryDropdown,
            } = attributes;

            const [categories, setCategories] = useState([]);
            const [posts, setPosts] = useState([]);
            const [loading, setLoading] = useState(true);

            // Fetch categories
            useEffect(() => {
                apiFetch({ path: '/wp/v2/categories?per_page=100&hide_empty=true' })
                    .then((categoriesData) => setCategories(categoriesData))
                    .catch(() => setCategories([]));
            }, []);

            // Fetch posts for preview
            useEffect(() => {
                setLoading(true);
                const query = `per_page=${postsPerPage}&orderby=date&order=desc`;
                apiFetch({ path: `/wp/v2/posts?${query}` })
                    .then((postsData) => {
                        setPosts(postsData);
                        setLoading(false);
                    })
                    .catch(() => {
                        setPosts([]);
                        setLoading(false);
                    });
            }, [postsPerPage]);

            const blockProps = useBlockProps({
                className: 'wp-block-understrap-filter-blog-editor',
            });

            return el(
                Fragment,
                {},
                // 🔧 Sidebar Controls
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        { title: 'Display Settings', initialOpen: true },
                        el(RangeControl, {
                            label: 'Posts per page',
                            value: postsPerPage,
                            onChange: (value) => setAttributes({ postsPerPage: value }),
                            min: 1,
                            max: 20,
                        }),
                        el(ToggleControl, {
                            label: 'Show filter tabs',
                            checked: showFilterTabs,
                            onChange: (value) => setAttributes({ showFilterTabs: value }),
                        }),
                        el(ToggleControl, {
                            label: 'Enable Search Bar',
                            checked: enableSearch,
                            onChange: (value) => setAttributes({ enableSearch: value }),
                        }),
                        el(ToggleControl, {
                            label: 'Enable Category Dropdown',
                            checked: enableCategoryDropdown,
                            onChange: (value) => setAttributes({ enableCategoryDropdown: value }),
                        }),
                        el(ToggleControl, {
                            label: 'Display featured image',
                            checked: displayFeaturedImage,
                            onChange: (value) => setAttributes({ displayFeaturedImage: value }),
                        }),
                        el(ToggleControl, {
                            label: 'Display category',
                            checked: displayCategory,
                            onChange: (value) => setAttributes({ displayCategory: value }),
                        }),
                        el(ToggleControl, {
                            label: 'Display excerpt',
                            checked: displayExcerpt,
                            onChange: (value) => setAttributes({ displayExcerpt: value }),
                        }),
                        displayExcerpt &&
                            el(RangeControl, {
                                label: 'Excerpt length',
                                value: excerptLength,
                                onChange: (value) => setAttributes({ excerptLength: value }),
                                min: 50,
                                max: 300,
                            }),
                        el(ToggleControl, {
                            label: 'Display author',
                            checked: displayAuthor,
                            onChange: (value) => setAttributes({ displayAuthor: value }),
                        }),
                        el(ToggleControl, {
                            label: 'Display date',
                            checked: displayDate,
                            onChange: (value) => setAttributes({ displayDate: value }),
                        })
                    ),

                    categories.length > 0 &&
                        el(
                            PanelBody,
                            { title: 'Category Filter', initialOpen: false },
                            el(
                                'p',
                                { style: { marginBottom: '12px' } },
                                'Select categories to show in filter tabs (leave all unchecked to show all categories):'
                            ),
                            categories.map((category) =>
                                el(CheckboxControl, {
                                    key: category.id,
                                    label: category.name,
                                    checked: selectedCategories.includes(category.id),
                                    onChange: (checked) => {
                                        if (checked) {
                                            setAttributes({
                                                selectedCategories: [
                                                    ...selectedCategories,
                                                    category.id,
                                                ],
                                            });
                                        } else {
                                            setAttributes({
                                                selectedCategories: selectedCategories.filter(
                                                    (id) => id !== category.id
                                                ),
                                            });
                                        }
                                    },
                                })
                            )
                        )
                ),

                // 🧱 Block Preview Layout
                el(
                    'div',
                    blockProps,

                    // 🔍 Search + Dropdown UI
                    el(
                        'div',
                        { className: 'filter-blog-search-bar-preview' },
                        enableSearch &&
                            el(
                                'div',
                                { className: 'filter-blog-search-wrapper' },
                                el('input', {
                                    type: 'text',
                                    placeholder: 'Search...',
                                    className: 'filter-blog-search-input',
                                    style: {
                                        padding: '10px 14px',
                                        borderRadius: '6px',
                                        border: '1px solid #ccc',
                                        minWidth: '220px',
                                        marginRight: '12px',
                                    },
                                })
                            ),
                        enableCategoryDropdown &&
                            el(
                                'div',
                                { className: 'filter-blog-category-dropdown' },
                                el(
                                    'select',
                                    {
                                        style: {
                                            padding: '10px 14px',
                                            borderRadius: '6px',
                                            border: '1px solid #ccc',
                                            minWidth: '180px',
                                        },
                                    },
                                    el('option', { value: '' }, 'All Categories'),
                                    categories
                                        .filter(
                                            (cat) =>
                                                selectedCategories.length === 0 ||
                                                selectedCategories.includes(cat.id)
                                        )
                                        .map((category) =>
                                            el(
                                                'option',
                                                { key: category.id, value: category.id },
                                                category.name
                                            )
                                        )
                                )
                            )
                    ),

                    // 🏷 Filter Tabs Preview
                    showFilterTabs &&
                        el(
                            'div',
                            { className: 'filter-blog-tabs-preview' },
                            el(
                                'div',
                                { className: 'wp-block-buttons is-layout-flex' },
                                el(
                                    'div',
                                    { className: 'wp-block-button filter-blog-tab active' },
                                    el(
                                        'span',
                                        {
                                            className:
                                                'wp-block-button__link wp-element-button',
                                        },
                                        'All'
                                    )
                                ),
                                categories
                                    .filter(
                                        (cat) =>
                                            selectedCategories.length === 0 ||
                                            selectedCategories.includes(cat.id)
                                    )
                                    .slice(0, 4)
                                    .map((category) =>
                                        el(
                                            'div',
                                            {
                                                key: category.id,
                                                className: 'wp-block-button filter-blog-tab',
                                            },
                                            el(
                                                'span',
                                                {
                                                    className:
                                                        'wp-block-button__link wp-element-button',
                                                },
                                                category.name
                                            )
                                        )
                                    )
                            )
                        ),

                    // 📰 Posts Preview
                    el(
                        'div',
                        { className: 'filter-blog-posts-preview' },
                        loading
                            ? el(
                                  'div',
                                  { className: 'filter-blog-loading' },
                                  el('p', {}, 'Loading posts...')
                              )
                            : el(
                                  'div',
                                  { className: 'filter-blog-grid-preview' },
                                  posts.slice(0, postsPerPage).map((post) =>
                                      el(
                                          'article',
                                          {
                                              key: post.id,
                                              className: 'filter-blog-card-preview',
                                          },
                                          displayFeaturedImage &&
                                              post.featured_media &&
                                              el(
                                                  'div',
                                                  {
                                                      className:
                                                          'filter-blog-card-image-preview',
                                                  },
                                                  el(
                                                      'div',
                                                      {
                                                          style: {
                                                              backgroundColor: '#f0f0f0',
                                                              height: '200px',
                                                              display: 'flex',
                                                              alignItems: 'center',
                                                              justifyContent: 'center',
                                                              fontSize: '14px',
                                                              color: '#666',
                                                          },
                                                      },
                                                      'Featured Image'
                                                  )
                                              ),
                                          el(
                                              'div',
                                              { className: 'filter-blog-card-content-preview' },
                                              displayCategory &&
                                                  el(
                                                      'div',
                                                      {
                                                          className:
                                                              'filter-blog-card-category-preview',
                                                      },
                                                      el(
                                                          'span',
                                                          { className: 'category-badge' },
                                                          'Category'
                                                      )
                                                  ),
                                              el(
                                                  'h3',
                                                  { className: 'filter-blog-card-title-preview' },
                                                  el('span', {
                                                      dangerouslySetInnerHTML: {
                                                          __html: post.title.rendered,
                                                      },
                                                  })
                                              ),
                                              displayExcerpt &&
                                                  el(
                                                      'div',
                                                      {
                                                          className:
                                                              'filter-blog-card-excerpt-preview',
                                                      },
                                                      el('p', {
                                                          dangerouslySetInnerHTML: {
                                                              __html:
                                                                  post.excerpt.rendered
                                                                      .replace(
                                                                          /<[^>]*>/g,
                                                                          ''
                                                                      )
                                                                      .substring(
                                                                          0,
                                                                          excerptLength / 7
                                                                      ) + '...',
                                                          },
                                                      })
                                                  ),
                                              (displayAuthor || displayDate) &&
                                                  el(
                                                      'div',
                                                      {
                                                          className:
                                                              'filter-blog-card-meta-preview',
                                                      },
                                                      displayAuthor &&
                                                          el(
                                                              'span',
                                                              {
                                                                  className:
                                                                      'card-author-preview',
                                                              },
                                                              'Author'
                                                          ),
                                                      displayDate &&
                                                          el(
                                                              'span',
                                                              {
                                                                  className:
                                                                      'card-date-preview',
                                                              },
                                                              'Date'
                                                          )
                                                  )
                                          )
                                      )
                                  )
                              )
                    ),

                    // 🔢 Pagination Preview
                    el(
                        'div',
                        { className: 'filter-blog-pagination-preview' },
                        el(
                            'nav',
                            { className: 'wp-block-query-pagination is-layout-flex' },
                            el(
                                'span',
                                {
                                    className:
                                        'wp-block-query-pagination-numbers page-numbers current',
                                },
                                '1'
                            ),
                            el(
                                'span',
                                {
                                    className:
                                        'wp-block-query-pagination-numbers page-numbers',
                                },
                                '2'
                            ),
                            el(
                                'span',
                                {
                                    className:
                                        'wp-block-query-pagination-numbers page-numbers',
                                },
                                '3'
                            ),
                            el(
                                'span',
                                {
                                    className:
                                        'wp-block-query-pagination-next page-numbers',
                                },
                                'Next'
                            )
                        )
                    )
                )
            );
        },

        save: function () {
            // Server-side rendering
            return null;
        },
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.data,
    window.wp.apiFetch
);
