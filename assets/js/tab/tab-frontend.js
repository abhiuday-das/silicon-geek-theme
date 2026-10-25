/**
 * Frontend Tab Functionality
 * Handles tab navigation and content switching
 */
document.addEventListener('DOMContentLoaded', function() {
    
    function initializeTabs() {
        const tabContainers = document.querySelectorAll('.wp-block-understrap-tabs');
        
        tabContainers.forEach(function(container) {
            const tabsId = container.id;
            const tabsStyle = container.dataset.tabsStyle || 'nav-tabs';
            const isJustified = container.dataset.justified === 'true';
            const isVertical = container.dataset.vertical === 'true';
            const isFill = container.dataset.fill === 'true';
            
            // Get all tab panes
            const tabPanes = container.querySelectorAll('.tab-pane');
            
            if (tabPanes.length === 0) return;
            
            // Create tab navigation
            const tabNav = createTabNavigation(tabPanes, tabsStyle, isJustified, isVertical, isFill);
            
            // Insert navigation before tab content
            const tabContent = container.querySelector('.tab-content');
            if (tabContent && tabNav) {
                container.insertBefore(tabNav, tabContent);
            }
            
            // Initialize tab functionality
            initializeTabEvents(container);
        });
    }
    
    function createTabNavigation(tabPanes, style, justified, vertical, fill) {
        const nav = document.createElement('ul');
        let navClasses = 'nav ' + style;
        
        if (justified) navClasses += ' nav-justified';
        if (fill) navClasses += ' nav-fill';
        
        nav.className = navClasses;
        nav.setAttribute('role', 'tablist');
        
        tabPanes.forEach(function(pane, index) {
            const tabTitle = getTabTitle(pane);
            const tabDescription = getTabDescription(pane);
            const enableLearnMore = getLearnMoreEnabled(pane);
            const learnMoreText = getLearnMoreText(pane);
            const learnMoreUrl = getLearnMoreUrl(pane);
            const tabId = pane.id;
            const isActive = pane.classList.contains('active');
            
            const li = document.createElement('li');
            li.className = 'nav-item';
            li.setAttribute('role', 'presentation');
            
            const a = document.createElement('a');
            a.className = 'nav-link' + (isActive ? ' active' : '');
            a.href = '#' + tabId;
            a.id = tabId + '-tab';
            a.setAttribute('data-bs-toggle', 'tab');
            a.setAttribute('data-bs-target', '#' + tabId);
            a.setAttribute('role', 'tab');
            a.setAttribute('aria-controls', tabId);
            a.setAttribute('aria-selected', isActive ? 'true' : 'false');
            
            // Create tab content with title and description
            const tabContent = document.createElement('div');
            tabContent.className = 'tab-nav-content';
            
            const titleElement = document.createElement('h4');
            titleElement.className = 'tab-nav-title';
            titleElement.textContent = tabTitle;
            tabContent.appendChild(titleElement);
            
            if (tabDescription) {
                const descElement = document.createElement('p');
                descElement.className = 'tab-nav-description mb-0';
                descElement.textContent = tabDescription;
                tabContent.appendChild(descElement);
            }
            
            a.appendChild(tabContent);
            li.appendChild(a);
            
            // Add learn more link OUTSIDE the tab navigation anchor
            if (enableLearnMore && learnMoreUrl) {
                const learnMoreElement = document.createElement('div');
                learnMoreElement.className = 'wp-block-button is-style-link is-style-with-icon tab-learn-more';
                
                const learnMoreLink = document.createElement('a');
                learnMoreLink.href = learnMoreUrl;
                learnMoreLink.textContent = learnMoreText || 'Learn More';
                learnMoreLink.className = 'wp-block-button__link wp-element-button';

                
                learnMoreElement.appendChild(learnMoreLink);
                li.appendChild(learnMoreElement);
            }
            nav.appendChild(li);
        });
        
        return nav;
    }
    
    function getTabTitle(pane) {
        // Try to get title from data attribute first
        if (pane.dataset.tabTitle) {
            return pane.dataset.tabTitle;
        }
        
        // Fallback: get from first heading
        const heading = pane.querySelector('h1, h2, h3, h4, h5, h6');
        if (heading) {
            return heading.textContent.trim();
        }
        
        // Last fallback
        return 'Tab ' + (Array.from(pane.parentNode.children).indexOf(pane) + 1);
    }
    
    function getTabDescription(pane) {
        // Get description from data attribute
        if (pane.dataset.tabDescription) {
            return pane.dataset.tabDescription;
        }
        
        return null;
    }
    
    function getLearnMoreEnabled(pane) {
        return pane.dataset.enableLearnMore === 'true';
    }
    
    function getLearnMoreText(pane) {
        return pane.dataset.learnMoreText || 'Learn More';
    }
    
    function getLearnMoreUrl(pane) {
        return pane.dataset.learnMoreUrl || '';
    }
    
    function initializeTabEvents(container) {
        const tabLinks = container.querySelectorAll('[data-bs-toggle="tab"]');
        
        tabLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('data-bs-target').substring(1);
                const targetPane = document.getElementById(targetId);
                
                if (!targetPane) return;
                
                // Deactivate all tabs and panes in this container
                const allTabs = container.querySelectorAll('[data-bs-toggle="tab"]');
                const allPanes = container.querySelectorAll('.tab-pane');
                
                allTabs.forEach(function(tab) {
                    tab.classList.remove('active');
                    tab.setAttribute('aria-selected', 'false');
                });
                
                allPanes.forEach(function(pane) {
                    pane.classList.remove('show', 'active');
                });
                
                // Activate clicked tab and corresponding pane
                this.classList.add('active');
                this.setAttribute('aria-selected', 'true');
                
                targetPane.classList.add('show', 'active');
                
                // Trigger custom events
                const showEvent = new CustomEvent('shown.bs.tab', {
                    detail: {
                        target: targetPane,
                        relatedTarget: container.querySelector('.tab-pane.active:not(#' + targetId + ')')
                    }
                });
                
                this.dispatchEvent(showEvent);
            });
        });
    }
    
    // Initialize tabs on page load
    initializeTabs();
    
    // Re-initialize if new tabs are added dynamically
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && node.classList && node.classList.contains('wp-block-understrap-tabs')) {
                        initializeTabs();
                    }
                });
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});