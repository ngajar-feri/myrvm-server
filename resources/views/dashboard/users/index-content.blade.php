<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ti tabler-users me-2"></i>User & Tenants Management
                </h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-danger d-none" id="delete-selected-btn"
                        onclick="userManagement.showBulkDeleteModal()">
                        <i class="ti tabler-trash me-1"></i>Delete Selected (<span id="selected-count">0</span>)
                    </button>
                    <div class="btn-group d-none" id="status-selected-btn">
                        <button type="button" class="btn btn-outline-warning dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="ti tabler-toggle-left me-1"></i>Change Status (<span
                                id="status-selected-count">0</span>)
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item cursor-pointer"
                                    onclick="userManagement.showBulkStatusModal('active')">
                                    <i class="ti tabler-check me-2 text-success"></i>Set Active
                                </a></li>
                            <li><a class="dropdown-item cursor-pointer"
                                    onclick="userManagement.showBulkStatusModal('inactive')">
                                    <i class="ti tabler-x me-2 text-danger"></i>Set Inactive
                                </a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-outline-primary"
                        onclick="window.spaNavigator.loadPage('assignments', '/dashboard/assignments')">
                        <i class="ti tabler-subtask me-1"></i>Assignments
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#createUserModal">
                        <i class="ti tabler-plus me-1"></i>Add User
                    </button>
                </div>
            </div>

            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="search-input-wrapper">
                            <i class="ti tabler-search search-icon"></i>
                            <input type="text" id="user-search" class="form-control" placeholder="Search users...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select id="role-filter" class="form-select">
                            <option value="">All Roles</option>
                            <option value="super_admin">Super Admin</option>
                            <option value="admin">Admin</option>
                            <option value="operator">Operator</option>
                            <option value="user">User</option>
                            <option value="tenan">Tenant</option>
                            <option value="teknisi">Technician</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="status-filter" class="form-select">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-label-secondary w-100"
                            onclick="userManagement.refreshData()">
                            <i class="ti tabler-refresh"></i>
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="card stats-card stats-card-primary mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-2">
                                        <span class="avatar-initial rounded bg-label-primary">
                                            <i class="ti tabler-users"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h5 class="mb-0" id="total-users">0</h5>
                                        <small>Total Users</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card stats-card stats-card-success mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-2">
                                        <span class="avatar-initial rounded bg-label-success">
                                            <i class="ti tabler-check"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h5 class="mb-0" id="active-users">0</h5>
                                        <small>Active</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card stats-card stats-card-warning mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-2">
                                        <span class="avatar-initial rounded bg-label-warning">
                                            <i class="ti tabler-building-store"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h5 class="mb-0" id="total-tenants">0</h5>
                                        <small>Tenants</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card stats-card stats-card-danger mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-2">
                                        <span class="avatar-initial rounded bg-label-danger">
                                            <i class="ti tabler-user-plus"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h5 class="mb-0" id="new-today">0</h5>
                                        <small>New Today</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="40">
                                    <input type="checkbox" class="form-check-input" id="select-all">
                                </th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Points</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th width="100">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body">
                            <!-- Skeleton loader -->
                            <tr>
                                <td colspan="8">
                                    <div class="skeleton skeleton-card"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav aria-label="Users pagination" class="mt-3">
                    <ul class="pagination justify-content-end" id="users-pagination">
                        <!-- Dynamic pagination -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- User Detail Modal -->
<div class="modal fade" id="userDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="user-detail-content">
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="create-user-form">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                            <option value="operator">Operator</option>
                            <option value="tenan">Tenant</option>
                            <option value="teknisi">Technician</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Initial Points</label>
                        <input type="number" name="points_balance" class="form-control" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="edit-user-form">
                <input type="hidden" name="user_id" id="edit-user-id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="edit-user-name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit-user-email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password <small class="text-muted">(leave blank to keep
                                current)</small></label>
                        <input type="password" name="password" class="form-control"
                            placeholder="Leave blank to keep current password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" id="edit-user-role" class="form-select" required>
                            <option value="user">User</option>
                            <option value="super_admin">Super Admin</option>
                            <option value="admin">Admin</option>
                            <option value="operator">Operator</option>
                            <option value="tenan">Tenant</option>
                            <option value="teknisi">Technician</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Points Balance</label>
                        <input type="number" name="points_balance" id="edit-user-points" class="form-control" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assignment Modal (Bio-Digital Minimalism 2026 - 2 Step Wizard) -->
