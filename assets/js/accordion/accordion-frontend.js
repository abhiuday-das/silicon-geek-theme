/**
 * Frontend Accordion Functionality
 * Handles accordion interactions and Bootstrap integration
 */
document.addEventListener('DOMContentLoaded', function() {
    
    function initializeAccordions() {
        const accordionContainers = document.querySelectorAll('.wp-block-understrap-accordion');
        
        accordionContainers.forEach(function(container) {
            const accordionId = container.dataset.accordionId;
            const alwaysOpen = container.dataset.alwaysOpen === 'true';
            
            // Set up proper data attributes for Bootstrap
            const accordionItems = container.querySelectorAll('.accordion-item');
            
            accordionItems.forEach(function(item, index) {
                const itemId = item.dataset.itemId || 'accordion-item-' + accordionId + '-' + index;
                const button = item.querySelector('.accordion-button');
                const collapse = item.querySelector('.accordion-collapse');
                
                if (button && collapse) {
                    // Set proper IDs and references
                    collapse.id = itemId;
                    button.setAttribute('data-bs-target', '#' + itemId);
                    button.setAttribute('aria-controls', itemId);
                    
                    // Set parent reference for single-open behavior
                    if (!alwaysOpen) {
                        collapse.setAttribute('data-bs-parent', '#' + container.id);
                    }
                    
                    // Add click event for custom behavior
                    button.addEventListener('click', function(e) {
                        // Let Bootstrap handle the collapse, but we can add custom logic here
                        setTimeout(() => {
                            const isExpanded = !this.classList.contains('collapsed');
                            this.setAttribute('aria-expanded', isExpanded);
                            
                            // Custom event for developers
                            const customEvent = new CustomEvent('accordion.toggle', {
                                detail: {
                                    accordion: container,
                                    item: item,
                                    isOpen: isExpanded,
                                    itemId: itemId
                                }
                            });
                            container.dispatchEvent(customEvent);
                        }, 10);
                    });
                }
            });
            
            // Initialize Bootstrap Collapse for this accordion
            try {
                // Only initialize if Bootstrap is available
                if (window.bootstrap && window.bootstrap.Collapse) {
                    const collapses = container.querySelectorAll('.accordion-collapse');
                    collapses.forEach(function(collapse) {
                        new window.bootstrap.Collapse(collapse, {
                            toggle: false // Don't auto-toggle on initialization
                        });
                    });
                }
            } catch (error) {
                console.warn('Bootstrap not available for accordion initialization:', error);
            }
        });
    }
    
    // Initialize accordions on page load
    initializeAccordions();
    
    // Re-initialize if new accordions are added dynamically
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && 
                        node.classList && 
                        node.classList.contains('wp-block-understrap-accordion')) {
                        setTimeout(initializeAccordions, 100);
                    }
                });
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Keyboard accessibility
    document.addEventListener('keydown', function(e) {
        if (e.target.classList.contains('accordion-button')) {
            // Enter or Space key
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                e.target.click();
            }
            
            // Arrow key navigation
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                const accordion = e.target.closest('.wp-block-understrap-accordion');
                const buttons = accordion.querySelectorAll('.accordion-button');
                const currentIndex = Array.from(buttons).indexOf(e.target);
                
                let nextIndex;
                if (e.key === 'ArrowDown') {
                    nextIndex = currentIndex + 1 >= buttons.length ? 0 : currentIndex + 1;
                } else {
                    nextIndex = currentIndex - 1 < 0 ? buttons.length - 1 : currentIndex - 1;
                }
                
                buttons[nextIndex].focus();
            }
        }
    });
    
    // Add smooth scrolling for better UX
    document.addEventListener('shown.bs.collapse', function(e) {
        if (e.target.closest('.wp-block-understrap-accordion')) {
            const accordion = e.target.closest('.accordion-item');
            if (accordion) {
                setTimeout(() => {
                    accordion.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                }, 100);
            }
        }
    });
});