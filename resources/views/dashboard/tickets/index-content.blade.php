{{-- Maintenance Tickets Management - Bio-Digital 2026 --}}
<style>
    /* Bio-Digital 2026 - Tickets Page Styles */
    .ticket-card {
        background: linear-gradient(to bottom right, #ffffff, #fffbeb);
        border: 1px solid rgba(245, 158, 11, 0.15);
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .ticket-card:hover {
        box-shadow: 0 4px 20px rgba(245, 158, 11, 0.12);
    }

    .priority-low {
        background: #d1fae5;
        color: #065f46;
    }

    .priority-medium {
        background: #fef3c7;
        color: #92400e;
    }

    .priority-high {
        background: #fed7aa;
        color: #c2410c;
    }

    .priority-critical {
        background: #fecaca;
        color: #991b1b;
    }

    .status-pending {
        background: #e5e7eb;
        color: #374151;
    }

    .status-assigned {
        background: #dbeafe;
        color: #1e40af;
    }

    .status-in_progress {
        background: #fef3c7;
        color: #92400e;
    }

    .status-resolved {
        background: #d1fae5;
        color: #065f46;
    }

    .status-closed {
        background: #9ca3af;
        color: white;
    }

    /* Bio-Digital Modal 350px */
    .modal-add-ticket .modal-dialog {
        max-width: 350px !important;
    }

    .modal-add-ticket .modal-content {
        border-radius: 16px;
        border: none;
    }

    .modal-add-ticket .modal-header {
        border-bottom: none;
        padding: 20px 20px 0;
    }

    .modal-add-ticket .modal-body {
        padding: 16px 20px;
    }

    .modal-add-ticket .modal-footer {
        border-top: none;
        padding: 0 20px 20px;
    }

    .modal-add-ticket .modal-title {
        font-weight: 700;
        font-size: 1.1rem;
        color: #92400e;
    }

    .modal-add-ticket .form-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
    }

    .modal-add-ticket .form-select,
    .modal-add-ticket .form-control {
        border-radius: 10px;
        padding: 0.6rem 0.8rem;
    }

    .modal-add-ticket .btn-bio {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        border: none;
        border-radius: 10px;
        font-weight: 600;
    }

    .modal-add-ticket .btn-bio:hover {
        background: linear-gradient(135deg, #d97706, #b45309);
    }

    /* Autocomplete Dropdown Styles */
    .ticket-autocomplete-dropdown {
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

    .ticket-autocomplete-item {
        padding: 10px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f3f4f6;
        transition: background 0.2s ease;
    }

    .ticket-autocomplete-item:last-child {
        border-bottom: none;
    }

    .ticket-autocomplete-item:hover,
    .ticket-autocomplete-item.active {
        background: linear-gradient(to right, #fffbeb, #fef3c7);
    }

    .ticket-autocomplete-item .item-name {
        font-weight: 600;
        color: #1f2937;
    }

    .ticket-autocomplete-item .item-subtitle {
        font-size: 0.75rem;
        color: #6b7280;
    }

    .ticket-autocomplete-no-results {
        padding: 12px;
        text-align: center;
        color: #9ca3af;
        font-size: 0.85rem;
    }

    .selected-info-box {
        background: #fffbeb;
        border: 1px solid #f59e0b;
        border-radius: 10px;
        padding: 10px 12px;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ti tabler-tool me-2"></i>Maintenance Tickets
                </h5>
                <div>
                    <button type="button" class="btn btn-label-secondary me-2"
                        onclick="window.ticketManager.loadTickets()">
                        <i class="ti tabler-refresh me-1"></i>Refresh
                    </button>
                    @if(in_array(auth()->user()->role, ['super_admin', 'admin']))
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal"
                            data-bs-target="#addTicketModal">
                            <i class="ti tabler-plus me-1"></i>New Ticket
                        </button>
                    @endif
                </div>
            </div>

            <div class="card-body">
                <!-- Info Alert -->
                <div class="alert mb-4"
                    style="background: linear-gradient(to right, #fffbeb, #fef3c7); border: 1px solid #f59e0b; border-radius: 10px; color: #92400e;">
                    <i class="ti tabler-info-circle me-1"></i>
                    <strong>Tiket Tugas:</strong> Ticket hanya bisa di-assign ke teknisi yang sudah memiliki Hak Akses
                    ke RVM terkait.
                </div>

                <!-- Filters -->
                <div class="row mb-4 g-2">
                    <div class="col-md-3">
                        <select id="filter-status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="assigned">Assigned</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="filter-priority" class="form-select">
                            <option value="">All Priority</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="filter-rvm" class="form-select">
                            <option value="">All RVM</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" id="search-ticket" class="form-control" placeholder="Search ticket...">
                    </div>
                </div>

                <!-- Tickets Table -->
                <div class="table-responsive">
                    <table class="table table-hover" id="tickets-table">
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <th>RVM Machine</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Assignee</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tickets-tbody">
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="spinner-border text-warning"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Ticket Modal - Bio-Digital 350px -->
<div class="modal fade modal-add-ticket" id="addTicketModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti tabler-tool me-2"></i>New Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addTicketForm">
                    <!-- Step indicators -->
                    <div class="d-flex justify-content-center gap-2 mb-3">
                        <div class="ticket-step-dot active"
                            style="width: 10px; height: 10px; border-radius: 50%; background: #f59e0b;"></div>
                        <div class="ticket-step-dot"
                            style="width: 10px; height: 10px; border-radius: 50%; background: #e5e7eb;"></div>
                        <div class="ticket-step-dot"
                            style="width: 10px; height: 10px; border-radius: 50%; background: #e5e7eb;"></div>
                    </div>

                    <!-- Step 1: Search RVM -->
                    <div id="ticket-step-1" class="ticket-step">
                        <label class="form-label">Search RVM Machine</label>
                        <div class="position-relative mb-3">
                            <input type="text" id="search-ticket-rvm" class="form-control"
                                placeholder="Type to search RVM..." autocomplete="off">
                            <input type="hidden" id="ticket-rvm" required>
                            <div id="ticket-rvm-suggestions" class="ticket-autocomplete-dropdown d-none"></div>
                        </div>
                        <div id="ticket-rvm-info" class="selected-info-box d-none mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">Selected:</small>
                                    <div class="fw-bold" id="selected-ticket-rvm-name"></div>
                                    <small id="selected-ticket-rvm-location" class="text-muted"></small>
                                </div>
                                <button type="button" class="btn btn-sm btn-icon btn-label-danger"
                                    onclick="ticketSearch.clearRvm()">
                                    <i class="ti tabler-x"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-bio w-100 text-white" onclick="ticketWizard.nextStep()">
                            Next: Details <i class="ti tabler-arrow-right ms-1"></i>
                        </button>
                    </div>

                    <!-- Step 2: Issue Details -->
                    <div id="ticket-step-2" class="ticket-step d-none">
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select id="ticket-category" class="form-select" required>
                                <option value="">Choose...</option>
                                <option value="Installation">Installation</option>
                                <option value="Sensor Fault">Sensor Fault</option>
                                <option value="Motor Jammed">Motor Jammed</option>
                                <option value="Network Issue">Network Issue</option>
                                <option value="Full Bin">Full Bin</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Priority</label>
                            <select id="ticket-priority" class="form-select" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea id="ticket-description" class="form-control" rows="2" required
                                placeholder="Describe the issue..."></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-label-secondary flex-grow-1"
                                onclick="ticketWizard.prevStep()">Back</button>
                            <button type="button" id="btn-ticket-next-assign" class="btn btn-bio flex-grow-1 text-white"
                                onclick="ticketWizard.nextStep()">Next: Assign</button>
                        </div>
                    </div>

                    <!-- Step 3: Search & Assign Technician -->
                    <div id="ticket-step-3" class="ticket-step d-none">
                        <label class="form-label">Assign to Technician <small class="text-muted">(must have RVM
                                access)</small></label>
                        <div class="position-relative mb-3">
                            <input type="text" id="search-ticket-assignee" class="form-control"
                                placeholder="Type to search technician..." autocomplete="off">
                            <input type="hidden" id="ticket-assignee">
                            <div id="ticket-assignee-suggestions" class="ticket-autocomplete-dropdown d-none"></div>
                        </div>
                        <div id="ticket-assignee-info" class="selected-info-box d-none mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">Assigned to:</small>
                                    <div class="fw-bold" id="selected-ticket-assignee-name"></div>
                                    <span class="badge" id="selected-ticket-assignee-role"></span>
                                </div>
                                <button type="button" class="btn btn-sm btn-icon btn-label-danger"
                                    onclick="ticketSearch.clearAssignee()">
                                    <i class="ti tabler-x"></i>
                                </button>
                            </div>
                        </div>
                        <div id="no-technicians-alert" class="alert alert-warning small d-none">
                            <i class="ti tabler-alert-circle me-1"></i>No technicians assigned to this RVM. Add via
                            Assignments first.
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-label-secondary flex-grow-1"
                                onclick="ticketWizard.prevStep()">Back</button>
                            <button type="submit" class="btn btn-bio flex-grow-1 text-white" id="btn-submit-ticket">
                                <i class="ti tabler-check me-1"></i>Create
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal - Bio-Digital -->
<div class="modal fade" id="ticketSuccessModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 300px;">
        <div class="modal-content text-center" style="border-radius: 16px; border: none;">
            <div class="modal-body py-4">
                <div class="mb-3" style="font-size: 48px;">🛠️</div>
                <h5 class="fw-bold" style="color: #92400e;">Ticket Created!</h5>
                <p class="text-muted small mb-1" id="created-ticket-number"></p>
                <button type="button" class="btn btn-bio text-white px-4 mt-3" data-bs-dismiss="modal"
                    onclick="window.ticketManager.loadTickets()">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Ticket Detail Modal - Bio-Digital 350px -->
<div class="modal fade" id="ticketDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header"
                style="border-bottom: none; background: linear-gradient(to right, #fffbeb, #fef3c7);">
                <h6 class="modal-title fw-bold" style="color: #92400e;"><i class="ti tabler-tool me-2"></i>Ticket
                    Detail</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="ticket-detail-content">
                <div class="text-center py-3">
                    <div class="spinner-border text-warning"></div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: none;">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Ticket Modal - Bio-Digital 350px -->
<div class="modal fade modal-add-ticket" id="editTicketModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti tabler-edit me-2"></i>Edit Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-ticket-id">
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select id="edit-ticket-category" class="form-select">
                        <option value="Installation">Installation</option>
                        <option value="Sensor Fault">Sensor Fault</option>
                        <option value="Motor Jammed">Motor Jammed</option>
                        <option value="Network Issue">Network Issue</option>
                        <option value="Full Bin">Full Bin</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Priority</label>
                    <select id="edit-ticket-priority" class="form-select">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea id="edit-ticket-description" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Resolution Notes</label>
                    <textarea id="edit-ticket-resolution" class="form-control" rows="2"
                        placeholder="Notes about resolution..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-bio text-white" id="btn-save-ticket-edit"
                    onclick="ticketManager.saveTicketEdit()">
                    <i class="ti tabler-check me-1"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Update Success Modal - Bio-Digital -->
<div class="modal fade" id="updateSuccessModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 300px;">
        <div class="modal-content text-center" style="border-radius: 16px; border: none;">
            <div class="modal-body py-4">
                <div class="mb-3" style="font-size: 48px;">✅</div>
                <h5 class="fw-bold" style="color: #065f46;">Pembaruan Berhasil</h5>
                <p class="text-muted small mb-1" id="update-success-message"></p>
                <button type="button" class="btn btn-bio text-white px-4 mt-3" data-bs-dismiss="modal">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Ticket Manager
    window.ticketManager = {
        tickets: [],
        machines: [],

        async init() {
            await this.loadMachines();
            await this.loadTickets();
        },

        async loadMachines() {
            try {
                // Fetch all machines AND assignments to filter
                const [machinesRes, assignmentsRes] = await Promise.all([
                    apiHelper.get('/api/v1/dashboard/machines'),
                    apiHelper.get('/api/v1/dashboard/technician-assignments')
                ]);
                const machinesData = await machinesRes.json();
                const assignmentsData = await assignmentsRes.json();

                const allMachines = machinesData.data || machinesData || [];
                const assignments = assignmentsData.data || assignmentsData || [];

                // Get unique RVM IDs that have assignments
                const assignedRvmIds = new Set(assignments.map(a => a.rvm_machine?.id || a.rvm_machine_id));

                // Filter machines to only those with assignments
                this.machines = allMachines.filter(m => assignedRvmIds.has(m.id));
                this.allMachines = allMachines; // Keep all for filter dropdown
                this.populateMachineDropdowns();
            } catch (e) {
                console.error('Failed to load machines:', e);
            }
        },

        populateMachineDropdowns() {
            const filterRvm = document.getElementById('filter-rvm');
            if (!filterRvm) return;

            // Filter dropdown uses all machines
            const allMachines = this.allMachines || this.machines || [];
            const filterOptions = allMachines.map(m => `<option value="${m.id}">${m.name}</option>`).join('');
            filterRvm.innerHTML = '<option value="">All RVM</option>' + filterOptions;

            // search-ticket-rvm uses autocomplete (ticketSearch), not dropdown
            // So we just store machines for the search
        },

        async loadTickets() {
            const tbody = document.getElementById('tickets-tbody');
            if (!tbody) return;

            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5"><div class="spinner-border text-warning"></div></td></tr>';

            try {
                const response = await apiHelper.get('/api/v1/dashboard/maintenance-tickets');
                const data = await response.json();
                this.tickets = data.data || data || [];
                this.renderTickets();
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Failed to load tickets</td></tr>';
            }
        },

        renderTickets() {
            const tbody = document.getElementById('tickets-tbody');
            if (!tbody) return;

            if (this.tickets.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-5"><i class="ti tabler-tool-off" style="font-size: 32px;"></i><div class="mt-2">No tickets yet</div></td></tr>';
                return;
            }

            const userRole = '{{ auth()->user()->role }}';
            const canEdit = ['super_admin', 'admin'].includes(userRole);
            const canDelete = userRole === 'super_admin';

            tbody.innerHTML = this.tickets.map(t => `
            <tr>
                <td><code class="fw-bold">${t.ticket_number}</code></td>
                <td>${t.rvm_machine?.name || 'Unknown'}</td>
                <td><span class="badge bg-label-secondary">${t.category}</span></td>
                <td><span class="badge priority-${t.priority}">${t.priority}</span></td>
                <td>${t.assignee?.name || '<span class="text-muted">Unassigned</span>'}</td>
                <td><span class="badge status-${t.status}">${t.status.replace('_', ' ')}</span></td>
                <td class="small">${new Date(t.created_at).toLocaleDateString()}</td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-icon btn-label-primary" onclick="ticketManager.viewTicket(${t.id})" title="View">
                            <i class="ti tabler-eye"></i>
                        </button>
                        ${canEdit ? `
                        <button class="btn btn-sm btn-icon btn-label-warning" onclick="ticketManager.editTicket(${t.id})" title="Edit">
                            <i class="ti tabler-edit"></i>
                        </button>
                        ` : ''}
                        ${canDelete ? `
                        <button class="btn btn-sm btn-icon btn-label-danger" onclick="ticketManager.deleteTicket(${t.id})" title="Delete">
                            <i class="ti tabler-trash"></i>
                        </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `).join('');
        },

        async viewTicket(id) {
            try {
                const response = await apiHelper.get(`/api/v1/dashboard/maintenance-tickets/${id}`);
                const data = await response.json();
                const t = data.data;

                const statusOptions = ['pending', 'assigned', 'in_progress', 'resolved', 'closed'].map(s =>
                    `<option value="${s}" ${t.status === s ? 'selected' : ''}>${s.replace('_', ' ')}</option>`
                ).join('');

                const detail = `
                    <div class="text-start">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge priority-${t.priority}">${t.priority}</span>
                            <span class="badge status-${t.status}">${t.status.replace('_', ' ')}</span>
                        </div>
                        <dl class="row mb-0" style="font-size: 0.85rem;">
                            <dt class="col-4">Ticket #:</dt><dd class="col-8"><code>${t.ticket_number}</code></dd>
                            <dt class="col-4">RVM:</dt><dd class="col-8">${t.rvm_machine?.name || 'N/A'}</dd>
                            <dt class="col-4">Category:</dt><dd class="col-8">${t.category}</dd>
                            <dt class="col-4">Description:</dt><dd class="col-8">${t.description || '-'}</dd>
                            <dt class="col-4">Assignee:</dt><dd class="col-8">${t.assignee?.name || 'Unassigned'}</dd>
                            <dt class="col-4">Reporter:</dt><dd class="col-8">${t.reporter?.name || 'System'}</dd>
                            <dt class="col-4">Created:</dt><dd class="col-8">${new Date(t.created_at).toLocaleString()}</dd>
                            ${t.started_at ? `<dt class="col-4">Started:</dt><dd class="col-8">${new Date(t.started_at).toLocaleString()}</dd>` : ''}
                            ${t.completed_at ? `<dt class="col-4">Completed:</dt><dd class="col-8">${new Date(t.completed_at).toLocaleString()}</dd>` : ''}
                            ${t.resolution_notes ? `<dt class="col-4">Resolution:</dt><dd class="col-8">${t.resolution_notes}</dd>` : ''}
                        </dl>
                        @if(in_array(auth()->user()->role, ['super_admin', 'admin']))
                            <hr>
                            <label class="form-label small">Quick Status Update:</label>
                            <div class="d-flex gap-2">
                                <select id="quick-status-${t.id}" class="form-select form-select-sm">${statusOptions}</select>
                                <button class="btn btn-sm btn-bio text-white" onclick="ticketManager.updateStatus(${t.id})">Update</button>
                            </div>
                        @endif
                    </div>
                `;
                document.getElementById('ticket-detail-content').innerHTML = detail;
                new bootstrap.Modal(document.getElementById('ticketDetailModal')).show();
            } catch (e) {
                alert('Failed to load ticket details');
            }
        },

        async updateStatus(id) {
            const status = document.getElementById(`quick-status-${id}`).value;
            try {
                await apiHelper.patch(`/api/v1/dashboard/maintenance-tickets/${id}/status`, { status });
                bootstrap.Modal.getInstance(document.getElementById('ticketDetailModal')).hide();
                this.loadTickets();
                // Show success toast
                document.getElementById('update-success-message').textContent = 'Status updated successfully';
                new bootstrap.Modal(document.getElementById('updateSuccessModal')).show();
            } catch (e) {
                alert('Failed to update status');
            }
        },

        async editTicket(id) {
            try {
                const response = await apiHelper.get(`/api/v1/dashboard/maintenance-tickets/${id}`);
                const data = await response.json();
                const t = data.data;

                document.getElementById('edit-ticket-id').value = t.id;
                document.getElementById('edit-ticket-category').value = t.category;
                document.getElementById('edit-ticket-priority').value = t.priority;
                document.getElementById('edit-ticket-description').value = t.description || '';
                document.getElementById('edit-ticket-resolution').value = t.resolution_notes || '';

                new bootstrap.Modal(document.getElementById('editTicketModal')).show();
            } catch (e) {
                alert('Failed to load ticket for editing');
            }
        },

        async saveTicketEdit() {
            const id = document.getElementById('edit-ticket-id').value;
            const btn = document.getElementById('btn-save-ticket-edit');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            try {
                await apiHelper.put(`/api/v1/dashboard/maintenance-tickets/${id}`, {
                    category: document.getElementById('edit-ticket-category').value,
                    priority: document.getElementById('edit-ticket-priority').value,
                    description: document.getElementById('edit-ticket-description').value,
                    resolution_notes: document.getElementById('edit-ticket-resolution').value,
                });
                bootstrap.Modal.getInstance(document.getElementById('editTicketModal')).hide();
                this.loadTickets();
                document.getElementById('update-success-message').textContent = 'Ticket updated successfully';
                new bootstrap.Modal(document.getElementById('updateSuccessModal')).show();
            } catch (e) {
                alert('Failed to save changes');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="ti tabler-check me-1"></i>Save Changes';
            }
        },

        async deleteTicket(id) {
            if (!confirm('Are you sure you want to delete this ticket? This action cannot be undone.')) return;

            try {
                await apiHelper.delete(`/api/v1/maintenance-tickets/${id}`);
                this.loadTickets();
            } catch (e) {
                alert('Failed to delete ticket');
            }
        }
    };

    // Ticket Wizard
    window.ticketWizard = {
        currentStep: 1,

        async nextStep() {
            if (this.currentStep === 1 && !document.getElementById('ticket-rvm').value) {
                alert('Please select an RVM');
                return;
            }
            if (this.currentStep === 2) {
                if (!document.getElementById('ticket-category').value || !document.getElementById('ticket-description').value) {
                    alert('Please fill in category and description');
                    return;
                }

                // Show loading state
                const btn = document.getElementById('btn-ticket-next-assign');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
                }

                // Load technicians for selected RVM
                await this.loadAvailableTechnicians();

                // Reset button state (will be hidden by step change anyway, but good practice)
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = 'Next: Assign';
                }
            }

            this.currentStep++;
            this.updateStepDisplay();
        },

        prevStep() {
            this.currentStep--;
            this.updateStepDisplay();
        },

        updateStepDisplay() {
            for (let i = 1; i <= 3; i++) {
                const step = document.getElementById(`ticket-step-${i}`);
                if (step) step.classList.toggle('d-none', i !== this.currentStep);
            }
            const dots = document.querySelectorAll('.ticket-step-dot');
            dots.forEach((dot, i) => {
                dot.style.background = i < this.currentStep ? '#f59e0b' : '#e5e7eb';
            });
        },

        async loadAvailableTechnicians() {
            const rvmId = document.getElementById('ticket-rvm').value;
            const alert = document.getElementById('no-technicians-alert');

            try {
                const response = await apiHelper.get(`/api/v1/dashboard/technician-assignments/by-rvm/${rvmId}`);
                const data = await response.json();
                const technicians = data.data || [];

                if (technicians.length === 0) {
                    alert.classList.remove('d-none');
                    window.ticketSearch?.setAvailableTechnicians([]);
                } else {
                    alert.classList.add('d-none');
                    // Store technicians for search autocomplete
                    window.ticketSearch?.setAvailableTechnicians(technicians);
                }
            } catch (e) {
                console.error('Failed to load technicians:', e);
            }
        },

        reset() {
            this.currentStep = 1;
            this.updateStepDisplay();
            document.getElementById('addTicketForm').reset();
            document.getElementById('no-technicians-alert').classList.add('d-none');
            // Clear search fields
            document.getElementById('search-ticket-rvm').value = '';
            document.getElementById('search-ticket-assignee').value = '';
            document.getElementById('ticket-rvm').value = '';
            document.getElementById('ticket-assignee').value = '';
            document.getElementById('ticket-rvm-info')?.classList.add('d-none');
            document.getElementById('ticket-assignee-info')?.classList.add('d-none');
            window.ticketSearch?.setAvailableTechnicians([]);
        }
    };

    // Form submit
    document.getElementById('addTicketForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();

        const btn = document.getElementById('btn-submit-ticket');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const response = await apiHelper.post('/api/v1/dashboard/maintenance-tickets', {
                rvm_machine_id: document.getElementById('ticket-rvm').value,
                category: document.getElementById('ticket-category').value,
                priority: document.getElementById('ticket-priority').value,
                description: document.getElementById('ticket-description').value,
                assignee_id: document.getElementById('ticket-assignee').value || null
            });

            const data = await response.json();
            document.getElementById('created-ticket-number').textContent = data.data?.ticket_number || '';
            bootstrap.Modal.getInstance(document.getElementById('addTicketModal')).hide();
            new bootstrap.Modal(document.getElementById('ticketSuccessModal')).show();
            window.ticketManager.loadTickets();
            ticketWizard.reset();
        } catch (e) {
            alert('Failed to create ticket');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti tabler-check me-1"></i>Create';
        }
    });

    // Reset wizard when modal closes
    document.getElementById('addTicketModal')?.addEventListener('hidden.bs.modal', () => ticketWizard.reset());

    // Search/Autocomplete Handler for Tickets
    const ticketSearch = {
        debounceTimer: null,
        availableTechnicians: [],

        init() {
            this.setupRvmSearch();
            this.setupAssigneeSearch();
            this.setupClickOutside();
        },

        setupRvmSearch() {
            const input = document.getElementById('search-ticket-rvm');
            if (!input) return;

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

        setupAssigneeSearch() {
            const input = document.getElementById('search-ticket-assignee');
            if (!input) return;

            input.addEventListener('input', () => {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {
                    this.searchAssignee(input.value);
                }, 200);
            });

            input.addEventListener('focus', () => {
                if (input.value.length >= 1) {
                    this.searchAssignee(input.value);
                }
            });
        },

        setupClickOutside() {
            document.addEventListener('click', (e) => {
                const rvmSuggestions = document.getElementById('ticket-rvm-suggestions');
                const assigneeSuggestions = document.getElementById('ticket-assignee-suggestions');
                if (rvmSuggestions && !e.target.closest('#search-ticket-rvm') && !e.target.closest('#ticket-rvm-suggestions')) {
                    rvmSuggestions.classList.add('d-none');
                }
                if (assigneeSuggestions && !e.target.closest('#search-ticket-assignee') && !e.target.closest('#ticket-assignee-suggestions')) {
                    assigneeSuggestions.classList.add('d-none');
                }
            });
        },

        searchRvm(query) {
            const suggestions = document.getElementById('ticket-rvm-suggestions');
            if (!suggestions) return;

            if (query.length < 1) {
                suggestions.classList.add('d-none');
                return;
            }

            const machines = window.ticketManager.machines || [];
            const filtered = machines.filter(m =>
                m.name.toLowerCase().includes(query.toLowerCase()) ||
                (m.location && m.location.toLowerCase().includes(query.toLowerCase()))
            ).slice(0, 8);

            if (filtered.length === 0) {
                suggestions.innerHTML = '<div class="ticket-autocomplete-no-results">No RVM machines found</div>';
            } else {
                suggestions.innerHTML = filtered.map(m => `
                    <div class="ticket-autocomplete-item" onclick="ticketSearch.selectRvm(${m.id}, '${this.escapeHtml(m.name)}', '${this.escapeHtml(m.location || '')}')">
                        <div class="item-name">${this.escapeHtml(m.name)}</div>
                        <div class="item-subtitle">${m.location || 'No location set'}</div>
                    </div>
                `).join('');
            }
            suggestions.classList.remove('d-none');
        },

        searchAssignee(query) {
            const suggestions = document.getElementById('ticket-assignee-suggestions');
            if (!suggestions) return;

            if (query.length < 1) {
                suggestions.classList.add('d-none');
                return;
            }

            const technicians = this.availableTechnicians || [];
            const filtered = technicians.filter(t =>
                t.name.toLowerCase().includes(query.toLowerCase()) ||
                t.email?.toLowerCase().includes(query.toLowerCase())
            ).slice(0, 8);

            if (filtered.length === 0) {
                suggestions.innerHTML = '<div class="ticket-autocomplete-no-results">No technicians found</div>';
            } else {
                suggestions.innerHTML = filtered.map(t => `
                    <div class="ticket-autocomplete-item" onclick="ticketSearch.selectAssignee(${t.id}, '${this.escapeHtml(t.name)}', '${t.role || 'teknisi'}')">
                        <div class="item-name">${this.escapeHtml(t.name)}</div>
                        <div class="item-subtitle">${t.email || ''}</div>
                    </div>
                `).join('');
            }
            suggestions.classList.remove('d-none');
        },

        selectRvm(id, name, location) {
            document.getElementById('ticket-rvm').value = id;
            document.getElementById('search-ticket-rvm').value = name;
            document.getElementById('selected-ticket-rvm-name').textContent = name;
            document.getElementById('selected-ticket-rvm-location').textContent = location || 'No location set';
            document.getElementById('ticket-rvm-info').classList.remove('d-none');
            document.getElementById('ticket-rvm-suggestions').classList.add('d-none');
        },

        selectAssignee(id, name, role) {
            document.getElementById('ticket-assignee').value = id;
            document.getElementById('search-ticket-assignee').value = name;
            document.getElementById('selected-ticket-assignee-name').textContent = name;
            document.getElementById('selected-ticket-assignee-role').textContent = role;
            document.getElementById('selected-ticket-assignee-role').className = 'badge role-badge-' + role;
            document.getElementById('ticket-assignee-info').classList.remove('d-none');
            document.getElementById('ticket-assignee-suggestions').classList.add('d-none');
        },

        clearRvm() {
            document.getElementById('ticket-rvm').value = '';
            document.getElementById('search-ticket-rvm').value = '';
            document.getElementById('ticket-rvm-info').classList.add('d-none');
        },

        clearAssignee() {
            document.getElementById('ticket-assignee').value = '';
            document.getElementById('search-ticket-assignee').value = '';
            document.getElementById('ticket-assignee-info').classList.add('d-none');
        },

        setAvailableTechnicians(technicians) {
            this.availableTechnicians = technicians;
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML.replace(/'/g, "\\'");
        }
    };

    // Make ticketSearch globally available
    window.ticketSearch = ticketSearch;

    // Init with apiHelper readiness check
    function initTickets() {
        if (typeof window.apiHelper !== 'undefined') {
            window.ticketManager.init();
            window.ticketSearch.init();
        } else {
            setTimeout(initTickets, 100);
        }
    }

    // Init on load
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initTickets();
    } else {
        document.addEventListener('DOMContentLoaded', initTickets);
    }
</script>