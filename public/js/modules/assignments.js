/**
 * Assignment List Management Module
 * Handles display, filtering, status updates, and map integration for RVM assignments
 */

class AssignmentManager {
    constructor() {
        this.assignments = [];
        this.currentPage = 1;
        this.perPage = 15;
        this.currentFilter = 'all';
    }

    /**
     * Initialize the assignment manager
     */
    /**
     * Initialize the assignment manager
     */
    init() {
        console.log('[AssignmentManager] Initializing...');

        // Wait for page loaded event (SPA Navigation)
        document.addEventListener('pageLoaded', (e) => {
            if (e.detail.page === 'assignments') {
                this.initializeModule();
            }
        });

        // Initialize immediately if directly on page
        if (window.location.pathname.includes('/dashboard/assignments')) {
            this.initializeModule();
        }
    }

    /**
     * Core initialization logic
     */
    initializeModule() {
        this.waitForBootstrap().then(() => {
            // Initialize Bootstrap tooltips
            this.initTooltips();

            // Initialize toasts
            const statusToast = document.getElementById('statusToast');
            if (statusToast) this.toasts.status = new bootstrap.Toast(statusToast);

            // Setup filter buttons
            this.setupFilterButtons();

            // Load assignments
            this.loadAssignments();
        });
    }

    // Wait for Bootstrap to be fully loaded
    waitForBootstrap() {
        return new Promise((resolve) => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
                resolve();
                return;
            }

            const checkBootstrap = setInterval(() => {
                if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
                    clearInterval(checkBootstrap);
                    resolve();
                }
            }, 100);

