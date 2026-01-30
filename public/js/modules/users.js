/**
 * User Management Module
 * Handles user list, filtering, CRUD operations
 */

class UserManagement {
    constructor() {
        this.currentPage = 1;
        this.perPage = 10;
        this.filters = {
            search: '',
            role: '',
            status: ''
        };
        this.bootstrapReady = false;
        this.selectedUserIds = [];
        this.currentUserRole = null;
        this.init();
    }

    init() {
        // Wait for page loaded event
        document.addEventListener('pageLoaded', (e) => {
            if (e.detail.page === 'users') {
                this.waitForBootstrap().then(() => {
                    this.setupEventListeners();
                    this.loadUsers();
                    this.loadStats();
                });
            }
        });

        // If already on users page
        if (window.location.pathname.includes('/users')) {
            this.waitForBootstrap().then(() => {
                this.setupEventListeners();
                this.loadUsers();
                this.loadStats();
            });
        }
    }

    // Wait for Bootstrap to be fully loaded
    waitForBootstrap() {
        return new Promise((resolve) => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                this.bootstrapReady = true;
                resolve();
                return;
            }

            const checkBootstrap = setInterval(() => {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    clearInterval(checkBootstrap);
                    this.bootstrapReady = true;
                    resolve();
                }
            }, 100);

