/**
 * Read More Toggle - Frontend Script
 * 
 * Vanilla JavaScript for toggle functionality
 * Accessible, works with dynamic content (Query Loops, etc.)
 */

(function() {
    'use strict';

    /**
     * Initialize toggle functionality
     */
    function initReadMoreToggles() {
        const toggleWrappers = document.querySelectorAll('.read-more-toggle-wrapper');
        
        toggleWrappers.forEach(function(wrapper) {
            // Skip if already initialized
            if (wrapper.dataset.initialized === 'true') {
                return;
            }

            const hiddenSection = wrapper.querySelector('.read-more-hidden-section');
            const readMoreBtn = wrapper.querySelector('.read-more-button');
            const readLessBtn = wrapper.querySelector('.read-less-button');
            
            // Check if elements exist
            if (!hiddenSection || !readMoreBtn || !readLessBtn) {
                console.warn('Read More Toggle: Missing required elements', wrapper);
                return;
            }

            // Get button text from data attributes
            const readMoreText = wrapper.dataset.readMoreText || 'Read More';
            const readLessText = wrapper.dataset.readLessText || 'Read Less';

            // Initially hide the hidden section
            hiddenSection.style.display = 'none';
            hiddenSection.classList.add('is-hidden');

            // Read More button click
            readMoreBtn.addEventListener('click', function() {
                // Show hidden content
                hiddenSection.style.display = 'block';
                hiddenSection.classList.remove('is-hidden');
                hiddenSection.classList.add('is-visible');
                
                // Update ARIA
                readMoreBtn.setAttribute('aria-expanded', 'true');
                readLessBtn.setAttribute('aria-expanded', 'true');
                
                // Toggle button visibility
                readMoreBtn.style.display = 'none';
                readLessBtn.style.display = 'inline-block';

                // Smooth scroll to hidden content if needed
                const rect = hiddenSection.getBoundingClientRect();
                if (rect.top < 0) {
                    hiddenSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });

            // Read Less button click
            readLessBtn.addEventListener('click', function() {
                // Hide content
                hiddenSection.style.display = 'none';
                hiddenSection.classList.add('is-hidden');
                hiddenSection.classList.remove('is-visible');
                
                // Update ARIA
                readMoreBtn.setAttribute('aria-expanded', 'false');
                readLessBtn.setAttribute('aria-expanded', 'false');
                
                // Toggle button visibility
                readMoreBtn.style.display = 'inline-block';
                readLessBtn.style.display = 'none';

                // Scroll back to visible section
                const visibleSection = wrapper.querySelector('.read-more-visible-section');
                if (visibleSection) {
                    visibleSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });

            // Mark as initialized
            wrapper.dataset.initialized = 'true';
        });
    }

    /**
     * Initialize on DOM ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReadMoreToggles);
    } else {
        initReadMoreToggles();
    }

    /**
     * Re-initialize for dynamic content (Query Loops, AJAX)
     * Use MutationObserver to detect new content
     */
    const observer = new MutationObserver(function(mutations) {
        let shouldReinit = false;
        
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(function(node) {
                    // Check if added node contains read-more-toggle
                    if (node.nodeType === 1) { // Element node
                        if (node.classList && node.classList.contains('read-more-toggle-wrapper')) {
                            shouldReinit = true;
                        } else if (node.querySelector && node.querySelector('.read-more-toggle-wrapper')) {
                            shouldReinit = true;
                        }
                    }
                });
            }
        });

        if (shouldReinit) {
            initReadMoreToggles();
        }
    });

    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    /**
     * Keyboard accessibility
     * Allow Enter/Space to trigger buttons
     */
    document.addEventListener('keydown', function(e) {
        if (e.target.classList.contains('read-more-button') || 
            e.target.classList.contains('read-less-button')) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                e.target.click();
            }
        }
    });

})();
