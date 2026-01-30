<!-- Logs Management Content -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">
            <i class="ti tabler-file-analytics me-2"></i>Activity Logs
        </h4>
        <p class="text-muted mb-0">Monitor system activities and events</p>
    </div>
    <div>
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
        <button class="btn btn-primary ms-2" onclick="logsManagement.loadLogs()">
            <i class="ti tabler-refresh me-1"></i>Refresh
        </button>
    </div>
</div>

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