<!-- Logs Management Content -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">
            <i class="ti tabler-file-analytics me-2"></i>Activity Logs
        </h4>
        <p class="text-muted mb-0">Monitor and manage system activities</p>
    </div>
    <div id="logs-actions">
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown"
                aria-expanded="false">
                <i class="ti tabler-download me-1"></i>Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end"
                style="border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                <li>
                    <a class="dropdown-item d-flex align-items-center py-2" href="javascript:void(0)"
                        onclick="logsManagement.exportLogs('excel')">
                        <i class="ti tabler-file-spreadsheet text-success me-2 fs-5"></i>
                        <span>Export to Excel</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center py-2" href="javascript:void(0)"
                        onclick="logsManagement.exportLogs('pdf')">
                        <i class="ti tabler-file-type-pdf text-danger me-2 fs-5"></i>
                        <span>Export to PDF</span>
                    </a>
                </li>
            </ul>
        </div>
        <button class="btn btn-danger ms-2" onclick="logsManagement.clearAllLogs()">
            <i class="ti tabler-trash me-1"></i>Clear All
        </button>
        <button class="btn btn-primary ms-2" onclick="logsManagement.loadLogs()">
            <i class="ti tabler-refresh me-1"></i>Refresh
        </button>
    </div>
    <div id="backups-actions" class="d-none">
        <button class="btn btn-success" onclick="logsManagement.createBackup()">
            <i class="ti tabler-database-export me-1"></i>Create New Backup
        </button>
    </div>
</div>

<!-- Clear Logs Confirmation Modal -->
<div class="modal fade" id="clearLogsConfirmModal" tabindex="-1" aria-labelledby="clearLogsConfirmModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title" id="clearLogsConfirmModalLabel">
                    <i class="ti tabler-alert-triangle me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning mb-4 border-0 shadow-sm" style="background-color: #fff4e5;">
                    <div class="d-flex">
                        <i class="ti tabler-alert-circle me-2 fs-4 text-warning"></i>
                        <div>
                            <strong class="text-warning">Warning:</strong> This action cannot be undone. All activity logs will be permanently deleted from the active database.
                        </div>
                    </div>
                </div>

                <p class="text-muted mb-4 px-1">Are you sure you want to delete all activity logs? A full backup will be automatically created before deletion.</p>

                <div class="mb-0">
                    <div class="alert alert-info mb-3 border-0 shadow-sm" style="background-color: #e6f7ff;">
                        <div class="d-flex">
                            <i class="ti tabler-lock me-2 fs-4 text-info"></i>
                            <div class="text-info">
                                <strong>Security:</strong> Super Admin password required.
                            </div>
                        </div>
                    </div>
                    <label class="form-label fw-bold text-dark">Enter Super Admin password to confirm:</label>
                    <input type="password" id="clear-logs-password" class="form-control form-control-lg border-2"
                        placeholder="Super Admin password" autocomplete="new-password" style="border-radius: 12px;">
                    <div class="invalid-feedback" id="clear-logs-password-error">Invalid password</div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-label-secondary btn-lg border-0 shadow-none px-4" data-bs-dismiss="modal" style="border-radius: 12px;">Cancel</button>
                <button type="button" class="btn btn-danger btn-lg shadow-sm px-4" id="confirm-clear-logs-btn"
                    onclick="logsManagement.confirmClearLogs()" style="border-radius: 12px;">
                    <i class="ti tabler-trash me-2"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Create Backup Confirmation Modal -->
