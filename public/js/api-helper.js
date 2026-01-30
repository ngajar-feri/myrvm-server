/**
 * API Helper for Dashboard
 * Provides utility functions for making authenticated API calls
 */

const apiHelper = {
    /**
     * Get CSRF token from meta tag
     */
    getCsrfToken() {
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        return tokenMeta ? tokenMeta.getAttribute('content') : '';
    },

    /**
     * Make authenticated API call
     * Uses Bearer Token (from session) for authentication
     * @param {string} url - The API endpoint URL
     * @param {object} options - Fetch options
     * @param {boolean} options.skipAuthRedirect - If true, don't redirect on 401/403 (for password verification)
     */
    async fetch(url, options = {}) {
        const token = window.API_TOKEN || localStorage.getItem('api_token') || '';
        const skipAuthRedirect = options.skipAuthRedirect || false;
        delete options.skipAuthRedirect; // Remove from options before passing to fetch

        const defaultOptions = {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        };

        // Add Bearer Token if available
        if (token) {
            defaultOptions.headers['Authorization'] = `Bearer ${token}`;
        }

        // Merge options
        const mergedOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...(options.headers || {})
            }
        };

        try {
            const response = await fetch(url, mergedOptions);

            // Handle 401/403 - redirect to login (unless skipAuthRedirect is true)
            if ((response.status === 401 || response.status === 403) && !skipAuthRedirect) {
                window.location.href = '/login';
                return null;
            }

            return response;
        } catch (error) {
            console.error('API Helper Error:', error);
            throw error;
        }
    },

    /**
     * GET request
     */
    async get(url) {
        return this.fetch(url, { method: 'GET' });
    },

    /**
     * POST request
     */
    async post(url, data) {
        return this.fetch(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    /**
     * PUT request
     */
    async put(url, data) {
        return this.fetch(url, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    /**
     * PATCH request
     */
    async patch(url, data) {
        return this.fetch(url, {
            method: 'PATCH',
            body: JSON.stringify(data)
        });
    },

    /**
     * DELETE request  
     */
    async delete(url, data = null) {
        const options = { method: 'DELETE' };
        if (data) {
            options.body = JSON.stringify(data);
        }
        return this.fetch(url, options);
    },

    /**
     * Generic request method
     */
    async request(url, options = {}) {
        return this.fetch(url, options);
    }
};

// Make available globally
window.apiHelper = apiHelper;
