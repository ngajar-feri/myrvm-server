/**
 * Logs Management Module
 * Handles activity logs display, filtering, and pagination
 */

class LogsManagement {
    constructor() {
        this.currentPage = 1;
        this.perPage = 20;
        this.filters = {
            search: '',
            module: '',
            action: '',
            date_from: '',
            date_to: ''
        };

        this.init();
    }

    init() {
        // Wait for page loaded event
        document.addEventListener('pageLoaded', (e) => {
            if (e.detail.page === 'logs') {
                this.setupEventListeners();
                this.loadLogs();
                this.loadStats();
            }
        });

        // If already on logs page
        if (window.location.pathname.includes('/logs')) {
            this.setupEventListeners();
            this.loadLogs();
            this.loadStats();
        }
    }

    setupEventListeners() {
        // Search with debounce
        const searchInput = document.getElementById('log-search');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(() => {
                this.filters.search = searchInput.value;
                this.currentPage = 1;
                this.loadLogs();
            }, 500));
        }

        // Module filter
        const moduleFilter = document.getElementById('module-filter');
        if (moduleFilter) {
            moduleFilter.addEventListener('change', () => {
                this.filters.module = moduleFilter.value;
                this.currentPage = 1;
                this.loadLogs();
            });
        }

        // Action filter
        const actionFilter = document.getElementById('action-filter');
        if (actionFilter) {
            actionFilter.addEventListener('change', () => {
                this.filters.action = actionFilter.value;
                this.currentPage = 1;
                this.loadLogs();
            });
        }

        // Date from filter
        const dateFrom = document.getElementById('date-from');
        if (dateFrom) {
            dateFrom.addEventListener('change', () => {
                this.filters.date_from = dateFrom.value;
                this.currentPage = 1;
                this.loadLogs();
            });
        }

        // Date to filter
        const dateTo = document.getElementById('date-to');
        if (dateTo) {
            dateTo.addEventListener('change', () => {
                this.filters.date_to = dateTo.value;
                this.currentPage = 1;
                this.loadLogs();
            });
        }
    }

    async loadLogs() {
        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                per_page: this.perPage,
                search: this.filters.search,
                module: this.filters.module,
                action: this.filters.action,
                date_from: this.filters.date_from,
                date_to: this.filters.date_to
            });

            // Use apiHelper with Bearer Token
            const response = await apiHelper.get(`/api/v1/logs?${params}`);

            if (!response.ok) {
                if (response.status === 403) {
                    this.showError('Access denied. Insufficient permissions.');
                    return;
                }
                throw new Error('Failed to load logs');
            }

            const data = await response.json();
            this.renderLogs(data.data);
            this.renderPagination(data);
            this.updateInfo(data);

        } catch (error) {
            console.error('Error loading logs:', error);
            this.showError('Failed to load logs. Please refresh the page.');
        }
    }

    async loadStats() {
        try {
            const response = await apiHelper.get('/api/v1/logs/stats');

            if (!response.ok) return;

            const data = await response.json();
            const stats = data.stats;

            // Update UI
            const totalEl = document.getElementById('total-logs');
            const todayEl = document.getElementById('today-logs');
            const errorEl = document.getElementById('error-logs');
            const warningEl = document.getElementById('warning-logs');

            if (totalEl) totalEl.textContent = stats.total || 0;
            if (todayEl) todayEl.textContent = stats.today || 0;
            if (errorEl) errorEl.textContent = stats.errors || 0;
            if (warningEl) warningEl.textContent = stats.warnings || 0;

        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }

    renderLogs(logs) {
        const tbody = document.getElementById('logs-table-body');
        if (!tbody) return;

        if (logs.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="empty-state">
                            <i class="ti tabler-file-off" style="font-size: 3rem; color: #ccc;"></i>
                            <div class="mt-2 text-muted">No logs found</div>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = logs.map(log => `
            <tr>
                <td>
                    <small class="text-muted">${this.formatDateTime(log.created_at)}</small>
                </td>
                <td>
                    ${log.user ? `
                        <span class="fw-semibold">${this.escapeHtml(log.user.name)}</span>
                    ` : '<span class="text-muted">System</span>'}
                </td>
                <td>
                    <span class="badge bg-label-${this.getModuleColor(log.module)}">
                        ${this.escapeHtml(log.module)}
                    </span>
                </td>
                <td>
                    <span class="badge bg-${this.getActionColor(log.action)}">
                        ${this.escapeHtml(log.action)}
                    </span>
                </td>
                <td>
                    <span class="text-truncate d-inline-block" style="max-width: 200px;" title="${this.escapeHtml(log.description || '')}">
                        ${this.escapeHtml(log.description || '-')}
                    </span>
                </td>
                <td>
                    ${log.browser ? `
                        <small><i class="ti tabler-browser me-1"></i>${this.escapeHtml(log.browser)}</small>
                    ` : '<span class="text-muted">-</span>'}
                </td>
                <td>
                    ${log.platform ? `
                        <small>
                            <i class="ti tabler-${this.getDeviceIcon(log.device)} me-1"></i>
                            ${this.escapeHtml(log.platform)}
                        </small>
                    ` : '<span class="text-muted">-</span>'}
                </td>
                <td>
                    <code>${this.escapeHtml(log.ip_address || '-')}</code>
                </td>
            </tr>
        `).join('');
    }

    renderPagination(data) {
        const pagination = document.getElementById('logs-pagination');
        if (!pagination || !data.last_page) return;

        let html = '';

        // Previous button
        html += `
            <li class="page-item ${data.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link cursor-pointer" onclick="logsManagement.goToPage(${data.current_page - 1})">
                    <i class="ti tabler-chevron-left"></i>
                </a>
            </li>
        `;

        // Page numbers
        const startPage = Math.max(1, data.current_page - 2);
        const endPage = Math.min(data.last_page, data.current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            html += `
                <li class="page-item ${i === data.current_page ? 'active' : ''}">
                    <a class="page-link cursor-pointer" onclick="logsManagement.goToPage(${i})">${i}</a>
                </li>
            `;
        }

        // Next button
        html += `
            <li class="page-item ${data.current_page === data.last_page ? 'disabled' : ''}">
                <a class="page-link cursor-pointer" onclick="logsManagement.goToPage(${data.current_page + 1})">
                    <i class="ti tabler-chevron-right"></i>
                </a>
            </li>
        `;

        pagination.innerHTML = html;
    }

    updateInfo(data) {
        const info = document.getElementById('logs-info');
        if (!info) return;

        const from = (data.current_page - 1) * data.per_page + 1;
        const to = Math.min(data.current_page * data.per_page, data.total);

        info.textContent = `Showing ${from} to ${to} of ${data.total} entries`;
    }

    goToPage(page) {
        this.currentPage = page;
        this.loadLogs();
    }

    clearFilters() {
        this.filters = {
            search: '',
            module: '',
            action: '',
            date_from: '',
            date_to: ''
        };

        // Reset form elements
        const searchInput = document.getElementById('log-search');
        const moduleFilter = document.getElementById('module-filter');
        const actionFilter = document.getElementById('action-filter');
        const dateFrom = document.getElementById('date-from');
        const dateTo = document.getElementById('date-to');

        if (searchInput) searchInput.value = '';
        if (moduleFilter) moduleFilter.value = '';
        if (actionFilter) actionFilter.value = '';
        if (dateFrom) dateFrom.value = '';
        if (dateTo) dateTo.value = '';

        this.currentPage = 1;
        this.loadLogs();
    }

    async exportLogs(format = 'excel') {
        try {
            // Tampilkan loading indicator (opsional)
            const btn = document.querySelector(`button[onclick="logsManagement.exportLogs('${format}')"]`);
            const originalText = btn ? btn.innerHTML : '';
            if (btn) btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Downloading...';

            const params = new URLSearchParams({
                search: this.filters.search,
                module: this.filters.module,
                action: this.filters.action,
                date_from: this.filters.date_from,
                date_to: this.filters.date_to,
                per_page: 1000,
                format: format
            });

            // Ambil Token dari localStorage atau tempat Anda menyimpannya
            // Sesuaikan key 'access_token' dengan aplikasi Anda
            const token = localStorage.getItem('token');

            const response = await fetch(`/api/v1/logs/export?${params}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': format === 'excel' ?
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' :
                        'application/pdf',
                }
            });

            if (!response.ok) {
                if (response.status === 403) throw new Error('Access denied');
                throw new Error('Export failed');
            }

            // Ubah response menjadi Blob (File)
            const blob = await response.blob();

            // Buat URL sementara untuk blob tersebut
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;

            // Tentukan nama file
            const timestamp = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
            const extension = format === 'excel' ? 'xlsx' : 'pdf';
            a.download = `activity_logs_${timestamp}.${extension}`;

            // Trigger download otomatis
            document.body.appendChild(a);
            a.click();

            // Bersihkan
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

        } catch (error) {
            console.error('Export error:', error);
            alert('Gagal mengunduh file: ' + error.message);
        } finally {
            // Kembalikan tombol ke semula
            if (btn) btn.innerHTML = originalText;
        }
    }

    // Utility functions
    getModuleColor(module) {
        const colors = {
            'Auth': 'primary',
            'Device': 'info',
            'Machine': 'success',
            'System': 'secondary',
            'Transaction': 'warning'
        };
        return colors[module] || 'secondary';
    }

    getActionColor(action) {
        const colors = {
            'Login': 'success',
            'Logout': 'secondary',
            'Create': 'primary',
            'Update': 'info',
            'Delete': 'danger',
            'Error': 'danger',
            'Warning': 'warning'
        };
        return colors[action] || 'secondary';
    }

    formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    showError(message) {
        const tbody = document.getElementById('logs-table-body');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="alert alert-danger">${message}</div>
                    </td>
                </tr>
            `;
        }
    }

    getDeviceIcon(device) {
        const icons = {
            'desktop': 'device-desktop',
            'phone': 'device-mobile',
            'tablet': 'device-tablet'
        };
        return icons[device] || 'device-desktop';
    }
}

// Initialize
const logsManagement = new LogsManagement();
