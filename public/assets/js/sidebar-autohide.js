/* ==========================================================================
   🎯 SIDEBAR AUTO-HIDE SYSTEM
   ========================================================================== */

class SidebarAutoHide {
    constructor() {
        this.body = document.body;
        this.sidebar = document.querySelector('.main-sidebar');
        this.isPinned = this.getStoredState();
        this.hoverTimeout = null;
        this.isInitialized = false;
        
        this.init();
    }
    
    init() {
        if (!this.sidebar || this.isInitialized) return;
        
        // Apply initial state
        this.applyState();
        
        // Create pin button
        this.createPinButton();
        
        // Bind events
        this.bindEvents();
        
        // Set ARIA attributes
        this.updateARIA();
        
        this.isInitialized = true;
        console.log('✅ Sidebar auto-hide initialized');
    }
    
    getStoredState() {
        try {
            const stored = localStorage.getItem('sidebarPinned');
            // Default to collapsed (false) for auto-hover behavior
            return stored === 'true';
        } catch (e) {
            console.warn('localStorage not available, using default state');
            return false; // Default to collapsed for auto-hover
        }
    }
    
    saveState() {
        try {
            localStorage.setItem('sidebarPinned', this.isPinned.toString());
        } catch (e) {
            console.warn('Could not save sidebar state to localStorage');
        }
    }
    
    applyState() {
        if (this.isPinned) {
            this.body.classList.remove('sidebar-collapsed');
        } else {
            this.body.classList.add('sidebar-collapsed');
        }
        this.updateARIA();
    }
    
    createPinButton() {
        const pinBtn = document.createElement('button');
        pinBtn.className = 'sidebar-pin-btn';
        pinBtn.innerHTML = '<i class="fas fa-thumbtack"></i>';
        pinBtn.title = this.isPinned ? 'Recolher sidebar' : 'Fixar sidebar';
        pinBtn.setAttribute('aria-label', pinBtn.title);
        
        this.sidebar.appendChild(pinBtn);
        this.pinBtn = pinBtn;
    }
    
    bindEvents() {
        // Pin button click
        this.pinBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.toggle();
        });
        
        // Keyboard support
        this.pinBtn?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.toggle();
            }
        });
        
        // Auto-collapse/expand on hover
        this.sidebar.addEventListener('mouseenter', () => {
            if (!this.isPinned) {
                // Clear any pending collapse
                if (this.hoverTimeout) {
                    clearTimeout(this.hoverTimeout);
                    this.hoverTimeout = null;
                }
                // Expand sidebar
                this.body.classList.remove('sidebar-collapsed');
                this.showTooltips();
            }
        });
        
        this.sidebar.addEventListener('mouseleave', () => {
            if (!this.isPinned) {
                // Delay collapse to avoid flickering
                this.hoverTimeout = setTimeout(() => {
                    this.body.classList.add('sidebar-collapsed');
                    this.hideTooltips();
                    this.hoverTimeout = null;
                }, 100); // 100ms delay
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', this.throttle(() => {
            this.handleResize();
        }, 250));
        
        // Escape key to collapse (accessibility)
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isPinned && document.activeElement?.closest('.main-sidebar')) {
                this.collapse();
            }
        });
    }
    
    toggle() {
        this.isPinned = !this.isPinned;
        this.applyState();
        this.saveState();
        this.updatePinButton();
        
        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('sidebarToggle', {
            detail: { isPinned: this.isPinned }
        }));
    }
    
    expand() {
        this.isPinned = true;
        this.applyState();
        this.saveState();
        this.updatePinButton();
    }
    
    collapse() {
        this.isPinned = false;
        this.applyState();
        this.saveState();
        this.updatePinButton();
    }
    
    updatePinButton() {
        if (!this.pinBtn) return;
        
        const title = this.isPinned ? 'Recolher sidebar' : 'Fixar sidebar';
        this.pinBtn.title = title;
        this.pinBtn.setAttribute('aria-label', title);
        
        const icon = this.pinBtn.querySelector('i');
        if (icon) {
            icon.className = this.isPinned ? 'fas fa-thumbtack' : 'fas fa-thumbtack fa-rotate-45';
        }
    }
    
    updateARIA() {
        if (!this.sidebar) return;
        
        this.sidebar.setAttribute('aria-expanded', this.isPinned.toString());
        this.sidebar.setAttribute('data-collapsed', (!this.isPinned).toString());
    }
    
    showTooltips() {
        // Add tooltip data attributes to nav items
        const navItems = this.sidebar.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            const link = item.querySelector('.nav-link p');
            if (link && !item.hasAttribute('data-tooltip')) {
                item.setAttribute('data-tooltip', link.textContent.trim());
            }
        });
    }
    
    hideTooltips() {
        // Tooltips are handled by CSS, no action needed
    }
    
    handleResize() {
        // Ensure proper behavior on mobile
        const isMobile = window.innerWidth <= 767.98;
        if (isMobile && !this.isPinned) {
            // On mobile, auto-hide behavior is different
            this.body.classList.add('sidebar-collapsed');
        }
    }
    
    // Utility: throttle function
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        }
    }
    
    // Public API
    getState() {
        return {
            isPinned: this.isPinned,
            isCollapsed: !this.isPinned
        };
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if AdminLTE is available
    if (typeof window.AdminLTE === 'undefined') {
        console.warn('AdminLTE not detected, initializing sidebar auto-hide anyway');
    }
    
    // Initialize auto-hide system
    window.sidebarAutoHide = new SidebarAutoHide();
});

// Graceful fallback for no-JS users
document.documentElement.classList.add('js-enabled');