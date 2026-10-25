/**
 * Filter Blog Frontend JavaScript
 * Handles search, category filtering, and pagination for the Filter Blog block
 */

jQuery(document).ready(function ($) {

    $(".wp-block-understrap-filter-blog").each(function () {
        initFilterBlog($(this));
    });

    function initFilterBlog($block) {
        // Ensure one tab is active
        if ($block.find(".filter-blog-tab.active").length === 0) {
            $block.find(".filter-blog-tab").first().addClass("active");
        }

        /**
         * 🏷️ Category Tabs
         */
        $block.on("click", ".filter-blog-tab a", function (e) {
            e.preventDefault();

            const $tab = $(this);
            const category = $tab.data("category") || "all";
            const postsPerPage = $block.find(".filter-blog-posts").data("posts-per-page") || 8;

            $block.find(".filter-blog-tab").removeClass("active");
            $tab.closest(".filter-blog-tab").addClass("active");

            // Sync dropdown
            $block.find(".filter-blog-category-dropdown").val(category);

            loadPosts($block, category, 1, postsPerPage);
        });

        /**
         * 📄 Pagination
         */
        $block.on("click", ".filter-blog-pagination a.page-numbers", function (e) {
            e.preventDefault();

            const $link = $(this);
            const page = parseInt($link.data("page")) || 1;
            const activeCategory =
                $block.find(".filter-blog-tab.active a").data("category") ||
                $block.find(".filter-blog-category-dropdown").val() ||
                "all";
            const postsPerPage =
                $block.find(".filter-blog-posts").data("posts-per-page") || 8;

            $block.find(".filter-blog-pagination .page-numbers").removeClass("current");
            $link.addClass("current");

            loadPosts($block, activeCategory, page, postsPerPage);

            $("html, body").animate(
                { scrollTop: $block.offset().top - 100 },
                500
            );
        });

        /**
         * 📂 Category Dropdown
         */
        $block.on("change", ".filter-blog-category-dropdown", function () {
            const category = $(this).val() || "all";
            const postsPerPage =
                $block.find(".filter-blog-posts").data("posts-per-page") || 8;
            loadPosts($block, category, 1, postsPerPage);

            // Sync tab highlight
            $block.find(".filter-blog-tab").removeClass("active");
            const $tab = $block
                .find('.filter-blog-tab a[data-category="' + category + '"]')
                .closest(".filter-blog-tab");
            if ($tab.length > 0) $tab.addClass("active");
        });

        /**
         * 🔍 Search (Debounced)
         */
        let searchTimeout;
        $block.on("input", ".filter-blog-search", function () {
            clearTimeout(searchTimeout);
            const $input = $(this);
            const searchValue = $input.val();
            const postsPerPage =
                $block.find(".filter-blog-posts").data("posts-per-page") || 8;
            const category =
                $block.find(".filter-blog-category-dropdown").val() ||
                $block.find(".filter-blog-tab.active a").data("category") ||
                "all";

            searchTimeout = setTimeout(() => {
                loadPosts($block, category, 1, postsPerPage, searchValue);
            }, 500);
        });
    }

    /**
     * 🔄 Load Posts (AJAX)
     */
    function loadPosts($block, category, page, postsPerPage, search = "") {
        showLoading($block);

        // Read block attributes from data attributes (passed by PHP)
        const postsWrapper = $block.find(".filter-blog-posts");

        const attributes = {
            displayFeaturedImage: postsWrapper.data("display-featured-image") == 1,
            displayDate: postsWrapper.data("display-date") == 1,
            displayAuthor: postsWrapper.data("display-author") == 1,
            displayCategory: postsWrapper.data("display-category") == 1,
            displayExcerpt: postsWrapper.data("display-excerpt") == 1,
            excerptLength: parseInt(postsWrapper.data("excerpt-length")) || 150,
        };

        $.ajax({
            url: filterBlogAjax.ajax_url,
            type: "POST",
            data: {
                action: "filter_blog_posts",
                nonce: filterBlogAjax.nonce,
                category: category || "all",
                search: search || "",
                paged: page || 1,
                postsPerPage: postsPerPage || 9,
                displayFeaturedImage: attributes.displayFeaturedImage,
                displayDate: attributes.displayDate,
                displayAuthor: attributes.displayAuthor,
                displayCategory: attributes.displayCategory,
                displayExcerpt: attributes.displayExcerpt,
                excerptLength: attributes.excerptLength,
            },
            success: function (response) {
                if (response.success) {
                    $block.find(".filter-blog-posts").html(response.data.posts);
                    $block.find(".filter-blog-pagination").html(response.data.pagination);
                    updateActiveStates($block, category, page);
                } else {
                    showError($block);
                }
                hideLoading($block);
            },
            error: function () {
                showError($block);
            },
        });
    }

    /**
     * 🧭 Update Active UI States
     */
    function updateActiveStates($block, category, page) {
        $block.find(".filter-blog-tab").removeClass("active");
        const $targetTab = $block
            .find('.filter-blog-tab a[data-category="' + category + '"]')
            .closest(".filter-blog-tab");
        if ($targetTab.length > 0) $targetTab.addClass("active");

        $block.find(".filter-blog-pagination .page-numbers").removeClass("current");
        const $targetPage = $block.find(
            '.filter-blog-pagination a.page-numbers[data-page="' + page + '"]'
        );
        if ($targetPage.length > 0) $targetPage.addClass("current");
    }

    /**
     * 🎞️ Loading / Error Handling
     */
    function showLoading($block) {
        $block.find(".filter-blog-loading").show();
        $block.find(".filter-blog-posts").css("opacity", "0.5");
    }

    function hideLoading($block) {
        $block.find(".filter-blog-loading").hide();
        $block.find(".filter-blog-posts").css("opacity", "1");
    }

    function showError($block) {
        hideLoading($block);
        $block
            .find(".filter-blog-posts")
            .html('<div class="filter-blog-error"><p>Error loading posts.</p></div>');
    }
});