<div class="modal fade" id="createBackupConfirmModal" tabindex="-1" aria-labelledby="createBackupConfirmModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title" id="createBackupConfirmModalLabel">
                    <i class="ti tabler-database-export me-2"></i>Create New Backup
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-success mb-4 border-0 shadow-sm" style="background-color: #e6fffa;">
                    <div class="d-flex">
                        <i class="ti tabler-circle-check me-2 fs-4 text-success"></i>
                        <div>
                            <strong class="text-success">Info:</strong> Creating a new backup will save a snapshot of all current activity logs into the local storage.
                        </div>
                    </div>
                </div>

                <p class="text-dark fw-semibold mb-2">Proceed with manual backup?</p>
                <p class="text-muted mb-0 px-0">This process will run in the background and may take a few seconds depending on the number of logs.</p>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-label-secondary btn-lg border-0 shadow-none px-4" data-bs-dismiss="modal" style="border-radius: 12px;">Cancel</button>
                <button type="button" class="btn btn-success btn-lg shadow-sm px-4" id="confirm-create-backup-btn"
                    onclick="logsManagement.confirmCreateBackup()" style="border-radius: 12px;">
                    <i class="ti tabler-database-export me-2"></i>Create Backup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-pills mb-4" id="logsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="live-logs-tab" data-bs-toggle="pill" data-bs-target="#live-logs" type="button" role="tab">
            <i class="ti tabler-broadcast me-1"></i>Live Logs
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="backups-tab" data-bs-toggle="pill" data-bs-target="#backups" type="button" role="tab">
            <i class="ti tabler-archive me-1"></i>Backups
        </button>
    </li>
</ul>

<div class="tab-content p-0" id="logsTabsContent">
    <!-- Tab 1: Live Logs -->
    <div class="tab-pane fade show active" id="live-logs" role="tabpanel">
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <div class="avatar avatar-lg bg-label-primary me-3">
                            <i class="ti tabler-list fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-0" id="total-logs">0</h4>
                            <span class="text-muted">Total Logs</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <div class="avatar avatar-lg bg-label-success me-3">
                            <i class="ti tabler-calendar fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-0" id="today-logs">0</h4>
                            <span class="text-muted">Today</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <div class="avatar avatar-lg bg-label-danger me-3">
                            <i class="ti tabler-alert-circle fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-0" id="error-logs">0</h4>
                            <span class="text-muted">Errors</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <div class="avatar avatar-lg bg-label-warning me-3">
                            <i class="ti tabler-alert-triangle fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-0" id="warning-logs">0</h4>
                            <span class="text-muted">Warnings</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" id="log-search" placeholder="Search logs...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Module</label>
                        <select class="form-select" id="module-filter">
                            <option value="">All Modules</option>
                            <option value="Auth">Auth</option>
                            <option value="Device">Device</option>
                            <option value="Machine">Machine</option>
                            <option value="System">System</option>
                            <option value="Transaction">Transaction</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Action</label>
                        <select class="form-select" id="action-filter">
                            <option value="">All Actions</option>
                            <option value="Login">Login</option>
                            <option value="Logout">Logout</option>
                            <option value="Create">Create</option>
                            <option value="Update">Update</option>
                            <option value="Delete">Delete</option>
                            <option value="Error">Error</option>
                            <option value="Warning">Warning</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date-from">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" id="date-to">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button class="btn btn-secondary w-100" onclick="logsManagement.clearFilters()">
                            <i class="ti tabler-x"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Module</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Browser</th>
                                <th>Platform</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody id="logs-table-body">
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted" id="logs-info">
                        Showing 0 to 0 of 0 entries
                    </div>
                    <nav>
                        <ul class="pagination mb-0" id="logs-pagination"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 2: Backups -->
    <div class="tab-pane fade" id="backups" role="tabpanel">
        <!-- Backup Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Search Backups</label>
                        <input type="text" class="form-control" id="backup-search"
                            placeholder="Search by filename or date...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Time Range</label>
                        <select class="form-select" id="backup-range-filter">
                            <option value="">Any Time</option>
                            <option value="1">Last 24 Hours</option>
                            <option value="3">Last 3 Days</option>
                            <option value="7">Last 7 Days</option>
                            <option value="30">Last Month</option>
                            <option value="365">Last Year</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-secondary w-100" onclick="logsManagement.clearBackupFilters()">
                            <i class="ti tabler-x me-1"></i>Clear
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backups Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Storage: <code class="fs-6">storage/app/private/logs/backups/</code></h5>
                <span class="badge bg-label-secondary" id="backup-count-info">0 Backups found</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Backup Date</th>
                                <th>Filename</th>
                                <th>Size</th>
                                <th>Created At</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="backups-table-body">
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>