            // Timeout after 5 seconds
            setTimeout(() => {
                clearInterval(checkBootstrap);
                resolve();
            }, 5000);
        });
    }
    setupFilterButtons() {
        const filterButtons = document.querySelectorAll('[data-filter]');
        filterButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                // Update active state
                filterButtons.forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');

                // Update filter and reload
                this.currentFilter = e.target.getAttribute('data-filter');
                this.currentPage = 1;
                this.loadAssignments();
            });
        });
    }

    /**
     * Load assignments from API
     */
    async loadAssignments(page = 1) {
        try {
            this.showLoading();
            this.currentPage = page;

            // Build query parameters
            const params = new URLSearchParams({
                page: this.currentPage,
                per_page: this.perPage
            });

            if (this.currentFilter !== 'all') {
                params.append('status', this.currentFilter);
            }

            const rawResponse = await apiHelper.get(`/api/v1/admin/assignments?${params.toString()}`);

            if (rawResponse && rawResponse.ok) {
                const response = await rawResponse.json();
                if (response && response.data) {
                    this.assignments = response.data;
                    this.renderAssignments(response);
                    this.renderPagination(response);
                }
            }
        } catch (error) {
            console.error('[AssignmentManager] Load error:', error);
            this.showError('Failed to load assignments');
        }
    }

    /**
     * Render assignments table
     */
    renderAssignments(response) {
        const tbody = document.getElementById('assignments-tbody');
        if (!tbody) return;

        if (!response.data || response.data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <i class="bx bx-folder-open" style="font-size: 3rem; color: #ccc;"></i>
                        <p class="text-muted mt-2">No assignments found</p>
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        response.data.forEach(assignment => {
            html += this.renderAssignmentRow(assignment);
        });

        tbody.innerHTML = html;

        // Re-initialize tooltips for new content
        this.initTooltips();
    }

    /**
     * Render single assignment row with collapse details
     */
    renderAssignmentRow(assignment) {
        const statusClass = this.getStatusClass(assignment.status);
        const statusLabel = this.getStatusLabel(assignment.status);

        return `
            <!-- Main Row (Clickable) -->
            <tr class="cursor-pointer" data-bs-toggle="collapse" data-bs-target="#collapse-${assignment.id}" 
                aria-expanded="false" aria-controls="collapse-${assignment.id}">
                <td><strong>#${assignment.id}</strong></td>
                <td>
                    <span class="fw-bold">${this.escapeHtml(assignment.machine?.name || 'N/A')}</span><br>
                    <small class="text-muted">${this.escapeHtml(assignment.machine?.serial_number || '')}</small>
                </td>
                <td>
                    ${this.renderAvatarGroup(assignment.team || assignment.user)}
                </td>
                <td>
                    <span class="badge bg-label-${statusClass}">${statusLabel}</span>
                </td>
                <td>
                    ${this.renderLocationButton(assignment)}
                </td>
                <td>
                    ${this.renderActionDropdown(assignment)}
                </td>
            </tr>
            
            <!-- Details Row (Collapse) -->
            <tr>
                <td colspan="6" class="p-0">
                    <div id="collapse-${assignment.id}" class="accordion-collapse collapse bg-lighter">
                        ${this.renderAssignmentDetails(assignment)}
                    </div>
                </td>
            </tr>
        `;
    }

    /**
     * Render avatar group (with initials fallback)
     */
    renderAvatarGroup(userOrUsers) {
        if (!userOrUsers) {
            return '<span class="text-muted">-</span>';
        }

        // Normalize to array
        const users = Array.isArray(userOrUsers) ? userOrUsers : [userOrUsers];

        if (users.length === 0) {
            return '<span class="text-muted">-</span>';
        }

        // Build avatar HTML items
        const avatars = users.slice(0, 3).map(user => {
            const initials = this.getUserInitials(user);
            const color = this.getAvatarColor(user.id);

            if (user.avatar_url) {
                return `
                    <span class="avatar avatar-sm me-1" data-bs-toggle="tooltip" title="${this.escapeHtml(user.name)}">
                        <img src="${user.avatar_url}" alt="${initials}" class="rounded-circle" style="width: 32px; height: 32px;">
                    </span>
                `;
            }

            // Initials avatar with proper styling
            return `
                <span class="avatar avatar-sm me-1" data-bs-toggle="tooltip" title="${this.escapeHtml(user.name)}">
                    <span class="avatar-initial rounded-circle bg-label-${color}" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600;">
                        ${initials}
                    </span>
                </span>
            `;
        }).join('');

        // Add counter if more than 3 users
        let counter = '';
        if (users.length > 3) {
            const remainingNames = users.slice(3).map(u => u.name).join(', ');
            counter = `
                <span class="avatar avatar-sm" data-bs-toggle="tooltip" title="${this.escapeHtml(remainingNames)}">
                    <span class="avatar-initial rounded-circle bg-secondary text-white" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 600;">
                        +${users.length - 3}
                    </span>
                </span>
            `;
        }

        return `
            <div class="d-flex align-items-center">
                ${avatars}
                ${counter}
            </div>
        `;
    }

    /**
     * Get user initials (First + Last)
     */
    getUserInitials(user) {
        if (user.avatar_initials) {
            return user.avatar_initials;
        }

        if (user.initials) {
            return user.initials;
        }

        const nameParts = user.name.trim().split(' ');
        if (nameParts.length >= 2) {
            return (nameParts[0][0] + nameParts[nameParts.length - 1][0]).toUpperCase();
        }
        return user.name.substring(0, 2).toUpperCase();
    }

    /**
     * Get consistent avatar color based on user ID
     */
    getAvatarColor(userId) {
        const colors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'];
        return colors[userId % colors.length];
    }

    /**
     * Render location button
     */
    renderLocationButton(assignment) {
        if (!assignment.latitude || !assignment.longitude) {
            return '<span class="text-muted">-</span>';
        }

        return `
            <button type="button" 
                    class="btn btn-icon btn-outline-primary btn-sm btn-location" 
                    onclick="event.stopPropagation(); window.assignmentManager.openLocation(${assignment.latitude}, ${assignment.longitude}, '${this.escapeHtml(assignment.address || 'Location')}')"
                    data-bs-toggle="tooltip" 
                    title="View on Map">
                <i class="ti tabler-map-pin"></i>
            </button>
        `;
    }

    /**
     * Render action dropdown
     */
    renderActionDropdown(assignment) {
        return `
            <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" 
                        data-bs-toggle="dropdown" 
                        onclick="event.stopPropagation();">
                    <i class="ti tabler-dots-vertical"></i>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);" 
                       onclick="event.stopPropagation(); window.assignmentManager.updateStatus(${assignment.id}, 'pending')">
                        <i class="ti tabler-clock me-1"></i> Set Pending
                    </a>
                    <a class="dropdown-item" href="javascript:void(0);" 
                       onclick="event.stopPropagation(); window.assignmentManager.updateStatus(${assignment.id}, 'in_progress')">
                        <i class="ti tabler-player-play me-1"></i> Set In Progress
                    </a>
                    <a class="dropdown-item" href="javascript:void(0);" 
                       onclick="event.stopPropagation(); window.assignmentManager.updateStatus(${assignment.id}, 'completed')">
                        <i class="ti tabler-check me-1"></i> Set Completed
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="javascript:void(0);" 
                       onclick="event.stopPropagation(); window.assignmentManager.updateStatus(${assignment.id}, 'cancelled')">
                        <i class="ti tabler-ban me-1"></i> Cancel
                    </a>
                </div>
            </div>
        `;
    }

    /**
     * Render assignment details (collapsed section)
     */
    renderAssignmentDetails(assignment) {
        return `
            <div class="assignment-details p-3">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary">Location & Machine Details</h6>
                        <p class="mb-1"><strong>Machine:</strong> ${this.escapeHtml(assignment.machine?.name || 'N/A')}</p>
                        <p class="mb-1"><strong>Serial:</strong> ${this.escapeHtml(assignment.machine?.serial_number || 'N/A')}</p>
                        <p class="mb-1" style="word-wrap: break-word; white-space: normal;"><strong>Address:</strong> ${this.escapeHtml(assignment.address || 'Not specified')}</p>
                        <p class="mb-1"><strong>Assigned:</strong> ${this.formatDate(assignment.assigned_at)}</p>
                        ${assignment.completed_at ? `<p class="mb-0"><strong>Completed:</strong> ${this.formatDate(assignment.completed_at)}</p>` : ''}
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary">Notes & Instructions</h6>
                        ${assignment.notes ? `
                            <div class="alert alert-warning mb-0" role="alert" style="word-wrap: break-word; white-space: normal;">
                                <i class="ti tabler-note me-1"></i> ${this.escapeHtml(assignment.notes)}
                            </div>
                        ` : '<p class="text-muted">No notes provided</p>'}
                    </div>
                </div>
                <div class="mt-3 text-end">
                    <button class="btn btn-sm btn-primary" onclick="window.assignmentManager.openLocation(${assignment.latitude}, ${assignment.longitude}, '${this.escapeHtml(assignment.address || 'Location')}')">
                        <i class="ti tabler-map-pin me-1"></i> View on Map
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Update assignment status
     */
    async updateStatus(assignmentId, newStatus) {
        try {
            const rawResponse = await apiHelper.patch(`/api/v1/admin/assignments/${assignmentId}/status`, {
                status: newStatus
            });

            if (rawResponse && rawResponse.ok) {
                await rawResponse.json(); // Consume body

                // Show success toast
                this.showSuccess(`Status updated to "<strong>${this.getStatusLabel(newStatus)}</strong>"`);

                // Reload assignments
                await this.loadAssignments(this.currentPage);
            }
        } catch (error) {
            console.error('[AssignmentManager] Update status error:', error);
            this.showError('Failed to update status');
        }
    }

    /**
     * Open location in Google Maps
     */
    /**
     * Show success notification
     */
    showSuccess(message) {
        this.showToast(message, 'success');
    }

    /**
     * Show error notification
     */
    showError(message) {
        this.showToast(message, 'danger');
    }

    /**
     * Show dynamic toast notification
     */
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = 'position-fixed top-0 end-0 p-3';
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <div class="toast show" role="alert">
                <div class="toast-header bg-${type} text-white">
                    <i class="ti tabler-${type === 'success' ? 'check' : 'alert-circle'} me-2"></i>
                    <strong class="me-auto">${type === 'success' ? 'Success' : 'Notification'}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" onclick="this.closest('.position-fixed').remove()"></button>
                </div>
                <div class="toast-body bg-white">
                    ${message}
                </div>
            </div>
        `;
        document.body.appendChild(toast);

        // Auto remove after 3 seconds
        setTimeout(() => {
            if (toast && toast.parentNode) {
                toast.remove();
            }
        }, 3000);
    }

    /**
     * Open location in Google Maps
     */
    openLocation(lat, lng, name) {
        if (!lat || !lng) {
            this.showError('No location data available');
            return;
        }

        // Show success toast (Green, Top Right)
        this.showSuccess(`Opening Google Maps for <strong>${name}</strong>...`);

        // Open Google Maps in new tab
        setTimeout(() => {
            const url = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
            window.open(url, '_blank');
        }, 500);
    }

    /**
     * Render pagination
     */
    renderPagination(response) {
        const paginationEl = document.getElementById('assignments-pagination');
        if (!paginationEl || !response.last_page) return;

        let html = '';

        // Previous button
        html += `
            <li class="page-item ${response.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0);" 
                   onclick="window.assignmentManager.loadAssignments(${response.current_page - 1})">
                    <i class="ti tabler-chevron-left"></i>
                </a>
            </li>
        `;

        //Page numbers (show 5 pages max)
        const start = Math.max(1, response.current_page - 2);
        const end = Math.min(response.last_page, start + 4);

        for (let i = start; i <= end; i++) {
            html += `
                <li class="page-item ${i === response.current_page ? 'active' : ''}">
                    <a class="page-link" href="javascript:void(0);" 
                       onclick="window.assignmentManager.loadAssignments(${i})">${i}</a>
                </li>
            `;
        }

        // Next button
        html += `
            <li class="page-item ${response.current_page === response.last_page ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0);" 
                   onclick="window.assignmentManager.loadAssignments(${response.current_page + 1})">
                    <i class="ti tabler-chevron-right"></i>
                </a>
            </li>
        `;

        paginationEl.innerHTML = html;
    }

    /**
     * Helper: Get status CSS class
     */
    getStatusClass(status) {
        const statusMap = {
            'pending': 'pending',
            'in_progress': 'in_progress',
            'completed': 'completed',
            'cancelled': 'cancelled'
        };
        return statusMap[status] || 'secondary';
    }

    /**
     * Helper: Get status label
     */
    getStatusLabel(status) {
        const labelMap = {
            'pending': 'Pending',
            'in_progress': 'In Progress',
            'completed': 'Completed',
            'cancelled': 'Cancelled'
        };
        return labelMap[status] || status;
    }

    /**
     * Helper: Initialize Bootstrap tooltips
     */
    initTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(tooltipTriggerEl => {
            // Dispose existing tooltip if any
            const existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
            if (existingTooltip) {
                existingTooltip.dispose();
            }
            // Create new tooltip
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    /**
     * Helper: Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Helper: Format date
     */
    formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    /**
     * Show loading state
     */
    showLoading() {
        const tbody = document.getElementById('assignments-tbody');
        if (!tbody) return;

        tbody.innerHTML = `
            <tr class="skeleton-row">
                <td colspan="6">
                    <div class="d-flex justify-content-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    }

    /**
     * Show error message
     */
    showError(message) {
        console.error('[AssignmentManager]', message);
        // Could implement toast notification here
        alert(message);
    }
}

// Initialize global instance
window.assignmentManager = new AssignmentManager();

// Auto-init if on assignments page directly
if (window.location.pathname.includes('/dashboard/assignments')) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => window.assignmentManager.init());
    } else {
        window.assignmentManager.init();
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AssignmentManager;
}
