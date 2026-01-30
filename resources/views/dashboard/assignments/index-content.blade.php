{{-- Technician Assignments Management - Bio-Digital 2026 --}}
<style>
    /* Bio-Digital 2026 - Assignment Page Styles */
    .assignment-card {
        background: linear-gradient(to bottom right, #f0fdf4, #ffffff);
        border: 1px solid rgba(16, 185, 129, 0.15);
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .assignment-card:hover {
        box-shadow: 0 4px 20px rgba(16, 185, 129, 0.12);
        transform: translateY(-2px);
    }

    .role-badge-super_admin {
        background: linear-gradient(135deg, #7c3aed, #8b5cf6);
        color: white;
    }

    .role-badge-admin {
        background: linear-gradient(135deg, #2563eb, #3b82f6);
        color: white;
    }

    .role-badge-operator {
        background: linear-gradient(135deg, #059669, #10b981);
        color: white;
    }

    .role-badge-teknisi {
        background: linear-gradient(135deg, #d97706, #f59e0b);
        color: white;
    }

    .role-badge-tenant {
        background: linear-gradient(135deg, #dc2626, #ef4444);
        color: white;
    }

    /* Bio-Digital Modal 350px */
    .modal-add-assignment .modal-dialog {
        max-width: 350px !important;
    }

    .modal-add-assignment .modal-content {
        border-radius: 16px;
        border: none;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    }

    .modal-add-assignment .modal-header {
        border-bottom: none;
        padding: 20px 20px 0;
    }

    .modal-add-assignment .modal-body {
        padding: 16px 20px;
    }

    .modal-add-assignment .modal-footer {
        border-top: none;
        padding: 0 20px 20px;
    }

    .modal-add-assignment .modal-title {
        font-weight: 700;
        font-size: 1.1rem;
        color: #065f46;
    }

    .modal-add-assignment .form-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
    }

    .modal-add-assignment .form-select {
        border-radius: 10px;
        padding: 0.6rem 0.8rem;
    }

    .modal-add-assignment .btn-bio {
        background: linear-gradient(135deg, #10b981, #059669);
        border: none;
        border-radius: 10px;
        font-weight: 600;
    }

    .modal-add-assignment .btn-bio:hover {
        background: linear-gradient(135deg, #059669, #047857);
        transform: scale(1.02);
    }

    /* Autocomplete Dropdown Styles */
    .autocomplete-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        max-height: 200px;
        overflow-y: auto;
        z-index: 1050;
    }

    .autocomplete-item {
        padding: 10px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f3f4f6;
        transition: background 0.2s ease;
    }

    .autocomplete-item:last-child {
        border-bottom: none;
    }

    .autocomplete-item:hover,
    .autocomplete-item.active {
        background: linear-gradient(to right, #ecfdf5, #f0fdf4);
    }

    .autocomplete-item .item-name {
        font-weight: 600;
        color: #1f2937;
    }

    .autocomplete-item .item-subtitle {
        font-size: 0.75rem;
        color: #6b7280;
    }

    .autocomplete-no-results {
        padding: 12px;
        text-align: center;
        color: #9ca3af;
        font-size: 0.85rem;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ti tabler-user-check me-2"></i>Technician Assignments (Hak Akses RVM)
                </h5>
                <div>
                    <button type="button" class="btn btn-label-secondary me-2"
                        onclick="window.assignmentManager.loadAssignments()">
                        <i class="ti tabler-refresh me-1"></i>Refresh
                    </button>
                    @if(in_array(auth()->user()->role, ['super_admin', 'admin']))
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#addAssignmentModal">
                            <i class="ti tabler-plus me-1"></i>Add Assignment
                        </button>
                    @endif
                </div>
            </div>

            <div class="card-body">
                <!-- Info Alert -->
                <div class="alert mb-4"
                    style="background: linear-gradient(to right, #ecfdf5, #f0fdf4); border: 1px solid #10b981; border-radius: 10px; color: #065f46;">
                    <i class="ti tabler-info-circle me-1"></i>
                    <strong>Hak Akses Tetap:</strong> Teknisi yang di-assign ke RVM bisa melihat status, generate PIN,
                    dan menerima tiket maintenance.
                </div>

                <!-- Unified Search Filter -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent">
                                <i class="ti tabler-search"></i>
                            </span>
                            <input type="text" id="unified-search" class="form-control"
                                placeholder="Cari berdasarkan nama user, RVM, lokasi, atau role...">
                        </div>
                    </div>
                </div>

                <!-- Assignments Grid -->
                <div class="row g-3" id="assignments-grid">
                    <!-- Loading skeleton -->
                    <div class="col-md-4">
                        <div class="skeleton skeleton-card" style="height: 120px; border-radius: 12px;"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="skeleton skeleton-card" style="height: 120px; border-radius: 12px;"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="skeleton skeleton-card" style="height: 120px; border-radius: 12px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Assignment Modal - Bio-Digital 350px -->
<div class="modal fade modal-add-assignment" id="addAssignmentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti tabler-user-plus me-2"></i>Add Assignment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addAssignmentForm">
                    <!-- Step indicator -->
                    <div class="d-flex justify-content-center gap-2 mb-3">
                        <div class="step-dot active"
                            style="width: 10px; height: 10px; border-radius: 50%; background: #10b981;"></div>
                        <div class="step-dot"
                            style="width: 10px; height: 10px; border-radius: 50%; background: #e5e7eb;"></div>
                    </div>

                    <!-- Step 1: Search User -->
                    <div id="step-user" class="assignment-step">
                        <label class="form-label">Search User to Assign</label>
                        <div class="position-relative mb-3">
                            <input type="text" id="search-user" class="form-control"
                                placeholder="Type to search user..." autocomplete="off">
                            <input type="hidden" id="assign-user" required>
                            <div id="user-suggestions" class="autocomplete-dropdown d-none"></div>
                        </div>
                        <div id="user-info" class="d-none p-2 rounded mb-3"
                            style="background: #f0fdf4; border: 1px solid #10b981;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">Selected:</small>
                                    <div class="fw-bold" id="selected-user-name"></div>
                                    <span class="badge" id="selected-user-role"></span>
                                </div>
                                <button type="button" class="btn btn-sm btn-icon btn-label-danger"
                                    onclick="assignmentSearch.clearUser()">
                                    <i class="ti tabler-x"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-bio w-100 text-white"
                            onclick="assignmentWizard.nextStep()">
                            Next: Select RVM <i class="ti tabler-arrow-right ms-1"></i>
                        </button>
                    </div>

                    <!-- Step 2: Search RVM -->
                    <div id="step-rvm" class="assignment-step d-none">
                        <label class="form-label">Search RVM Machine</label>
                        <div class="position-relative mb-3">
                            <input type="text" id="search-rvm" class="form-control" placeholder="Type to search RVM..."
                                autocomplete="off">
                            <input type="hidden" id="assign-rvm" required>
                            <div id="rvm-suggestions" class="autocomplete-dropdown d-none"></div>
                        </div>
                        <div id="rvm-info" class="d-none p-2 rounded mb-3"
                            style="background: #f0fdf4; border: 1px solid #10b981;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">Selected:</small>
                                    <div class="fw-bold" id="selected-rvm-name"></div>
                                    <small id="selected-rvm-location" class="text-muted"></small>
                                </div>
                                <button type="button" class="btn btn-sm btn-icon btn-label-danger"
                                    onclick="assignmentSearch.clearRvm()">
                                    <i class="ti tabler-x"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-label-secondary flex-grow-1"
                                onclick="assignmentWizard.prevStep()">
                                <i class="ti tabler-arrow-left me-1"></i>Back
                            </button>
                            <button type="submit" class="btn btn-bio flex-grow-1 text-white" id="btn-submit-assignment">
                                <i class="ti tabler-check me-1"></i>Assign
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- API Credentials Modal - High Contrast Terminal Style -->
<div class="modal fade" id="assignmentSuccessModal" tabindex="-1" data-bs-backdrop="static" style="z-index: 1060;"
    role="dialog" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content"
            style="border-radius: 16px; border: none; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">

            <!-- Header - Success Banner -->
            <div
                style="background: linear-gradient(135deg, #10b981, #059669); padding: 16px 20px; display: flex; align-items: center; gap: 12px;">
                <div
                    style="background: rgba(255,255,255,0.2); border-radius: 50%; padding: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="ti tabler-check" style="color: white; font-size: 18px;"></i>
                </div>
                <div>
                    <h5 style="margin: 0; color: white; font-weight: 700; font-size: 16px;">Assignment Berhasil</h5>
                    <p style="margin: 0; color: rgba(255,255,255,0.8); font-size: 12px;">API Credentials untuk
                        konfigurasi Edge</p>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"
                    onclick="window.assignmentManager.loadAssignments()"></button>
            </div>

            <!-- Body -->
            <div style="padding: 24px; background: #ffffff;">

                <!-- Serial Number -->
                <div style="margin-bottom: 20px;">
                    <label
                        style="display: block; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">
                        Serial Number
                    </label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="text" id="cred-serial" readonly
                            style="flex: 1; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 14px; font-size: 18px; font-weight: 700; color: #111827; letter-spacing: 0.5px; font-family: 'Inter', system-ui, sans-serif;">
                        <button class="btn btn-sm" onclick="assignmentCredentials.copySerial()"
                            style="background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; color: #374151;">
                            <i class="ti tabler-copy"></i>
                        </button>
                    </div>
                </div>

                <!-- API Key - Terminal Style (Dark) -->
                <div style="margin-bottom: 20px;">
                    <label
                        style="display: block; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">
                        API Key
                    </label>
                    <div
                        style="background: #1e293b; border-radius: 10px; padding: 14px 16px; border: 1px solid #334155; position: relative;">
                        <code id="cred-apikey-display"
                            style="display: block; color: #4ade80; font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace; font-size: 13px; word-break: break-all; line-height: 1.6; letter-spacing: 0.5px;">
                                ••••••••••••••••••••••••••••••••
                            </code>
                        <input type="hidden" id="cred-apikey" value="">
                    </div>
                    <p style="margin: 8px 0 0 0; font-size: 12px; color: #dc2626; font-style: italic;">
                        <i class="ti tabler-alert-triangle" style="margin-right: 4px;"></i>
                        Salin kunci ini sekarang. Kunci tidak akan ditampilkan lagi.
                    </p>

                    <!-- Action Buttons -->
                    <div style="display: flex; gap: 8px; margin-top: 12px;">
                        <button class="btn btn-sm" onclick="assignmentCredentials.toggleApiKey()" id="btn-toggle-apikey"
                            style="flex: 1; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 12px; color: #475569; font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                            <i class="ti tabler-eye"></i> Show
                        </button>
                        <button class="btn btn-sm" onclick="assignmentCredentials.copyApiKey()"
                            style="flex: 1; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 12px; color: #475569; font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                            <i class="ti tabler-copy"></i> Copy
                        </button>
                    </div>
                </div>

                <!-- Download JSON Button -->
                <button class="btn w-100" onclick="assignmentCredentials.downloadJson()"
                    style="background: linear-gradient(135deg, #10b981, #059669); border: none; border-radius: 10px; padding: 12px 16px; color: white; font-weight: 600; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s;">
                    <i class="ti tabler-download"></i> Download Credentials (JSON)
                </button>

                <!-- Done Button -->
                <button type="button" class="btn btn-light w-100 mt-3" data-bs-dismiss="modal"
                    onclick="window.assignmentManager.loadAssignments()"
                    style="background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 16px; color: #374151; font-weight: 500; font-size: 14px;">
                    Selesai
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Assignment Detail Modal - Bio-Digital 350px -->
<div class="modal fade" id="assignmentDetailModal" tabindex="-1" role="dialog" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 350px;">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header"
                style="border-bottom: none; background: linear-gradient(to right, #ecfdf5, #f0fdf4);">
                <h6 class="modal-title fw-bold" style="color: #065f46;"><i
                        class="ti tabler-user-check me-2"></i>Assignment Detail</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="assignment-detail-content">
                <div class="text-center py-3">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: none;">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- PIN Generated Modal - Bio-Digital -->
<div class="modal fade" id="pinGeneratedModal" tabindex="-1" role="dialog" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 300px;">
        <div class="modal-content text-center" style="border-radius: 16px; border: none;">
            <div class="modal-body py-4">
                <div class="mb-3" style="font-size: 48px;">🔑</div>
                <h5 class="fw-bold" style="color: #065f46;">PIN Generated!</h5>
                <div class="my-3 p-3 rounded" style="background: linear-gradient(135deg, #ecfdf5, #d1fae5);">
                    <div class="display-4 fw-bold" style="color: #065f46; letter-spacing: 8px;"
                        id="generated-pin-value">------</div>
                </div>
                <p class="text-muted small mb-0">
                    <i class="ti tabler-clock me-1"></i>Expires: <span id="generated-pin-expires">-</span>
                </p>
                <button type="button" class="btn btn-bio text-white px-4 mt-3" data-bs-dismiss="modal">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Current user info for role hierarchy - Use window to prevent SPA redeclaration errors
    window.currentUserRole = '{{ auth()->user()->role }}';
    window.currentUserId = {{ auth()->id() }};

    // Role hierarchy (lower = more permissions)
    window.roleHierarchy = {
        'super_admin': 1,
        'admin': 2,
        'operator': 3,
        'teknisi': 4,
        'tenant': 5,
        'user': 6
    };

    // Assignment Manager
    window.assignmentManager = {
        assignments: [],
        users: [],
        machines: [],
        searchQuery: '',

        async init() {
            await this.loadUsers();
            await this.loadMachines();
            await this.loadAssignments();
            this.setupEventListeners();
        },

        async loadUsers() {
            try {
                const response = await apiHelper.get('/api/v1/dashboard/users');
                const data = await response.json();
                this.users = data.data || data || [];
                this.populateUserDropdowns();
            } catch (e) {
                console.error('Failed to load users:', e);
            }
        },

        async loadMachines() {
            try {
                const response = await apiHelper.get('/api/v1/dashboard/machines');
                const data = await response.json();
                this.machines = data.data || data || [];
                this.populateMachineDropdowns();
            } catch (e) {
                console.error('Failed to load machines:', e);
            }
        },

        populateUserDropdowns() {
            // Allowed roles for RVM assignment (exclude tenant and user)
            const allowedRoles = ['super_admin', 'admin', 'teknisi', 'operator'];

            // Filter users that current user can assign (role hierarchy + allowed roles)
            const assignableUsers = this.users.filter(u => {
                // First check if role is allowed
                if (!allowedRoles.includes(u.role)) return false;

                // super_admin can assign anyone (in allowed roles)
                if (window.currentUserRole === 'super_admin') return true;
                // admin can assign self, teknisi, operator (not super_admin or other admin)
                if (window.currentUserRole === 'admin') {
                    if (u.id === window.currentUserId) return true; // self
                    return window.roleHierarchy[u.role] > window.roleHierarchy['admin'];
                }
                return false;
            });

            // Store assignable users for search
            this.assignableUsers = assignableUsers;
        },

        populateMachineDropdowns() {
            // No dropdown to populate anymore - unified search handles filtering
            // Machines are stored in this.machines for search use
        },

        async loadAssignments() {
            const grid = document.getElementById('assignments-grid');
            if (!grid) return;

            grid.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div></div>';

            try {
                const response = await apiHelper.get('/api/v1/dashboard/technician-assignments');
                const data = await response.json();
                this.assignments = data.data || data || [];
                this.renderAssignments();
            } catch (e) {
                grid.innerHTML = '<div class="col-12"><div class="alert alert-danger">Failed to load assignments</div></div>';
            }
        },

        renderAssignments() {
            const grid = document.getElementById('assignments-grid');
            if (!grid) return;

            // Apply unified search filter
            const searchQuery = (this.searchQuery || '').toLowerCase().trim();
            let filtered = this.assignments;

            if (searchQuery) {
                filtered = this.assignments.filter(a => {
                    const userName = (a.user?.name || '').toLowerCase();
                    const userRole = (a.user?.role || '').toLowerCase();
                    const userEmail = (a.user?.email || '').toLowerCase();
                    const rvmName = (a.rvm_machine?.name || '').toLowerCase();
                    const rvmLocation = (a.rvm_machine?.location || '').toLowerCase();

                    return userName.includes(searchQuery) ||
                        userRole.includes(searchQuery) ||
                        userEmail.includes(searchQuery) ||
                        rvmName.includes(searchQuery) ||
                        rvmLocation.includes(searchQuery);
                });
            }

            if (filtered.length === 0) {
                const message = searchQuery
                    ? `Tidak ada assignment yang cocok dengan "${searchQuery}"`
                    : 'No assignments yet. Click "Add Assignment" to get started.';
                grid.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="ti tabler-user-off" style="font-size: 48px; color: #d1d5db;"></i>
                    <p class="text-muted mt-2">${message}</p>
                </div>`;
                return;
            }

            grid.innerHTML = filtered.map(a => `
            <div class="col-md-4">
                <div class="assignment-card p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-1 fw-bold">${a.user?.name || 'Unknown'}</h6>
                            <span class="badge role-badge-${a.user?.role || 'user'}" style="font-size: 0.7rem; padding: 4px 8px; border-radius: 6px;">
                                ${a.user?.role || 'user'}
                            </span>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-icon btn-label-primary" onclick="assignmentManager.viewAssignment(${a.id})" title="View Detail">
                                <i class="ti tabler-eye"></i>
                            </button>
                            @if(in_array(auth()->user()->role, ['super_admin', 'admin']))
                                <button class="btn btn-sm btn-icon btn-label-warning" onclick="assignmentManager.generatePin(${a.id})" title="Generate PIN">
                                    <i class="ti tabler-key"></i>
                                </button>
                                <button class="btn btn-sm btn-icon btn-label-primary" onclick="assignmentManager.regenerateApiKey(${a.rvm_machine?.id}, '${(a.rvm_machine?.name || '').replace(/'/g, "\\'")}')" title="Regenerate RVM API Key">
                                    <i class="ti tabler-refresh"></i>
                                </button>
                                <button class="btn btn-sm btn-icon btn-label-danger" onclick="assignmentManager.removeAssignment(${a.id})" title="Remove">
                                    <i class="ti tabler-trash"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <hr style="border-color: rgba(16,185,129,0.2);">
                    <div class="d-flex align-items-center">
                        <i class="ti tabler-device-desktop me-2" style="color: #10b981;"></i>
                        <div>
                            <div class="small fw-semibold">${a.rvm_machine?.name || 'Unknown RVM'}</div>
                            <small class="text-muted">${a.rvm_machine?.location || 'No location'}</small>
                        </div>
                    </div>
                    <div class="mt-2 small text-muted">
                        <i class="ti tabler-calendar me-1"></i>Assigned: ${new Date(a.assigned_at || a.created_at).toLocaleDateString()}
                    </div>
                </div>
            </div>
        `).join('');
        },

        async viewAssignment(id) {
            try {
                const response = await apiHelper.get(`/api/v1/dashboard/technician-assignments/${id}`);
                const data = await response.json();
                const a = data.data;

                // Show detail in a simple alert modal (could be enhanced later)
                const detail = `
                    <div class="text-start">
                        <dl class="row mb-0">
                            <dt class="col-4">User:</dt><dd class="col-8">${a.user?.name || 'N/A'}</dd>
                            <dt class="col-4">Email:</dt><dd class="col-8">${a.user?.email || 'N/A'}</dd>
                            <dt class="col-4">Role:</dt><dd class="col-8">${a.user?.role || 'N/A'}</dd>
                            <dt class="col-4">RVM:</dt><dd class="col-8">${a.rvm_machine?.name || 'N/A'}</dd>
                            <dt class="col-4">Location:</dt><dd class="col-8">${a.rvm_machine?.location || 'N/A'}</dd>
                            <dt class="col-4">Status:</dt><dd class="col-8">${a.status || 'assigned'}</dd>
                            <dt class="col-4">PIN:</dt><dd class="col-8">${a.access_pin || 'Not generated'}</dd>
                            <dt class="col-4">PIN Expires:</dt><dd class="col-8">${a.pin_expires_at ? new Date(a.pin_expires_at).toLocaleString() : '-'}</dd>
                            <dt class="col-4">Assigned By:</dt><dd class="col-8">${a.assigned_by || 'System'}</dd>
                            <dt class="col-4">Created:</dt><dd class="col-8">${new Date(a.created_at).toLocaleString()}</dd>
                        </dl>
                    </div>
                `;
                document.getElementById('assignment-detail-content').innerHTML = detail;

                const modalEl = document.getElementById('assignmentDetailModal');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
                // Fix: Force remove aria-hidden to prevent accessibility blocks
                modalEl.removeAttribute('aria-hidden');
            } catch (e) {
                console.error(e);
                alert('Failed to load assignment details');
            }
        },

        async generatePin(id) {
            if (!confirm('Generate a new 6-digit PIN for this assignment? The PIN will expire in 24 hours.')) return;

            try {
                const response = await apiHelper.post(`/api/v1/technician-assignments/${id}/generate-pin`);
                const data = await response.json();

                document.getElementById('generated-pin-value').textContent = data.data.pin;
                document.getElementById('generated-pin-expires').textContent = new Date(data.data.expires_at).toLocaleString();
                const pinModalEl = document.getElementById('pinGeneratedModal');
                bootstrap.Modal.getOrCreateInstance(pinModalEl).show();
                // Fix: Force remove aria-hidden
                pinModalEl.removeAttribute('aria-hidden');
            } catch (e) {
                alert('Failed to generate PIN');
            }
        },

        async removeAssignment(id) {
            if (!confirm('Remove this assignment? User will lose access to this RVM.')) return;

            try {
                await apiHelper.delete(`/api/v1/technician-assignments/${id}`);
                this.loadAssignments();
            } catch (e) {
                alert('Failed to remove assignment');
            }
        },

        async regenerateApiKey(rvmId, rvmName) {
            if (!confirm(`WARNING: Regenerating the API Key for "${rvmName}" will disconnect the Edge Device until the new key is configured. Continue?`)) return;

            try {
                const response = await apiHelper.post(`/api/v1/rvm-machines/${rvmId}/regenerate-api-key`);
                const data = await response.json();

                // Show the new key in the credentials modal
                const credResponse = await apiHelper.get(`/api/v1/rvm-machines/${rvmId}/credentials`);
                const credData = await credResponse.json();

                window.assignmentCredentials.setCredentials(
                    credData.serial_number,
                    credData.api_key,
                    credData.name,
                    rvmId
                );

                const successModalEl = document.getElementById('assignmentSuccessModal');
                bootstrap.Modal.getOrCreateInstance(successModalEl).show();
                // Fix: Force remove aria-hidden
                successModalEl.removeAttribute('aria-hidden');

            } catch (e) {
                alert('Failed to regenerate API Key');
            }
        },

        setupEventListeners() {
            // Unified search for filtering assignment cards
            const unifiedSearch = document.getElementById('unified-search');
            if (unifiedSearch) {
                let debounceTimer;
                unifiedSearch.addEventListener('input', () => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        this.searchQuery = unifiedSearch.value;
                        this.renderAssignments();
                    }, 200);
                });
            }

            // User select change
            document.getElementById('assign-user')?.addEventListener('change', function () {
                const opt = this.options[this.selectedIndex];
                const info = document.getElementById('user-info');
                if (this.value) {
                    document.getElementById('selected-user-name').textContent = opt.dataset.name;
                    const roleEl = document.getElementById('selected-user-role');
                    roleEl.textContent = opt.dataset.role;
                    roleEl.className = 'badge role-badge-' + opt.dataset.role;
                    info.classList.remove('d-none');
                } else {
                    info.classList.add('d-none');
                }
            });

            // RVM select change
            document.getElementById('assign-rvm')?.addEventListener('change', function () {
                const opt = this.options[this.selectedIndex];
                const info = document.getElementById('rvm-info');
                if (this.value) {
                    document.getElementById('selected-rvm-name').textContent = opt.textContent;
                    document.getElementById('selected-rvm-location').textContent = opt.dataset.location || '';
                    info.classList.remove('d-none');
                } else {
                    info.classList.add('d-none');
                }
            });

            // Form submit
            document.getElementById('addAssignmentForm')?.addEventListener('submit', async function (e) {
                e.preventDefault();
                const userId = document.getElementById('assign-user').value;
                const rvmId = document.getElementById('assign-rvm').value;

                if (!userId || !rvmId) {
                    alert('Please select both user and RVM');
                    return;
                }

                const btn = document.getElementById('btn-submit-assignment');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                try {
                    await apiHelper.post('/api/v1/dashboard/technician-assignments', {
                        user_id: userId,
                        rvm_machine_id: rvmId
                    });

                    // Fetch machine credentials for the newly assigned RVM
                    try {
                        const credResponse = await apiHelper.get(`/api/v1/rvm-machines/${rvmId}/credentials`);
                        const credData = await credResponse.json();
                        window.assignmentCredentials.setCredentials(
                            credData.serial_number,
                            credData.api_key,
                            credData.name,
                            rvmId
                        );
                    } catch (credErr) {
                        console.warn('Could not fetch credentials:', credErr);
                        window.assignmentCredentials.setCredentials('N/A', 'N/A', 'N/A', 'N/A');
                    }

                    bootstrap.Modal.getInstance(document.getElementById('addAssignmentModal')).hide();

                    // Small delay to ensure backdrop is cleared
                    setTimeout(() => {
                        const successModalEl = document.getElementById('assignmentSuccessModal');
                        bootstrap.Modal.getOrCreateInstance(successModalEl).show();
                        // Fix: Force remove aria-hidden
                        successModalEl.removeAttribute('aria-hidden');
                    }, 150);

                    assignmentWizard.reset();
                } catch (e) {
                    alert('Failed to create assignment. User may already be assigned to this RVM.');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ti tabler-check me-1"></i>Assign';
                }
            });
        }
    };

    // Assignment Wizard for modal steps
    window.assignmentWizard = {
        currentStep: 1,

        nextStep() {
            if (!document.getElementById('assign-user').value) {
                alert('Please select a user first');
                return;
            }
            document.getElementById('step-user').classList.add('d-none');
            document.getElementById('step-rvm').classList.remove('d-none');
            document.querySelectorAll('.step-dot')[0].style.background = '#10b981';
            document.querySelectorAll('.step-dot')[1].style.background = '#10b981';
            this.currentStep = 2;
        },

        prevStep() {
            document.getElementById('step-rvm').classList.add('d-none');
            document.getElementById('step-user').classList.remove('d-none');
            document.querySelectorAll('.step-dot')[1].style.background = '#e5e7eb';
            this.currentStep = 1;
        },

        reset() {
            this.currentStep = 1;
            document.getElementById('step-user').classList.remove('d-none');
            document.getElementById('step-rvm').classList.add('d-none');
            document.getElementById('addAssignmentForm').reset();
            document.getElementById('user-info').classList.add('d-none');
            document.getElementById('rvm-info').classList.add('d-none');
            // Clear search fields
            document.getElementById('search-user').value = '';
            document.getElementById('search-rvm').value = '';
            document.getElementById('assign-user').value = '';
            document.getElementById('assign-rvm').value = '';
            document.querySelectorAll('.step-dot')[1].style.background = '#e5e7eb';
        }
    };

    // Search/Autocomplete Handler
    window.assignmentSearch = {
        debounceTimer: null,

        init() {
            this.setupUserSearch();
            this.setupRvmSearch();
            this.setupClickOutside();
        },

        setupUserSearch() {
            const input = document.getElementById('search-user');
            const suggestions = document.getElementById('user-suggestions');
            if (!input || !suggestions) return;

            input.addEventListener('input', () => {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {
                    this.searchUsers(input.value);
                }, 200);
            });

            input.addEventListener('focus', () => {
                if (input.value.length >= 1) {
                    this.searchUsers(input.value);
                }
            });
        },

        setupRvmSearch() {
            const input = document.getElementById('search-rvm');
            const suggestions = document.getElementById('rvm-suggestions');
            if (!input || !suggestions) return;

            input.addEventListener('input', () => {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {
                    this.searchRvm(input.value);
                }, 200);
            });

            input.addEventListener('focus', () => {
                if (input.value.length >= 1) {
                    this.searchRvm(input.value);
                }
            });
        },

        setupClickOutside() {
            document.addEventListener('click', (e) => {
                const userSuggestions = document.getElementById('user-suggestions');
                const rvmSuggestions = document.getElementById('rvm-suggestions');
                if (userSuggestions && !e.target.closest('#search-user') && !e.target.closest('#user-suggestions')) {
                    userSuggestions.classList.add('d-none');
                }
                if (rvmSuggestions && !e.target.closest('#search-rvm') && !e.target.closest('#rvm-suggestions')) {
                    rvmSuggestions.classList.add('d-none');
                }
            });
        },

        searchUsers(query) {
            const suggestions = document.getElementById('user-suggestions');
            if (!suggestions) return;

            if (query.length < 1) {
                suggestions.classList.add('d-none');
                return;
            }

            const users = window.assignmentManager.assignableUsers || [];
            const filtered = users.filter(u =>
                u.name.toLowerCase().includes(query.toLowerCase()) ||
                u.email?.toLowerCase().includes(query.toLowerCase()) ||
                u.role.toLowerCase().includes(query.toLowerCase())
            ).slice(0, 8);

            if (filtered.length === 0) {
                suggestions.innerHTML = '<div class="autocomplete-no-results">No users found</div>';
            } else {
                suggestions.innerHTML = filtered.map(u => `
                        <div class="autocomplete-item" onclick="assignmentSearch.selectUser(${u.id}, '${this.escapeHtml(u.name)}', '${u.role}')">
                            <div class="item-name">${this.escapeHtml(u.name)}</div>
                            <div class="item-subtitle">${u.email || ''} • ${u.role}</div>
                        </div>
                    `).join('');
            }
            suggestions.classList.remove('d-none');
        },

        searchRvm(query) {
            const suggestions = document.getElementById('rvm-suggestions');
            if (!suggestions) return;

            if (query.length < 1) {
                suggestions.classList.add('d-none');
                return;
            }

            const selectedUserId = document.getElementById('assign-user').value;
            const machines = window.assignmentManager.machines || [];
            const assignments = window.assignmentManager.assignments || [];

            // Build set of RVM IDs already assigned to SELECTED USER (show as unavailable)
            const assignedRvmIds = new Set(
                assignments
                    .filter(a => a.user_id == selectedUserId || a.user?.id == selectedUserId)
                    .map(a => a.rvm_machine?.id || a.rvm_machine_id)
            );

            // Also check if RVM is assigned to ANYONE (for "Assigned" badge)
            const anyAssignedRvmIds = new Set(
                assignments.map(a => a.rvm_machine?.id || a.rvm_machine_id)
            );

            const filtered = machines.filter(m =>
                m.name.toLowerCase().includes(query.toLowerCase()) ||
                (m.location && m.location.toLowerCase().includes(query.toLowerCase())) ||
                (m.location_address && m.location_address.toLowerCase().includes(query.toLowerCase()))
            ).slice(0, 10);

            if (filtered.length === 0) {
                suggestions.innerHTML = '<div class="autocomplete-no-results">No RVM machines found</div>';
            } else {
                suggestions.innerHTML = filtered.map(m => {
                    const isAssignedToUser = assignedRvmIds.has(m.id);
                    const isAssignedToAnyone = anyAssignedRvmIds.has(m.id);

                    // Badge: show "Assigned" (gray) if assigned to anyone, otherwise "Available" (green)
                    const statusBadge = isAssignedToAnyone
                        ? '<span class="badge" style="background:#9ca3af;color:white;font-size:0.65rem;padding:2px 6px;border-radius:4px;">Assigned</span>'
                        : '<span class="badge" style="background:#10b981;color:white;font-size:0.65rem;padding:2px 6px;border-radius:4px;">Available</span>';

                    // Only disable selection if already assigned to THIS user
                    const itemStyle = isAssignedToUser ? 'opacity:0.5;cursor:not-allowed;' : '';
                    const onClick = isAssignedToUser
                        ? ''
                        : `onclick="assignmentSearch.selectRvm(${m.id}, '${this.escapeHtml(m.name)}', '${this.escapeHtml(m.location || '')}')"`;

                    return `
                            <div class="autocomplete-item" style="${itemStyle}" ${onClick}>
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="item-name">${this.escapeHtml(m.name)}</div>
                                    ${statusBadge}
                                </div>
                                <div class="item-subtitle">${m.location || 'No location set'}</div>
                            </div>
                        `;
                }).join('');
            }
            suggestions.classList.remove('d-none');
        },

        selectUser(id, name, role) {
            document.getElementById('assign-user').value = id;
            document.getElementById('search-user').value = name;
            document.getElementById('selected-user-name').textContent = name;
            document.getElementById('selected-user-role').textContent = role;
            document.getElementById('selected-user-role').className = 'badge role-badge-' + role;
            document.getElementById('user-info').classList.remove('d-none');
            document.getElementById('user-suggestions').classList.add('d-none');
        },

        selectRvm(id, name, location) {
            document.getElementById('assign-rvm').value = id;
            document.getElementById('search-rvm').value = name;
            document.getElementById('selected-rvm-name').textContent = name;
            document.getElementById('selected-rvm-location').textContent = location || 'No location set';
            document.getElementById('rvm-info').classList.remove('d-none');
            document.getElementById('rvm-suggestions').classList.add('d-none');
        },

        clearUser() {
            document.getElementById('assign-user').value = '';
            document.getElementById('search-user').value = '';
            document.getElementById('user-info').classList.add('d-none');
        },

        clearRvm() {
            document.getElementById('assign-rvm').value = '';
            document.getElementById('search-rvm').value = '';
            document.getElementById('rvm-info').classList.add('d-none');
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML.replace(/'/g, "\\'");
        }
    };

    // Credentials helper for API credentials modal
    window.assignmentCredentials = {
        serial: '',
        apiKey: '',
        machineName: '',
        rvmId: '', // Store rvmId
        visible: false,

        setCredentials(serial, apiKey, machineName, rvmId) {
            this.serial = serial || '';
            this.apiKey = apiKey || '';
            this.machineName = machineName || '';
            this.rvmId = rvmId || '';
            document.getElementById('cred-serial').value = this.serial;
            document.getElementById('cred-apikey').value = this.apiKey;
            // Hide API key initially with dots
            document.getElementById('cred-apikey-display').textContent = '••••••••••••••••••••••••••••••••';
            this.visible = false;
            document.getElementById('btn-toggle-apikey').innerHTML = '<i class="ti tabler-eye"></i> Show';
        },

        toggleApiKey() {
            this.visible = !this.visible;
            const display = document.getElementById('cred-apikey-display');
            const btn = document.getElementById('btn-toggle-apikey');
            if (this.visible) {
                display.textContent = this.apiKey;
                btn.innerHTML = '<i class="ti tabler-eye-off"></i> Hide';
            } else {
                display.textContent = '••••••••••••••••••••••••••••••••';
                btn.innerHTML = '<i class="ti tabler-eye"></i> Show';
            }
        },

        copySerial() {
            navigator.clipboard.writeText(this.serial).then(() => {
                this.showToast('Serial Number copied!');
            });
        },

        copyApiKey() {
            navigator.clipboard.writeText(this.apiKey).then(() => {
                this.showToast('API Key copied!');
            });
        },

        downloadJson() {
            const data = {
                serial_number: this.serial,
                api_key: this.apiKey,
                name: this.machineName,
                generated_at: new Date().toISOString()
            };
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `rvm-credentials-${this.serial}.json`;
            a.click();
            URL.revokeObjectURL(url);
            this.showToast('JSON downloaded!');
        },

        showToast(msg) {
            // Simple toast notification
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 m-3 p-2 px-3 rounded';
            toast.style.cssText = 'background:#065f46;color:white;z-index:9999;animation:fadeIn 0.3s;';
            toast.textContent = msg;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2000);
        }
    };

    // Init with apiHelper readiness check AND DOM element check
    function initAssignments() {
        // Check if required DOM elements exist (for SPA navigation)
        const grid = document.getElementById('assignments-grid');
        const hasApiHelper = typeof window.apiHelper !== 'undefined';

        if (hasApiHelper && grid) {
            // Elements ready, initialize
            window.assignmentManager.init();
            window.assignmentSearch.init();
        } else {
            // Retry after a short delay - DOM or apiHelper not ready yet
            setTimeout(initAssignments, 100);
        }
    }

    // Init on load - use requestAnimationFrame for SPA to ensure DOM is painted
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        requestAnimationFrame(() => {
            initAssignments();
        });
    } else {
        document.addEventListener('DOMContentLoaded', initAssignments);
    }
</script>