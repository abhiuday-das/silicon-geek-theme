/**
 * Custom Select Dropdown Functionality
 * Category Filter Block - Custom Select
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all custom selects
    const customSelects = document.querySelectorAll('.custom-select-wrapper');
    
    customSelects.forEach(function(wrapper) {
        const button = wrapper.querySelector('.custom-select-button');
        const dropdown = wrapper.querySelector('.custom-select-dropdown');
        const options = wrapper.querySelectorAll('.custom-select-option');
        const form = wrapper.closest('form');
        
        if (!button || !dropdown || !form) return;
        
        // Toggle dropdown on button click
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Close other dropdowns
            document.querySelectorAll('.custom-select-wrapper.active').forEach(function(other) {
                if (other !== wrapper) {
                    other.classList.remove('active');
                }
            });
            
            // Toggle current dropdown
            wrapper.classList.toggle('active');
        });
        
        // Handle option selection
        options.forEach(function(option) {
            option.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const value = this.getAttribute('data-value');
                const label = this.textContent.trim();
                
                // Update button text
                button.innerHTML = label + '<span class="custom-select-arrow"></span>';
                
                // Update selected state
                options.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                
                // Update hidden input value
                const hiddenInput = form.querySelector('input[name="cat"]');
                if (hiddenInput) {
                    hiddenInput.value = value;
                }
                
                // Close dropdown
                wrapper.classList.remove('active');
                
                // Submit form
                form.submit();
            });
        });
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-select-wrapper')) {
            document.querySelectorAll('.custom-select-wrapper.active').forEach(function(wrapper) {
                wrapper.classList.remove('active');
            });
        }
    });
    
    // Close dropdown on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.custom-select-wrapper.active').forEach(function(wrapper) {
                wrapper.classList.remove('active');
            });
        }
    });
});
