/* ==========================================================================
   🎯 SIDEBAR AUTO-HIDE SYSTEM - APENAS HOVER
   ========================================================================== */

class SidebarAutoHide {
    constructor() {
        this.body = document.body;
        this.sidebar = document.querySelector('.main-sidebar');
        this.hoverTimeout = null;
        this.isInitialized = false;
        
        this.init();
    }
    
    init() {
        if (!this.sidebar || this.isInitialized) return;
        
        // Força estado sempre colapsado para hover automático
        this.body.classList.add('sidebar-collapsed');
        
        // Bind apenas eventos de hover
        this.bindHoverEvents();
        
        // Set ARIA attributes
        this.updateARIA();
        
        this.isInitialized = true;
        console.log('✅ Sidebar auto-hide initialized');
    }
    
    bindHoverEvents() {
        // Hover para expandir
        this.sidebar.addEventListener('mouseenter', () => {
            if (this.hoverTimeout) {
                clearTimeout(this.hoverTimeout);
                this.hoverTimeout = null;
            }
            this.sidebar.setAttribute('data-hover-expanded', 'true');
        });
        
        // Mouse leave para colapsar
        this.sidebar.addEventListener('mouseleave', () => {
            this.hoverTimeout = setTimeout(() => {
                this.sidebar.setAttribute('data-hover-expanded', 'false');
                this.hoverTimeout = null;
            }, 200);
        });
        
        // Handle window resize
        window.addEventListener('resize', this.throttle(() => {
            this.handleResize();
        }, 250));
    }
    
    updateARIA() {
        if (!this.sidebar) return;
        
        this.sidebar.setAttribute('aria-expanded', 'false');
        this.sidebar.setAttribute('data-collapsed', 'true');
    }
    
    handleResize() {
        // Mantém sempre colapsado em mobile
        const isMobile = window.innerWidth <= 767.98;
        if (isMobile) {
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
            isPinned: false,
            isCollapsed: true
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