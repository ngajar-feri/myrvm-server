/**
 * MyRVM Dashboard - SPA Navigation Framework
 * Handles client-side navigation without full page reloads
 * 
 * @version 1.1.0
 * @author Antigravity AI
 */

class SPANavigator {
    constructor() {
        this.contentContainer = document.querySelector('.container-xxl.flex-grow-1.container-p-y');
        this.currentPage = null;
        this.pageCache = new Map();
        this.loadingState = false;

        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeCurrentPage();
    }

    setupEventListeners() {
        // Intercept menu link clicks
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[data-page]');
            if (link) {
                e.preventDefault();
                const page = link.dataset.page;
                const url = link.getAttribute('href');
                this.loadPage(page, url);
            }
        });

        // Handle browser back/forward buttons
        window.addEventListener('popstate', (e) => {
            if (e.state && e.state.page) {
                this.loadPage(e.state.page, e.state.url, false);
            }
        });
    }

    initializeCurrentPage() {
        // Detect current page from URL
        const path = window.location.pathname;
        const match = path.match(/\/dashboard\/([^\/]+)/);
        if (match) {
            this.currentPage = match[1];
        }
    }

    async loadPage(pageName, url, pushState = true) {
        // Prevent duplicate loads
        if (this.loadingState || this.currentPage === pageName) {
            return;
        }

        try {
            this.loadingState = true;

            // Cleanup any open modals before navigation
            this.cleanupModals();

            this.showLoading();

            // Check cache first
            let html;
            if (this.pageCache.has(pageName)) {
                html = this.pageCache.get(pageName);
            } else {
                // Fetch content-only version
                const response = await fetch(`${url}/content`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                html = await response.text();

                // Cache the content
                this.pageCache.set(pageName, html);
            }

            // Update content with fade animation
            await this.updateContent(html);

            // Update browser history
            if (pushState) {
                history.pushState(
                    { page: pageName, url: url },
                    '',
                    url
                );
            }

            // Update active menu
            this.updateActiveMenu(pageName);

            // Update current page
            this.currentPage = pageName;

            // Initialize page-specific scripts
            this.initPageScripts(pageName);

            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });

        } catch (error) {
            console.error('Failed to load page:', error);
            this.showError(error.message);
        } finally {
            this.loadingState = false;
            this.hideLoading();
        }
    }

    async updateContent(html) {
        return new Promise((resolve) => {
            // Fade out
            this.contentContainer.style.opacity = '0';
            this.contentContainer.style.transform = 'translateY(10px)';

            setTimeout(() => {
                // Update content
                this.contentContainer.innerHTML = html;

                // CRITICAL: Execute inline scripts (innerHTML doesn't execute them)
                this.executeInlineScripts(this.contentContainer);

                // Fade in
                setTimeout(() => {
                    // CRITICAL FIX: Clear inline styles to remove stacking context
                    // This creates a clean slate for z-index handling
                    this.contentContainer.style.opacity = '';
                    this.contentContainer.style.transform = '';
                    resolve();
                }, 50);
            }, 200);
        });
    }

    /**
     * Execute inline scripts that were injected via innerHTML
     * This is necessary because innerHTML doesn't execute script tags
     */
    executeInlineScripts(container) {
        const scripts = container.querySelectorAll('script');
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');

            // Copy attributes
            Array.from(oldScript.attributes).forEach(attr => {
                newScript.setAttribute(attr.name, attr.value);
            });

            // Copy inline content
            if (oldScript.textContent) {
                newScript.textContent = oldScript.textContent;
            }

            // Replace old script with new one (this triggers execution)
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });

        console.log(`✅ Executed ${scripts.length} inline script(s)`);
    }

    showLoading() {
        // Add loading overlay
        const overlay = document.createElement('div');
        overlay.id = 'spa-loading-overlay';
        overlay.className = 'spa-loading-overlay';
        overlay.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        `;
        document.body.appendChild(overlay);

        // Show progress bar
        const progressBar = document.createElement('div');
        progressBar.id = 'spa-progress-bar';
        progressBar.className = 'spa-progress-bar';
        document.body.appendChild(progressBar);

        setTimeout(() => {
            progressBar.style.width = '100%';
        }, 100);
    }

    hideLoading() {
        const overlay = document.getElementById('spa-loading-overlay');
        const progressBar = document.getElementById('spa-progress-bar');

        if (overlay) {
            overlay.style.opacity = '0';
            setTimeout(() => overlay.remove(), 300);
        }

        if (progressBar) {
            setTimeout(() => progressBar.remove(), 300);
        }
    }

    updateActiveMenu(pageName) {
        // Remove all active states
        document.querySelectorAll('.menu-item').forEach(item => {
            item.classList.remove('active');
        });

        // Add active state to current page
        const activeLink = document.querySelector(`a[data-page="${pageName}"]`);
        if (activeLink) {
            activeLink.closest('.menu-item').classList.add('active');
        }
    }

    initPageScripts(pageName) {
        // Map page names to their required module scripts
        const pageModules = {
            'users': '/js/modules/users.js',
            'machines': '/js/modules/machines.js',
            'edge-devices': '/js/modules/devices.js',
            'cv-servers': '/js/modules/cv-servers.js',
            'logs': '/js/modules/logs.js'
            // NOTE: 'assignments' removed - uses inline script in blade file with search functionality
        };

        // IMPORTANT: Initialize Bootstrap components FIRST before module scripts run
        // This ensures data-bs-toggle="modal" attributes work immediately
        this.initBootstrapComponents();

        // Load the module script if needed
        const moduleUrl = pageModules[pageName];
        if (moduleUrl) {
            this.loadModuleScript(moduleUrl, pageName);
        } else {
            // No module needed, just dispatch event
            this.dispatchPageLoaded(pageName);
        }
    }

    // Initialize Bootstrap components on dynamically loaded content
    initBootstrapComponents() {
        if (typeof bootstrap === 'undefined') {
            console.warn('Bootstrap not loaded yet');
            return;
        }

        // Initialize all modals in the new content
        document.querySelectorAll('.modal').forEach(modalEl => {
            // CRITICAL FIX: Move modals to body to escape stacking context
            // This ensures backdrops render correctly over the content
            if (modalEl.closest('.container-xxl')) {
                document.body.appendChild(modalEl);
            }

            // Dispose any existing instance first to prevent conflicts
            const existingModal = bootstrap.Modal.getInstance(modalEl);
            if (existingModal) {
                try { existingModal.dispose(); } catch (e) { }
            }
            // Pre-create modal instance so it's ready when triggered
            new bootstrap.Modal(modalEl, { backdrop: true, keyboard: true });
        });

        // Tooltips
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el);
        });

        // Popovers
        document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
            new bootstrap.Popover(el);
        });

        // Dropdowns - reinitialize for action menus
        document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(el => {
            new bootstrap.Dropdown(el);
        });

        console.log('✅ Bootstrap components initialized');
    }



    loadModuleScript(url, pageName) {
        // Check if script already loaded (check base URL without query params)
        const baseUrl = url.split('?')[0];
        const existingScript = document.querySelector(`script[src^="${baseUrl}"]`);

        if (existingScript) {
            // Script already loaded, just reinitialize
            this.reinitializeModule(pageName);
        } else {
            // Load script dynamically with cache-busting
            const script = document.createElement('script');
            // Add version timestamp to prevent browser caching
            const cacheBuster = `v=${Date.now()}`;
            script.src = url.includes('?') ? `${url}&${cacheBuster}` : `${url}?${cacheBuster}`;
            script.onload = () => {
                console.log(`✅ Module loaded: ${url}`);
                // Wait a bit for module to initialize, then dispatch event
                setTimeout(() => {
                    this.dispatchPageLoaded(pageName);
                }, 100);
            };
            script.onerror = () => {
                console.error(`❌ Failed to load module: ${url}`);
                this.dispatchPageLoaded(pageName);
            };
            document.body.appendChild(script);
        }
    }

    reinitializeModule(pageName) {
        // Call loadUsers/loadMachines directly if modules exist
        switch (pageName) {
            case 'users':
                if (typeof userManagement !== 'undefined') {
                    // Ensure event listeners are set up for new DOM
                    userManagement.setupEventListeners();
                    userManagement.loadUsers();
                    userManagement.loadStats();
                }
                break;
            case 'machines':
                if (typeof machineManagement !== 'undefined') {
                    machineManagement.setupEventListeners();
                    machineManagement.loadMachines();
                }
                break;
            case 'edge-devices':
                if (typeof deviceManagement !== 'undefined') {
                    deviceManagement.setupEventListeners();
                    deviceManagement.loadDevices();
                }
                break;
            case 'cv-servers':
                if (typeof cvServerManagement !== 'undefined') {
                    cvServerManagement.setupEventListeners();
                    cvServerManagement.loadServers();
                }
                break;
            case 'logs':
                if (typeof logsManagement !== 'undefined') {
                    logsManagement.setupEventListeners();
                    logsManagement.loadLogs();
                    logsManagement.loadStats();
                }
                break;
            case 'assignments':
                if (typeof assignmentManager !== 'undefined') {
                    assignmentManager.init();
                }
                break;
        }
        // Also dispatch pageLoaded event
        this.dispatchPageLoaded(pageName);
    }



    dispatchPageLoaded(pageName) {
        // Initialize page-specific functionality
        const event = new CustomEvent('pageLoaded', {
            detail: { page: pageName }
        });
        document.dispatchEvent(event);
    }


    showError(message) {
        // Show error toast/alert
        const toast = document.createElement('div');
        toast.className = 'position-fixed top-0 end-0 p-3';
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <div class="toast show" role="alert">
                <div class="toast-header bg-danger text-white">
                    <i class="ti tabler-alert-circle me-2"></i>
                    <strong class="me-auto">Error</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;
        document.body.appendChild(toast);

        setTimeout(() => toast.remove(), 5000);
    }

    clearCache() {
        this.pageCache.clear();
    }

    // Cleanup modal backdrops and reset body styles (fixes SPA navigation modal issues)
    cleanupModals() {
        // Remove any stale modal backdrops
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

        // Reset body styles that modals may have added
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';

        // Close any open modals and dispose their instances
        if (typeof bootstrap !== 'undefined') {
            document.querySelectorAll('.modal.show').forEach(modalEl => {
                try {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) {
                        modal.hide();
                        modal.dispose();
                    }
                } catch (e) {
                    // Ignore errors
                }
                modalEl.classList.remove('show');
                modalEl.style.display = '';
            });
        }
    }

    refreshCurrentPage() {
        if (this.currentPage) {
            this.pageCache.delete(this.currentPage);
            const link = document.querySelector(`a[data-page="${this.currentPage}"]`);
            if (link) {
                this.loadPage(this.currentPage, link.getAttribute('href'), false);
            }
        }
    }
}

// Initialize SPA Navigator when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Add transition styles to content container
    const container = document.querySelector('.container-xxl.flex-grow-1.container-p-y');
    if (container) {
        container.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
    }

    // Initialize navigator
    window.spaNavigator = new SPANavigator();

    console.log('✅ SPA Navigator initialized');
});

// Expose helper functions
window.refreshPage = () => {
    if (window.spaNavigator) {
        window.spaNavigator.refreshCurrentPage();
    }
};

window.clearPageCache = () => {
    if (window.spaNavigator) {
        window.spaNavigator.clearCache();
    }
};