<style>
    /* Bio-Digital Minimalism Styles */
    .modal-minimalist .modal-dialog {
        max-width: 600px !important;
    }

    .modal-minimalist .modal-content {
        border-radius: 20px;
        border: none;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
    }

    .modal-minimalist .modal-header {
        border-bottom: none;
        padding: 28px 28px 0;
    }

    .modal-minimalist .modal-body {
        padding: 20px 28px;
    }

    .modal-minimalist .modal-footer {
        border-top: none;
        padding: 0 28px 28px;
    }

    .modal-minimalist .modal-title {
        font-weight: 700;
        font-size: 1.25rem;
        color: #1f2937;
    }

    .modal-minimalist .btn-close {
        opacity: 0.4;
    }

    .modal-minimalist .btn-close:hover {
        opacity: 1;
    }

    /* Step Indicator */
    .step-indicator {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-bottom: 24px;
    }

    .step-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #e5e7eb;
        transition: all 0.3s ease;
    }

    .step-dot.active {
        background: #7c3aed;
        transform: scale(1.2);
    }

    .step-dot.completed {
        background: #7c3aed;
    }

    /* Step Content */
    .step-content {
        display: none;
    }

    .step-content.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Form Labels */
    .modal-minimalist .step-title {
        font-weight: 700;
        font-size: 1.1rem;
        color: #1f2937;
        margin-bottom: 16px;
    }

    .modal-minimalist .form-label {
        font-weight: 600;
        color: #374151;
        font-size: 0.875rem;
        margin-bottom: 6px;
    }

    .modal-minimalist .form-control,
    .modal-minimalist .form-select {
        border-radius: 12px;
        border: 2px solid #e5e7eb;
        padding: 12px 16px;
        font-size: 0.95rem;
        transition: border-color 0.2s;
    }

    .modal-minimalist .form-control:focus,
    .modal-minimalist .form-select:focus {
        border-color: #7c3aed;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    }

    .modal-minimalist .form-control::placeholder {
        color: #9ca3af;
    }

    .modal-minimalist .form-text {
        color: #6b7280;
        font-size: 0.8rem;
    }

    /* Buttons */
    .modal-minimalist .btn-wizard {
        width: 100%;
        padding: 14px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1rem;
    }

    .modal-minimalist .btn-primary {
        background: #7c3aed;
        border-color: #7c3aed;
    }

    .modal-minimalist .btn-primary:hover {
        background: #6d28d9;
        border-color: #6d28d9;
    }

    .modal-minimalist .btn-outline-secondary {
        border: 2px solid #e5e7eb;
        color: #6b7280;
    }

    .modal-minimalist .btn-outline-secondary:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
    }

    /* Wizard Primary Button */
    .modal-minimalist .btn-wizard-primary {
        background: linear-gradient(135deg, #7c3aed, #6d28d9);
        border: none;
        border-radius: 12px;
        padding: 14px 24px;
        color: white;
        font-weight: 600;
        font-size: 0.95rem;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
    }

    .modal-minimalist .btn-wizard-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(124, 58, 237, 0.3);
        color: white;
    }

    .modal-minimalist .btn-wizard-primary:disabled {
        opacity: 0.7;
        transform: none;
    }

    /* Wizard Secondary Button */
    .modal-minimalist .btn-wizard-secondary {
        background: #f3f4f6;
        border: none;
        border-radius: 12px;
        padding: 14px 24px;
        color: #374151;
        font-weight: 600;
        font-size: 0.95rem;
        width: 100%;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .modal-minimalist .btn-wizard-secondary:hover {
        background: #e5e7eb;
    }

    .modal-minimalist .map-container,
    .modal-minimalist #assignment-map {
        height: 160px;
        border-radius: 12px;
        border: 2px solid #e5e7eb;
    }

    /* Coordinates Box */
    .modal-minimalist .coords-box {
        background: #f9fafb;
        border-radius: 12px;
        padding: 12px;
        margin-top: 12px;
    }

    .modal-minimalist .coords-box .form-control {
        background: white;
        padding: 8px 12px;
        font-size: 0.85rem;
    }

    .modal-minimalist .coords-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #9ca3af;
        font-weight: 600;
    }

    /* Coordinates Grid */
    .modal-minimalist .coords-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .modal-minimalist .coords-grid input {
        font-size: 0.8rem;
        padding: 8px 12px;
    }

    /* Button Group */
    .modal-minimalist .btn-group-wizard {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    /* Tag Input Container */
    .modal-minimalist .tag-input-container {
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        padding: 6px 10px;
        min-height: 38px;
        position: relative;
        transition: border-color 0.2s;
    }

    .modal-minimalist .tag-input-container:focus-within {
        border-color: #7c3aed;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    }

    .modal-minimalist .tag-list {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 4px;
    }

    .modal-minimalist .tag-item {
        background: linear-gradient(135deg, #7c3aed, #6d28d9);
        color: white;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .modal-minimalist .tag-item .tag-remove {
        cursor: pointer;
        opacity: 0.8;
    }

    .modal-minimalist .tag-item .tag-remove:hover {
        opacity: 1;
    }

    .modal-minimalist .tag-input-container input {
        border: none;
        outline: none;
        width: 100%;
        padding: 4px 0;
        font-size: 0.9rem;
        background: transparent;
    }

    .modal-minimalist .autocomplete-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        margin-top: 4px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .modal-minimalist .autocomplete-item {
        padding: 10px 14px;
        cursor: pointer;
        transition: background 0.2s;
    }

    .modal-minimalist .autocomplete-item:hover {
        background: #f3f4f6;
    }

    /* ========== COMPACT FORM STYLING ========== */
    .modal-minimalist .compact-form {
        padding: 16px 20px;
    }

    .modal-minimalist .step-indicator {
        margin-bottom: 16px;
    }

    .modal-minimalist .step-title {
        font-size: 1rem;
        margin-bottom: 12px;
    }

    /* Compact Form Groups */
    .modal-minimalist .form-group-compact {
        margin-bottom: 10px;
    }

    .modal-minimalist .compact-label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6b7280;
        margin-bottom: 4px;
        line-height: 1.2;
    }

    .modal-minimalist .compact-input {
        padding: 8px 10px;
        font-size: 0.85rem;
        border-radius: 8px;
    }

    .modal-minimalist .compact-input:focus {
        border-color: #7c3aed;
        box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.1);
    }

    /* Search Row */
    .modal-minimalist .search-row {
        display: flex;
        gap: 8px;
        margin-bottom: 10px;
    }

    .modal-minimalist .search-row input {
        flex: 1;
    }

    .modal-minimalist .btn-search {
        background: #7c3aed;
        border: none;
        border-radius: 8px;
        padding: 8px 14px;
        color: white;
        cursor: pointer;
        transition: background 0.2s;
    }

    .modal-minimalist .btn-search:hover {
        background: #6d28d9;
    }

    /* Compact Map */
    .modal-minimalist .compact-map {
        height: 140px;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        margin-bottom: 10px;
    }

    /* Details Grid */
    .modal-minimalist .details-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .modal-minimalist .grid-full {
        grid-column: span 2;
    }

    .modal-minimalist .grid-half {
        grid-column: span 1;
    }

    .modal-minimalist .details-grid textarea {
        resize: none;
    }

    /* Compact Footer */
    .modal-minimalist .compact-footer {
        padding: 12px 20px 16px;
    }

    .modal-minimalist .btn-row {
        width: 100%;
    }

    .modal-minimalist .btn-row-split {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .modal-minimalist .btn-row .btn {
        padding: 10px 16px;
        font-size: 0.9rem;
    }
</style>

<div class="modal fade modal-minimalist" id="assignmentModal" tabindex="-1" aria-labelledby="assignmentModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignmentModalLabel">RVM Installation Assignment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body compact-form">
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step-dot active" data-step="1"></div>
                    <div class="step-dot" data-step="2"></div>
                </div>

                <!-- Step 1: Select Assignment -->
                <div class="step-content active" id="assignment-step-1">
                    <h6 class="step-title">1. Select Assignment</h6>

                    <div class="form-group-compact">
                        <label class="compact-label">Technician</label>
                        <div class="tag-input-container">
                            <div id="selected-users" class="tag-list"></div>
                            <input type="text" id="user-search-input" placeholder="Search technician..."
                                autocomplete="off">
                            <div id="user-suggestions" class="autocomplete-dropdown" style="display:none;"></div>
                        </div>
                    </div>

                    <div class="form-group-compact">
                        <label class="compact-label">RVM Machine</label>
                        <div class="tag-input-container">
                            <div id="selected-machines" class="tag-list"></div>
                            <input type="text" id="machine-search-input" placeholder="Search machine..."
                                autocomplete="off">
                            <div id="machine-suggestions" class="autocomplete-dropdown" style="display:none;"></div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Set Location -->
                <div class="step-content" id="assignment-step-2">
                    <h6 class="step-title">2. Set Location</h6>

                    <!-- Search -->
                    <div class="search-row">
                        <input type="text" id="location-search" class="form-control compact-input"
                            placeholder="Search location...">
                        <button class="btn-search" type="button" id="search-location-btn">
                            <i class="ti tabler-search"></i>
                        </button>
                    </div>

                    <!-- Map -->
                    <div id="assignment-map" class="compact-map"></div>

                    <!-- Details Grid -->
                    <div class="details-grid">
                        <div class="grid-full">
                            <label class="compact-label">ADDRESS</label>
                            <input type="text" id="assignment-address" class="form-control compact-input" readonly
                                placeholder="Click map...">
                        </div>
                        <!-- Hidden LAT/LNG -->
                        <input type="hidden" id="assignment-lat">
                        <input type="hidden" id="assignment-lng">
                        <div class="grid-full">
                            <label class="compact-label">NOTES</label>
                            <textarea id="assignment-notes" class="form-control compact-input" rows="2"
                                placeholder="Optional..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer compact-footer">
                <!-- Step 1 Buttons -->
                <div id="step-1-buttons" class="btn-row">
                    <button type="button" class="btn btn-wizard-primary" onclick="userManagement.goToStep(2)">
                        Next <i class="ti tabler-arrow-right"></i>
                    </button>
                </div>

                <!-- Step 2 Buttons -->
                <div id="step-2-buttons" class="btn-row btn-row-split" style="display:none;">
                    <button type="button" class="btn btn-wizard-secondary" onclick="userManagement.goToStep(1)">
                        <i class="ti tabler-arrow-left"></i> Back
                    </button>
                    <button type="button" class="btn btn-wizard-primary" id="submit-assignment-btn"
                        onclick="userManagement.submitAssignment()">
                        <i class="ti tabler-check"></i> Create
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmModalLabel">
                    <i class="ti tabler-alert-triangle me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning mb-3">
                    <i class="ti tabler-alert-circle me-1"></i>
                    <strong>Warning:</strong> This action cannot be undone.
                </div>

                <p id="delete-confirm-message">Are you sure you want to delete this user?</p>

                <div class="mb-3">
                    <div class="alert alert-info mb-3">
                        <i class="ti tabler-lock me-1"></i>
                        <strong>Security:</strong> Super Admin password required.
                    </div>
                    <label class="form-label fw-semibold">Enter Super Admin password to confirm:</label>
                    <input type="password" id="delete-confirm-password" class="form-control"
                        placeholder="Super Admin password" autocomplete="new-password">
                    <div class="invalid-feedback" id="password-error">Invalid password</div>
                </div>

                <input type="hidden" id="delete-user-ids" value="">
                <input type="hidden" id="delete-mode" value="single">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn"
                    onclick="userManagement.confirmDelete()">
                    <i class="ti tabler-trash me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Status Toggle Confirmation Modal -->
<div class="modal fade" id="statusConfirmModal" tabindex="-1" aria-labelledby="statusConfirmModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="statusConfirmModalLabel">
                    <i class="ti tabler-toggle-left me-2"></i>Confirm Status Change
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="status-confirm-message">Are you sure you want to change this user's status?</p>

                <div class="mb-3" id="status-password-section" style="display: none;">
                    <div class="alert alert-info mb-3">
                        <i class="ti tabler-lock me-1"></i>
                        <strong>Security:</strong> Super Admin password required for Operator/Teknisi.
                    </div>
                    <label class="form-label fw-semibold">Enter Super Admin password to confirm:</label>
                    <input type="password" id="status-confirm-password" class="form-control"
                        placeholder="Super Admin password" autocomplete="new-password">
                    <div class="invalid-feedback" id="status-password-error">Invalid Super Admin password</div>
                </div>

                <input type="hidden" id="status-user-ids" value="">
                <input type="hidden" id="status-new-value" value="">
                <input type="hidden" id="status-mode" value="single">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirm-status-btn"
                    onclick="userManagement.confirmStatusChange()">
                    <i class="ti tabler-toggle-left me-1"></i>Change Status
                </button>
            </div>
        </div>
    </div>
</div>