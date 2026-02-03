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

        this.backupFilters = {
            search: '',
            range: ''
        };

        this.backups = [];
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

        // Tab events
        const backupsTab = document.getElementById('backups-tab');
        const liveLogsTab = document.getElementById('live-logs-tab');

        if (backupsTab) {
            backupsTab.addEventListener('shown.bs.tab', () => {
                document.getElementById('logs-actions').classList.add('d-none');
                document.getElementById('backups-actions').classList.remove('d-none');
                this.loadBackups();
            });
        }

        if (liveLogsTab) {
            liveLogsTab.addEventListener('shown.bs.tab', () => {
                document.getElementById('logs-actions').classList.remove('d-none');
                document.getElementById('backups-actions').classList.add('d-none');
                this.loadLogs();
            });
        }

        // Backup search
        const backupSearch = document.getElementById('backup-search');
        if (backupSearch) {
            backupSearch.addEventListener('input', this.debounce(() => {
                this.backupFilters.search = backupSearch.value;
                this.renderBackups();
            }, 500));
        }

        // Backup range filter
        const rangeFilter = document.getElementById('backup-range-filter');
        if (rangeFilter) {
            rangeFilter.addEventListener('change', () => {
                this.backupFilters.range = rangeFilter.value;
                this.renderBackups();
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

    // Backup Methods
    async loadBackups() {
        try {
            const tbody = document.getElementById('backups-table-body');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                `;
            }

            const response = await apiHelper.get('/api/v1/logs/backups');
            if (!response.ok) throw new Error('Failed to load backups');

            const data = await response.json();
            this.backups = data.data;
            this.renderBackups();

        } catch (error) {
            console.error('Error loading backups:', error);
            this.showBackupError('Failed to load backups. Please try again.');
        }
    }

    renderBackups() {
        const tbody = document.getElementById('backups-table-body');
        if (!tbody) return;

        let filtered = this.backups;

        // Apply Search
        if (this.backupFilters.search) {
            const search = this.backupFilters.search.toLowerCase();
            filtered = filtered.filter(b => 
                b.filename.toLowerCase().includes(search) || 
                b.date_folder.toLowerCase().includes(search)
            );
        }

        // Apply Range Filter
        if (this.backupFilters.range) {
            const days = parseInt(this.backupFilters.range);
            const cutoff = new Date();
            cutoff.setDate(cutoff.getDate() - days);
            
            filtered = filtered.filter(b => new Date(b.created_at) >= cutoff);
        }

        document.getElementById('backup-count-info').textContent = `${filtered.length} Backups found`;

        if (filtered.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <div class="empty-state">
                            <i class="ti tabler-database-off" style="font-size: 3rem; color: #ccc;"></i>
                            <div class="mt-2 text-muted">No backups found</div>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = filtered.map(b => `
            <tr>
                <td>
                    <span class="badge bg-label-primary">${this.escapeHtml(b.date_folder)}</span>
                </td>
                <td>
                    <span class="fw-semibold">${this.escapeHtml(b.filename)}</span>
                </td>
                <td>
                    <small class="text-muted">${(b.size_bytes / 1024).toFixed(2)} KB</small>
                </td>
                <td>
                    <small>${this.formatDateTime(b.created_at)}</small>
                </td>
                <td class="text-end">
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-success" onclick="logsManagement.restoreBackup('${b.path}')" title="Restore">
                            <i class="ti tabler-restore"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary" onclick="logsManagement.downloadBackup('${b.filename}')" title="Download">
                            <i class="ti tabler-download"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    async createBackup() {
        if (!confirm('Are you sure you want to create a full backup of all activity logs?')) return;

        const btn = document.querySelector('button[onclick="logsManagement.createBackup()"]');
        const originalHtml = btn.innerHTML;
        
        try {
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';
            btn.disabled = true;

            const response = await apiHelper.post('/api/v1/logs/backups');
            const data = await response.json();

            if (response.ok) {
                Swal.fire({
                    icon: 'success',
                    title: 'Backup Created',
                    text: 'Logs have been successfully backed up to storage.',
                    confirmButtonColor: '#3085d6'
                });
                this.loadBackups();
            } else {
                throw new Error(data.message || 'Backup failed');
            }
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        } finally {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    }

    createBackup() {
        let modalElement = document.getElementById('createBackupConfirmModal');
        let modal = bootstrap.Modal.getInstance(modalElement);
        if (!modal) {
            modal = new bootstrap.Modal(modalElement);
        }
        modal.show();
    }

    async confirmCreateBackup() {
        const confirmBtn = document.getElementById('confirm-create-backup-btn');
        const modalElement = document.getElementById('createBackupConfirmModal');
        const modal = bootstrap.Modal.getInstance(modalElement);

        try {
            // Disable button and show loading state
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';

            const response = await apiHelper.post('/api/v1/logs/backups');
            const data = await response.json();

            if (response.ok) {
                if (modal) modal.hide();
                
                await Swal.fire({
                    icon: 'success',
                    title: 'Backup Created',
                    text: 'System activity logs have been backed up successfully.',
                    footer: `File: <code class="ms-1">${data.filename || 'Backup Created'}</code>`,
                    customClass: {
                        confirmButton: 'btn btn-primary px-4'
                    },
                    buttonsStyling: false
                });
                
                // Refresh backups list
                this.loadBackups();
            } else {
                throw new Error(data.message || 'Backup failed');
            }
        } catch (error) {
            console.error('Create Backup Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Operation Failed',
                text: error.message,
                customClass: {
                    confirmButton: 'btn btn-primary px-4'
                },
                buttonsStyling: false
            });
        } finally {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="ti tabler-database-export me-2"></i>Create Backup';
        }
    }

    async restoreBackup(path) {
        const result = await Swal.fire({
            title: 'Restore Logs?',
            text: "This will add the logs from the backup file into your current activity logs. No existing data will be deleted.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Restore it!'
        });

        if (!result.isConfirmed) return;

        try {
            Swal.fire({
                title: 'Restoring...',
                text: 'Please wait while we merge the log data.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const response = await apiHelper.post('/api/v1/logs/backups/restore', { file: path });
            const data = await response.json();

            if (response.ok) {
                Swal.fire('Restored!', 'Activity logs have been successfully restored.', 'success');
                this.loadLogs(); // Refresh logs if we're on that tab
            } else {
                throw new Error(data.message || 'Restore failed');
            }
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    }

    downloadBackup(filename) {
        const token = localStorage.getItem('token');
        window.location.href = `/api/v1/logs/backups/download/${filename}?token=${token}`;
    }

    clearAllLogs() {
        let modalElement = document.getElementById('clearLogsConfirmModal');
        let modal = bootstrap.Modal.getInstance(modalElement);
        if (!modal) {
            modal = new bootstrap.Modal(modalElement);
        }
        
        document.getElementById('clear-logs-password').value = '';
        document.getElementById('clear-logs-password').classList.remove('is-invalid');
        modal.show();
    }

    async confirmClearLogs() {
        const passwordInput = document.getElementById('clear-logs-password');
        const password = passwordInput.value;
        const confirmBtn = document.getElementById('confirm-clear-logs-btn');
        const modalElement = document.getElementById('clearLogsConfirmModal');
        const modal = bootstrap.Modal.getInstance(modalElement);

        if (!password) {
            passwordInput.classList.add('is-invalid');
            return;
        }

        try {
            // Disable button and show loading state
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

            const response = await apiHelper.delete('/api/v1/logs/clear', { password });
            const data = await response.json();

            if (response.ok) {
                // Hide modal first
                if (modal) modal.hide();
                
                // Construct footer safely
                let footerHtml = '';
                if (data.output && typeof data.output === 'string') {
                    const parts = data.output.split(':');
                    if (parts.length > 1) {
                        footerHtml = `Backup created: <code class="ms-1">${parts.pop().trim()}</code>`;
                    }
                }

                await Swal.fire({
                    icon: 'success',
                    title: 'System Cleared',
                    text: 'All logs have been backed up and deleted successfully.',
                    footer: footerHtml,
                    customClass: {
                        confirmButton: 'btn btn-primary px-4'
                    },
                    buttonsStyling: false
                });
                
                // Refresh everything using the global instance to be safe
                logsManagement.loadLogs();
                logsManagement.loadStats();
                logsManagement.loadBackups();
            } else {
                if (response.status === 401) {
                    passwordInput.classList.add('is-invalid');
                    document.getElementById('clear-logs-password-error').textContent = data.message || 'Invalid password';
                } else {
                    throw new Error(data.message || 'Operation failed');
                }
            }
        } catch (error) {
            console.error('Clear Logs Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Operation Failed',
                text: error.message,
                customClass: {
                    confirmButton: 'btn btn-primary px-4'
                },
                buttonsStyling: false
            });
        } finally {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="ti tabler-trash me-2"></i>Delete';
        }
    }

    clearBackupFilters() {
        this.backupFilters = {
            search: '',
            range: ''
        };

        const backupSearch = document.getElementById('backup-search');
        const rangeFilter = document.getElementById('backup-range-filter');

        if (backupSearch) backupSearch.value = '';
        if (rangeFilter) rangeFilter.value = '';

        this.renderBackups();
    }

    showBackupError(message) {
        const tbody = document.getElementById('backups-table-body');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <div class="alert alert-danger">${message}</div>
                    </td>
                </tr>
            `;
        }
    }
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
            'Warning': 'warning',
            'Clear': 'warning'
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