            // Timeout after 5 seconds
            setTimeout(() => {
                clearInterval(checkBootstrap);
                this.bootstrapReady = true;
                resolve();
            }, 5000);
        });
    }



    // Cleanup stale modal instances and backdrops (fixes SPA navigation issues)
    cleanupModals() {
        // Remove any stale modal backdrops
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

        // Reset body styles that modals may have added
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';

        // Dispose existing modal instances to prevent conflicts
        const modalIds = ['createUserModal', 'editUserModal', 'userDetailModal'];
        modalIds.forEach(id => {
            const modalEl = document.getElementById(id);
            if (modalEl) {
                const existingModal = bootstrap.Modal.getInstance(modalEl);
                if (existingModal) {
                    try {
                        existingModal.dispose();
                    } catch (e) {
                        // Ignore dispose errors
                    }
                }
                // Reset modal classes
                modalEl.classList.remove('show');
                modalEl.style.display = '';
                modalEl.removeAttribute('aria-modal');
                modalEl.removeAttribute('role');
            }
        });
    }

    setupEventListeners() {
        // Cleanup any stale modals from previous SPA navigation
        this.cleanupModals();

        // Fetch current user role
        this.fetchCurrentUserRole();

        // Search with debounce
        const searchInput = document.getElementById('user-search');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(() => {
                this.filters.search = searchInput.value;
                this.currentPage = 1;
                this.loadUsers();
            }, 500));
        }

        // Role filter
        const roleFilter = document.getElementById('role-filter');
        if (roleFilter) {
            roleFilter.addEventListener('change', () => {
                this.filters.role = roleFilter.value;
                this.currentPage = 1;
                this.loadUsers();
            });
        }

        // Status filter
        const statusFilter = document.getElementById('status-filter');
        if (statusFilter) {
            statusFilter.addEventListener('change', () => {
                this.filters.status = statusFilter.value;
                this.currentPage = 1;
                this.loadUsers();
            });
        }

        // Create user form
        const createForm = document.getElementById('create-user-form');
        if (createForm) {
            createForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.createUser(new FormData(createForm));
            });
        }

        // Edit user form
        const editForm = document.getElementById('edit-user-form');
        if (editForm) {
            editForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.updateUser(new FormData(editForm));
            });
        }

        // Select all checkbox
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                this.toggleSelectAll(e.target.checked);
            });
        }
    }

    async fetchCurrentUserRole() {
        try {
            const response = await apiHelper.get('/api/v1/me');
            if (response.ok) {
                const result = await response.json();
                // API returns { status: 'success', data: { role: '...' } }
                this.currentUserRole = result.data?.role || null;
                console.log('Current user role:', this.currentUserRole);
            }
        } catch (error) {
            console.error('Failed to fetch current user role:', error);
        }
    }

    toggleSelectAll(checked) {
        const checkboxes = document.querySelectorAll('#users-table-body input[type="checkbox"]');
        checkboxes.forEach(cb => {
            cb.checked = checked;
        });
        this.updateSelectedUsers();
    }

    updateSelectedUsers() {
        const checkboxes = document.querySelectorAll('#users-table-body input[type="checkbox"]:checked');
        this.selectedUserIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

        // Update UI - bulk action buttons
        const deleteBtn = document.getElementById('delete-selected-btn');
        const statusBtn = document.getElementById('status-selected-btn');
        const countSpan = document.getElementById('selected-count');
        const statusCountSpan = document.getElementById('status-selected-count');

        if (deleteBtn && countSpan) {
            countSpan.textContent = this.selectedUserIds.length;
            if (this.selectedUserIds.length > 0) {
                deleteBtn.classList.remove('d-none');
            } else {
                deleteBtn.classList.add('d-none');
            }
        }

        if (statusBtn && statusCountSpan) {
            statusCountSpan.textContent = this.selectedUserIds.length;
            if (this.selectedUserIds.length > 0) {
                statusBtn.classList.remove('d-none');
            } else {
                statusBtn.classList.add('d-none');
            }
        }

        // Hide single-user action dropdowns when multi-select (>1) is active
        const singleUserActions = document.querySelectorAll('.single-user-action');
        singleUserActions.forEach(action => {
            if (this.selectedUserIds.length > 1) {
                action.style.visibility = 'hidden';
            } else {
                action.style.visibility = 'visible';
            }
        });

        // Update select-all checkbox state
        const selectAll = document.getElementById('select-all');
        const allCheckboxes = document.querySelectorAll('#users-table-body input[type="checkbox"]');
        if (selectAll && allCheckboxes.length > 0) {
            selectAll.checked = checkboxes.length === allCheckboxes.length;
            selectAll.indeterminate = checkboxes.length > 0 && checkboxes.length < allCheckboxes.length;
        }
    }

    async loadUsers() {
        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                per_page: this.perPage,
                search: this.filters.search,
                role: this.filters.role,
                status: this.filters.status
            });

            // Use apiHelper for authenticated API call
            const response = await apiHelper.get(`/api/v1/admin/users?${params}`);

            if (!response.ok) throw new Error('Failed to load users');

            const data = await response.json();
            this.renderUsers(data.data);
            this.renderPagination(data);

        } catch (error) {
            console.error('Error loading users:', error);
            this.showError('Failed to load users. Please refresh the page.');
        }
    }

    async loadStats() {
        try {
            const response = await apiHelper.get('/api/v1/admin/users/stats');

            if (!response.ok) throw new Error('Failed to load stats');

            const result = await response.json();
            const stats = result.data;

            // Update UI
            const totalEl = document.getElementById('total-users');
            const activeEl = document.getElementById('active-users');
            const tenantsEl = document.getElementById('total-tenants');
            const newEl = document.getElementById('new-today');

            if (totalEl) totalEl.textContent = stats.total || 0;
            if (activeEl) activeEl.textContent = stats.active || 0;
            if (tenantsEl) tenantsEl.textContent = stats.tenants || 0;
            if (newEl) newEl.textContent = stats.new_today || 0;

        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }

    renderUsers(users) {
        const tbody = document.getElementById('users-table-body');
        if (!tbody) return;

        if (users.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="empty-state">
                            <i class="ti tabler-users empty-state-icon"></i>
                            <div class="empty-state-title">No users found</div>
                            <p class="empty-state-text">Try adjusting your filters</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = users.map(user => `
            <tr>
                <td><input type="checkbox" class="form-check-input" value="${user.id}" onchange="userManagement.updateSelectedUsers()"></td>
                <td>
                    <div class="d-flex align-items-center">
                        <img src="${user.photo_url || '/vendor/assets/img/avatars/1.png'}" 
                             class="rounded-circle me-2" width="32" height="32">
                        <span class="fw-semibold">${this.escapeHtml(user.name)}</span>
                    </div>
                </td>
                <td>${this.escapeHtml(user.email)}</td>
                <td>
                    <span class="badge bg-label-primary">${this.escapeHtml(user.role)}</span>
                </td>
                <td>
                    <span class="badge badge-with-icon bg-label-success">
                        <i class="ti tabler-coin"></i>
                        ${user.points_balance || 0}
                    </span>
                </td>
                <td>
                    <span class="badge bg-${user.status === 'active' || !user.status ? 'success' : 'secondary'}">
                        ${user.status || 'active'}
                    </span>
                </td>
                <td>${this.formatDate(user.created_at)}</td>
                <td>
                    <div class="dropdown single-user-action">
                        <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                            <i class="ti tabler-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item cursor-pointer" onclick="userManagement.viewUser(${user.id})">
                                <i class="ti tabler-eye me-2"></i>View Details
                            </a></li>
                            <li><a class="dropdown-item cursor-pointer" onclick="userManagement.editUser(${user.id})">
                                <i class="ti tabler-edit me-2"></i>Edit
                            </a></li>
                            ${['teknisi', 'operator', 'admin'].includes(user.role) ? `
                            <li><a class="dropdown-item cursor-pointer" onclick="userManagement.openAssignment(${user.id})">
                                <i class="ti tabler-map-pin me-2"></i>Assignment
                            </a></li>
                            ` : ''}
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item cursor-pointer" onclick="userManagement.showToggleStatusModal(${user.id}, '${this.escapeHtml(user.name).replace(/'/g, "\\'")}', '${user.status || 'active'}')">
                                <i class="ti tabler-toggle-${user.status === 'inactive' ? 'right' : 'left'} me-2"></i>
                                ${user.status === 'inactive' ? 'Activate' : 'Deactivate'}
                            </a></li>
                            <li><a class="dropdown-item text-danger cursor-pointer" onclick="userManagement.showDeleteModal(${user.id}, '${this.escapeHtml(user.name).replace(/'/g, "\\'")}')">
                                <i class="ti tabler-trash me-2"></i>Delete
                            </a></li>
                        </ul>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    renderPagination(data) {
        const pagination = document.getElementById('users-pagination');
        if (!pagination || !data.last_page) return;

        let html = '';

        // Previous button
        html += `
            <li class="page-item ${data.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link cursor-pointer" onclick="userManagement.goToPage(${data.current_page - 1})">
                    <i class="ti tabler-chevron-left"></i>
                </a>
            </li>
        `;

        // Page numbers (show max 5 pages)
        const startPage = Math.max(1, data.current_page - 2);
        const endPage = Math.min(data.last_page, data.current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            html += `
                <li class="page-item ${i === data.current_page ? 'active' : ''}">
                    <a class="page-link cursor-pointer" onclick="userManagement.goToPage(${i})">${i}</a>
                </li>
            `;
        }

        // Next button
        html += `
            <li class="page-item ${data.current_page === data.last_page ? 'disabled' : ''}">
                <a class="page-link cursor-pointer" onclick="userManagement.goToPage(${data.current_page + 1})">
                    <i class="ti tabler-chevron-right"></i>
                </a>
            </li>
        `;

        pagination.innerHTML = html;
    }

    goToPage(page) {
        this.currentPage = page;
        this.loadUsers();
    }

    async viewUser(userId) {
        await this.waitForBootstrap();

        const modalEl = document.getElementById('userDetailModal');
        if (!modalEl) {
            this.showError('Modal not found');
            return;
        }

        // Cleanup any stale modals first
        this.cleanupModals();

        // Use getOrCreateInstance to avoid duplicate modal instances
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        const content = document.getElementById('user-detail-content');

        modal.show();

        content.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>';

        try {
            const response = await apiHelper.get(`/api/v1/admin/users/${userId}/stats`);

            if (!response.ok) throw new Error('Failed to load user details');

            const data = await response.json();
            const user = data.user;
            const stats = data.stats;

            content.innerHTML = `
                <div class="row">
                    <div class="col-md-4 text-center">
                        <img src="${user.photo_url || '/vendor/assets/img/avatars/1.png'}" 
                             class="rounded img-fluid mb-3" style="max-width: 200px;">
                        <h5>${this.escapeHtml(user.name)}</h5>
                        <p class="text-muted">${this.escapeHtml(user.email)}</p>
                        <span class="badge bg-primary">${this.escapeHtml(user.role)}</span>
                    </div>
                    <div class="col-md-8">
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <div class="card bg-label-primary">
                                    <div class="card-body">
                                        <h4 class="mb-0">${stats.current_balance || 0}</h4>
                                        <small>Points Balance</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-label-success">
                                    <div class="card-body">
                                        <h4 class="mb-0">${stats.total_transactions || 0}</h4>
                                        <small>Transactions</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <h6>Points History (Last 7 Days)</h6>
                                <div id="user-points-chart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Render chart if ApexCharts is available
            if (typeof ApexCharts !== 'undefined' && stats.points_history) {
                this.renderPointsChart(stats.points_history);
            }

        } catch (error) {
            console.error('Error loading user details:', error);
            content.innerHTML = '<div class="alert alert-danger">Failed to load user details</div>';
        }
    }

    renderPointsChart(data) {
        const options = {
            series: [{
                name: 'Points Earned',
                data: data.map(d => d.points || 0)
            }],
            chart: {
                type: 'area',
                height: 200,
                toolbar: { show: false }
            },
            dataLabels: { enabled: false },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            colors: ['#696cff'],
            xaxis: {
                categories: data.map(d => new Date(d.date).toLocaleDateString())
            }
        };

        const chart = new ApexCharts(document.querySelector("#user-points-chart"), options);
        chart.render();
    }

    async createUser(formData) {
        try {
            const data = {
                name: formData.get('name'),
                email: formData.get('email'),
                password: formData.get('password'),
                role: formData.get('role'),
                points_balance: parseInt(formData.get('points_balance') || 0)
            };

            const response = await apiHelper.post('/api/v1/admin/users', data);

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to create user');
            }

            this.showSuccess('User created successfully');
            const modalEl = document.getElementById('createUserModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
            document.getElementById('create-user-form')?.reset();
            this.loadUsers();
            this.loadStats();

        } catch (error) {
            console.error('Error creating user:', error);
            this.showError(error.message || 'Failed to create user');
        }
    }

    async editUser(userId) {
        await this.waitForBootstrap();

        const modalEl = document.getElementById('editUserModal');
        if (!modalEl) {
            this.showError('Edit modal not found');
            return;
        }

        // Cleanup any stale modals first
        this.cleanupModals();

        // Use getOrCreateInstance to avoid duplicate modal instances
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();



        try {
            const response = await apiHelper.get(`/api/v1/admin/users/${userId}/stats`);
            if (!response.ok) throw new Error('Failed to load user');

            const data = await response.json();
            const user = data.user;

            // Populate form fields
            document.getElementById('edit-user-id').value = user.id;
            document.getElementById('edit-user-name').value = user.name;
            document.getElementById('edit-user-email').value = user.email;
            document.getElementById('edit-user-role').value = user.role;
            document.getElementById('edit-user-points').value = user.points_balance || 0;

        } catch (error) {
            console.error('Error loading user for edit:', error);
            this.showError('Failed to load user data');
            modal.hide();
        }
    }

    async updateUser(formData) {
        const userId = formData.get('user_id');

        try {
            const data = {
                name: formData.get('name'),
                email: formData.get('email'),
                role: formData.get('role'),
                points_balance: parseInt(formData.get('points_balance') || 0)
            };

            // Only include password if provided
            const newPassword = formData.get('password');
            if (newPassword && newPassword.trim()) {
                data.password = newPassword;
            }

            const response = await apiHelper.put(`/api/v1/admin/users/${userId}`, data);

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to update user');
            }

            this.showSuccess('User updated successfully');
            const modalEl = document.getElementById('editUserModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
            this.loadUsers();
            this.loadStats();

        } catch (error) {
            console.error('Error updating user:', error);
            this.showError(error.message || 'Failed to update user');
        }
    }

    // ====== REFRESH DATA ======

    refreshData() {
        this.loadUsers();
        this.loadStats();
        this.showSuccess('Data refreshed');
    }

    // ====== DELETE FUNCTIONALITY WITH PASSWORD CONFIRMATION ======

    showDeleteModal(userId, userName = '') {
        // Check if user has permission
        if (!['super_admin', 'admin'].includes(this.currentUserRole)) {
            this.showError('You do not have permission to delete users');
            return;
        }

        // Set up modal for single delete
        document.getElementById('delete-user-ids').value = userId;
        document.getElementById('delete-mode').value = 'single';
        document.getElementById('delete-confirm-message').innerHTML =
            `Are you sure you want to delete user <strong>${userName || 'this user'}</strong>?`;
        document.getElementById('delete-confirm-password').value = '';
        document.getElementById('delete-confirm-password').classList.remove('is-invalid');

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        modal.show();
    }

    showBulkDeleteModal() {
        // Check if user has permission
        if (!['super_admin', 'admin'].includes(this.currentUserRole)) {
            this.showError('You do not have permission to delete users');
            return;
        }

        if (this.selectedUserIds.length === 0) {
            this.showError('Please select at least one user to delete');
            return;
        }

        // Set up modal for bulk delete
        document.getElementById('delete-user-ids').value = this.selectedUserIds.join(',');
        document.getElementById('delete-mode').value = 'bulk';
        document.getElementById('delete-confirm-message').innerHTML =
            `Are you sure you want to delete <strong>${this.selectedUserIds.length} user(s)</strong>?`;
        document.getElementById('delete-confirm-password').value = '';
        document.getElementById('delete-confirm-password').classList.remove('is-invalid');

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        modal.show();
    }

    async confirmDelete() {
        const password = document.getElementById('delete-confirm-password').value;
        const mode = document.getElementById('delete-mode').value;
        const userIdsStr = document.getElementById('delete-user-ids').value;

        if (!password) {
            document.getElementById('delete-confirm-password').classList.add('is-invalid');
            document.getElementById('password-error').textContent = 'Password is required';
            return;
        }

        const confirmBtn = document.getElementById('confirm-delete-btn');
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting...';

        try {
            let response;

            if (mode === 'single') {
                const userId = userIdsStr;
                response = await apiHelper.request(`/api/v1/admin/users/${userId}`, {
                    method: 'DELETE',
                    body: JSON.stringify({ password }),
                    skipAuthRedirect: true // Don't redirect on 401 - show password error instead
                });
            } else {
                const userIds = userIdsStr.split(',').map(id => parseInt(id));
                response = await apiHelper.request('/api/v1/admin/users/bulk', {
                    method: 'DELETE',
                    body: JSON.stringify({
                        user_ids: userIds,
                        password
                    }),
                    skipAuthRedirect: true // Don't redirect on 401 - show password error instead
                });
            }

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to delete user(s)');
            }

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
            if (modal) modal.hide();

            // Show success
            this.showSuccess(data.message || 'User(s) deleted successfully');

            // Reset selection
            this.selectedUserIds = [];
            document.getElementById('select-all').checked = false;
            this.updateSelectedUsers();

            // Reload data
            this.loadUsers();
            this.loadStats();

        } catch (error) {
            console.error('Error deleting user(s):', error);

            if (error.message.includes('Invalid password')) {
                document.getElementById('delete-confirm-password').classList.add('is-invalid');
                document.getElementById('password-error').textContent = 'Invalid password. Please try again.';
            } else {
                this.showError(error.message || 'Failed to delete user(s)');
            }
        } finally {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="ti tabler-trash me-1"></i>Delete';
        }
    }

    // Legacy deleteUser method - now shows modal
    async deleteUser(userId) {
        this.showDeleteModal(userId);
    }

    // ====== STATUS TOGGLE FEATURE ======

    // Show toggle status modal for single user
    showToggleStatusModal(userId, userName, currentStatus) {
        if (!['super_admin', 'admin', 'operator', 'teknisi'].includes(this.currentUserRole)) {
            this.showError('You do not have permission to change user status');
            return;
        }

        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        const actionText = newStatus === 'active' ? 'activate' : 'deactivate';

        document.getElementById('status-confirm-message').innerHTML =
            `Are you sure you want to <strong>${actionText}</strong> user <strong>${userName}</strong>?`;
        document.getElementById('status-user-ids').value = userId;
        document.getElementById('status-new-value').value = newStatus;
        document.getElementById('status-mode').value = 'single';

        // Show password field for Operator/Teknisi
        const passwordSection = document.getElementById('status-password-section');
        if (['operator', 'teknisi'].includes(this.currentUserRole)) {
            passwordSection.style.display = 'block';
        } else {
            passwordSection.style.display = 'none';
        }

        // Clear previous inputs
        document.getElementById('status-confirm-password').value = '';
        document.getElementById('status-confirm-password').classList.remove('is-invalid');

        const modal = new bootstrap.Modal(document.getElementById('statusConfirmModal'));
        modal.show();
    }

    // Show bulk status modal
    showBulkStatusModal(newStatus) {
        if (!['super_admin', 'admin', 'operator', 'teknisi'].includes(this.currentUserRole)) {
            this.showError('You do not have permission to change user status');
            return;
        }

        if (this.selectedUserIds.length === 0) {
            this.showError('Please select at least one user');
            return;
        }

        const actionText = newStatus === 'active' ? 'activate' : 'deactivate';

        document.getElementById('status-confirm-message').innerHTML =
            `Are you sure you want to <strong>${actionText}</strong> <strong>${this.selectedUserIds.length}</strong> selected user(s)?`;
        document.getElementById('status-user-ids').value = this.selectedUserIds.join(',');
        document.getElementById('status-new-value').value = newStatus;
        document.getElementById('status-mode').value = 'bulk';

        // Show password field for Operator/Teknisi
        const passwordSection = document.getElementById('status-password-section');
        if (['operator', 'teknisi'].includes(this.currentUserRole)) {
            passwordSection.style.display = 'block';
        } else {
            passwordSection.style.display = 'none';
        }

        // Clear previous inputs
        document.getElementById('status-confirm-password').value = '';
        document.getElementById('status-confirm-password').classList.remove('is-invalid');

        const modal = new bootstrap.Modal(document.getElementById('statusConfirmModal'));
        modal.show();
    }

    // Confirm status change
    async confirmStatusChange() {
        const userIdsStr = document.getElementById('status-user-ids').value;
        const newStatus = document.getElementById('status-new-value').value;
        const mode = document.getElementById('status-mode').value;
        const password = document.getElementById('status-confirm-password').value;

        // Validate password for Operator/Teknisi
        if (['operator', 'teknisi'].includes(this.currentUserRole)) {
            if (!password) {
                document.getElementById('status-confirm-password').classList.add('is-invalid');
                document.getElementById('status-password-error').textContent = 'Super Admin password is required';
                return;
            }
        }

        const confirmBtn = document.getElementById('confirm-status-btn');
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Changing...';

        try {
            let response;

            if (mode === 'single') {
                const userId = userIdsStr;
                const body = { ...(password && { password }) };
                response = await apiHelper.request(`/api/v1/admin/users/${userId}/status`, {
                    method: 'PATCH',
                    body: JSON.stringify(body),
                    skipAuthRedirect: true
                });
            } else {
                const userIds = userIdsStr.split(',').map(id => parseInt(id));
                const body = {
                    user_ids: userIds,
                    new_status: newStatus,
                    ...(password && { password })
                };
                response = await apiHelper.request('/api/v1/admin/users/status/bulk', {
                    method: 'PATCH',
                    body: JSON.stringify(body),
                    skipAuthRedirect: true
                });
            }

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to change status');
            }

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('statusConfirmModal'));
            if (modal) modal.hide();

            // Show success
            this.showSuccess(data.message || 'Status changed successfully');

            // Reset selection
            this.selectedUserIds = [];
            const selectAll = document.getElementById('select-all');
            if (selectAll) selectAll.checked = false;
            this.updateSelectedUsers();

            // Reload data
            this.loadUsers();
            this.loadStats();

        } catch (error) {
            console.error('Error changing status:', error);

            if (error.message.includes('Invalid Super Admin password')) {
                document.getElementById('status-confirm-password').classList.add('is-invalid');
                document.getElementById('status-password-error').textContent = 'Invalid Super Admin password. Please try again.';
            } else {
                this.showError(error.message || 'Failed to change status');
            }
        } finally {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="ti tabler-toggle-left me-1"></i>Change Status';
        }
    }

    // ====== ASSIGNMENT FEATURE ======

    /**
     * Current step in assignment wizard (1 or 2)
     */
    currentAssignmentStep = 1;

    /**
     * Navigate to a specific step in the assignment wizard
     */
    goToStep(step) {
        // Validate step (now only 2 steps)
        if (step < 1 || step > 2) return;

        // Optional validation before moving forward
        if (step > this.currentAssignmentStep) {
            const valid = this.validateAssignmentStep(this.currentAssignmentStep);
            if (!valid) return;
        }

        this.currentAssignmentStep = step;

        // Update step indicators
        document.querySelectorAll('.step-dot').forEach((dot, index) => {
            dot.classList.remove('active', 'completed');
            if (index + 1 < step) {
                dot.classList.add('completed');
            } else if (index + 1 === step) {
                dot.classList.add('active');
            }
        });

        // Update step content
        document.querySelectorAll('.step-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(`assignment-step-${step}`)?.classList.add('active');

        // Update buttons (only 2 steps now)
        document.getElementById('step-1-buttons').style.display = step === 1 ? 'block' : 'none';
        document.getElementById('step-2-buttons').style.display = step === 2 ? 'grid' : 'none';

        // Initialize map when entering step 2
        if (step === 2 && this.assignmentMap) {
            setTimeout(() => this.assignmentMap.invalidateSize(), 100);
        }
    }

    /**
     * Validate current step before proceeding
     */
    validateAssignmentStep(step) {
        if (step === 1) {
            // Validate both technician AND machine in step 1
            const userIds = this.userAutocomplete?.getSelectedIds() || [];
            if (userIds.length === 0) {
                this.showError('Please select at least one technician');
                return false;
            }
            const machineIds = this.machineAutocomplete?.getSelectedIds() || [];
            if (machineIds.length === 0) {
                this.showError('Please select at least one RVM machine');
                return false;
            }
        }
        return true;
    }

    async openAssignment(userId = null) {
        await this.waitForBootstrap();

        const modalEl = document.getElementById('assignmentModal');
        if (!modalEl) {
            this.showError('Assignment modal not found');
            return;
        }

        this.cleanupModals();
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        // Reset wizard to step 1
        this.currentAssignmentStep = 1;
        this.goToStep(1);

        // Initialize tag autocomplete components if not already done
        if (!this.userAutocomplete) {
            this.userAutocomplete = new TagAutocomplete(
                'user-search-input',
                'user-suggestions',
                'selected-users'
            );
        }

        if (!this.machineAutocomplete) {
            this.machineAutocomplete = new TagAutocomplete(
                'machine-search-input',
                'machine-suggestions',
                'selected-machines'
            );
        }

        // Clear previous selections
        this.userAutocomplete.clear();
        this.machineAutocomplete.clear();
        document.getElementById('assignment-lat').value = '';
        document.getElementById('assignment-lng').value = '';
        document.getElementById('assignment-address').value = '';
        document.getElementById('assignment-notes').value = '';

        // Load data
        try {
            const [users, machines] = await Promise.all([
                this.loadAssignableUsers(),
                this.loadAvailableMachines()
            ]);

            this.userAutocomplete.setItems(users);
            this.machineAutocomplete.setItems(machines);

            // Pre-select user if provided
            if (userId) {
                this.userAutocomplete.preselectById(userId);
            }
        } catch (error) {
            console.error('Failed to load assignment data:', error);
            this.showError('Failed to load users/machines data');
        }

        modal.show();

        // Initialize map after modal is shown
        modalEl.addEventListener('shown.bs.modal', () => {
            if (!this.assignmentMap) {
                this.assignmentMap = new EnhancedMapHandler(
                    'assignment-map',
                    'location-search',
                    'search-location-btn'
                );

                this.assignmentMap.setLocationCallback((location) => {
                    document.getElementById('assignment-lat').value = location.latitude.toFixed(6);
                    document.getElementById('assignment-lng').value = location.longitude.toFixed(6);
                    document.getElementById('assignment-address').value = location.address;
                });
            } else {
                this.assignmentMap.invalidateSize();
            }
        }, { once: true });
    }

    async submitAssignment() {
        const userIds = this.userAutocomplete.getSelectedIds();
        const machineIds = this.machineAutocomplete.getSelectedIds();
        const latitude = document.getElementById('assignment-lat').value;
        const longitude = document.getElementById('assignment-lng').value;
        const address = document.getElementById('assignment-address').value;
        const notes = document.getElementById('assignment-notes').value;

        // Validation
        if (userIds.length === 0) {
            this.showError('Please select at least one user');
            return;
        }

        if (machineIds.length === 0) {
            this.showError('Please select at least one machine');
            return;
        }

        // Submit button loading state
        const submitBtn = document.getElementById('submit-assignment-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';

        try {
            const rawResponse = await apiHelper.post('/api/v1/admin/assignments', {
                user_ids: userIds,
                machine_ids: machineIds,
                latitude: latitude || null,
                longitude: longitude || null,
                address: address || null,
                notes: notes || null
            });

            if (!rawResponse) throw new Error('Network response was not ok');

            const response = await rawResponse.json();

            if (response.status === 'error' || (rawResponse.status >= 400 && response.message)) {
                throw new Error(response.message || 'Failed to create assignments');
            }

            this.showSuccess(`✅ ${response.count} assignment(s) created and notifications sent!`);
            bootstrap.Modal.getInstance(document.getElementById('assignmentModal')).hide();

        } catch (error) {
            console.error('Failed to create assignments:', error);
            this.showError(error.message || 'Failed to create assignments');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    async loadAssignableUsers() {
        try {
            const rawResponse = await apiHelper.get('/api/v1/admin/users?per_page=100');
            if (!rawResponse || !rawResponse.ok) return [];

            const response = await rawResponse.json();
            console.log('Users API response:', response);

            // Handle different response structures
            let users = [];
            if (response && response.data && Array.isArray(response.data)) {
                users = response.data;
            } else if (Array.isArray(response)) {
                users = response;
            } else if (response && response.users && Array.isArray(response.users)) {
                users = response.users;
            }

            // Filter to teknisi, operator, admin roles
            const assignableRoles = ['teknisi', 'operator', 'admin'];
            return users
                .filter(u => assignableRoles.includes(u.role))
                .map(u => ({
                    id: u.id,
                    name: u.name,
                    email: u.email,
                    role: u.role
                }));
        } catch (error) {
            console.error('Failed to load users:', error);
            return [];
        }
    }

    async loadAvailableMachines() {
        try {
            const rawResponse = await apiHelper.get('/api/v1/rvm-machines');
            if (!rawResponse || !rawResponse.ok) return [];

            const response = await rawResponse.json();
            console.log('Machines API response:', response);

            // Handle different response structures
            let machines = [];
            if (response && response.data && Array.isArray(response.data)) {
                machines = response.data;
            } else if (Array.isArray(response)) {
                machines = response;
            } else if (response && response.machines && Array.isArray(response.machines)) {
                machines = response.machines;
            }

            return machines.map(m => ({
                id: m.id,
                name: m.location_name || m.name || `RVM-${m.serial_number}`,
                serial_number: m.serial_number
            }));
        } catch (error) {
            console.error('Failed to load machines:', error);
            return [];
        }
    }

    // Utility functions
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    showSuccess(message) {
        this.showToast(message, 'success');
    }

    showError(message) {
        this.showToast(message, 'danger');
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = 'position-fixed top-0 end-0 p-3';
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <div class="toast show" role="alert">
                <div class="toast-header bg-${type} text-white">
                    <i class="ti tabler-${type === 'success' ? 'check' : 'alert-circle'} me-2"></i>
                    <strong class="me-auto">${type === 'success' ? 'Success' : 'Error'}</strong>
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
}

// Initialize
const userManagement = new UserManagement();
