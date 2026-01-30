<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ti tabler-device-desktop-analytics me-2"></i>RVM Machines Management
                </h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-danger d-none" id="delete-selected-btn"
                        onclick="machineManagement.bulkDelete()">
                        <i class="ti tabler-trash me-1"></i>Delete Selected (<span id="selected-count">0</span>)
                    </button>
                    <button type="button" class="btn btn-label-secondary d-none" id="clear-selection-btn"
                        onclick="machineManagement.clearSelection()">
                        <i class="ti tabler-x me-1"></i>Clear
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#addMachineModal">
                        <i class="ti tabler-plus me-1"></i>Add Machine
                    </button>
                </div>
            </div>

            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <select id="status-filter" class="form-select">
                            <option value="">All Status</option>
                            <option value="online">Online</option>
                            <option value="offline">Offline</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="location-filter" class="form-control"
                            placeholder="Filter by location...">
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-label-secondary w-100" onclick="window.refreshPage()">
                            <i class="ti tabler-refresh me-1"></i>Refresh
                        </button>
                    </div>
                </div>

                <!-- Stats Summary -->
                <div class="row g-3 mb-4">
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
                                        <h5 class="mb-0" id="online-count">0</h5>
                                        <small>Online</small>
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
                                            <i class="ti tabler-x"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h5 class="mb-0" id="offline-count">0</h5>
                                        <small>Offline</small>
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
                                            <i class="ti tabler-tool"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h5 class="mb-0" id="maintenance-count">0</h5>
                                        <small>Maintenance</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card stats-card stats-card-primary mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-2">
                                        <span class="avatar-initial rounded bg-label-primary">
                                            <i class="ti tabler-recycle"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h5 class="mb-0" id="total-transactions">0</h5>
                                        <small>Total Transactions</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grid View -->
                <div class="row g-3" id="machines-grid">
                    <!-- Loading skeleton -->
                    <div class="col-md-4">
                        <div class="skeleton skeleton-card"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="skeleton skeleton-card"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="skeleton skeleton-card"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Machine Detail Modal -->
<div class="modal fade" id="machineDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="machine-name">Machine Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="machine-detail-content">
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Machine Modal - Bio-Digital Minimalism 2026 Multi-Step Wizard -->
<style>
    /* Bio-Digital Minimalism 2026 - Add Machine Wizard */
    .modal-add-machine .modal-dialog {
        max-width: 350px !important;
    }

    .modal-add-machine .modal-content {
        border-radius: 16px;
        border: none;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    }

    .modal-add-machine .modal-header {
        border-bottom: none;
        padding: 20px 20px 0;
    }

    .modal-add-machine .modal-body {
        padding: 16px 20px;
    }

    .modal-add-machine .modal-footer {
        border-top: none;
        padding: 0 20px 20px;
    }

    .modal-add-machine .modal-title {
        font-weight: 700;
        font-size: 1.1rem;
        color: #1f2937;
    }

    /* Step Indicator */
    .add-machine-steps {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-bottom: 16px;
    }

    .add-machine-step {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #e5e7eb;
        transition: all 0.3s ease;
    }

    .add-machine-step.active {
        background: #10b981;
        transform: scale(1.2);
    }

    .add-machine-step.completed {
        background: #10b981;
    }

    /* Step Content */
    .add-machine-content {
        display: none;
    }

    .add-machine-content.active {
        display: block;
        animation: fadeSlide 0.3s ease;
    }

    @keyframes fadeSlide {
        from {
            opacity: 0;
            transform: translateY(8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Compact Labels */
    .modal-add-machine .compact-label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6b7280;
        margin-bottom: 4px;
    }

    .modal-add-machine .compact-input {
        padding: 10px 12px;
        font-size: 0.9rem;
        border-radius: 10px;
        border: 2px solid #e5e7eb;
        transition: border-color 0.2s;
    }

    .modal-add-machine .compact-input:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }

    /* Map Container */
    .modal-add-machine .compact-map {
        height: 120px;
        border-radius: 10px;
        border: 2px solid #e5e7eb;
        margin-bottom: 10px;
    }

    /* Search Row */
    .modal-add-machine .search-row {
        display: flex;
        gap: 8px;
        margin-bottom: 10px;
    }

    .modal-add-machine .search-row input {
        flex: 1;
    }

    .modal-add-machine .btn-search {
        background: #10b981;
        border: none;
        border-radius: 10px;
        padding: 8px 14px;
        color: white;
        cursor: pointer;
        transition: background 0.2s;
    }

    .modal-add-machine .btn-search:hover {
        background: #059669;
    }

    /* Wizard Buttons */
    .modal-add-machine .btn-wizard {
        width: 100%;
        padding: 12px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.2s;
    }

    .modal-add-machine .btn-wizard-primary {
        background: linear-gradient(135deg, #10b981, #059669);
        border: none;
        color: white;
    }

    .modal-add-machine .btn-wizard-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        color: white;
    }

    .modal-add-machine .btn-wizard-secondary {
        background: #f3f4f6;
        border: none;
        color: #374151;
    }

    .modal-add-machine .btn-wizard-secondary:hover {
        background: #e5e7eb;
    }

    .modal-add-machine .btn-row-split {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    /* Coords Grid */
    .modal-add-machine .coords-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-top: 8px;
    }

    .modal-add-machine .coords-grid input {
        font-size: 0.8rem;
        padding: 6px 10px;
    }

    /* Success Modal Styles */
    .modal-success .credentials-box {
        background: #f0fdf4;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 16px;
    }

    .modal-success .api-key-display {
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        background: #dcfce7;
        padding: 8px 12px;
        border-radius: 8px;
        word-break: break-all;
    }

    .modal-success .btn-group-creds {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .modal-success .btn-cred {
        flex: 1;
        min-width: 80px;
        padding: 8px 12px;
        font-size: 0.8rem;
        border-radius: 8px;
    }
</style>

<div class="modal fade modal-add-machine" id="addMachineModal" tabindex="-1" aria-labelledby="addMachineModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMachineModalLabel">Add New RVM Machine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addMachineForm">
                <div class="modal-body">
                    <!-- Step Indicator -->
                    <div class="add-machine-steps">
                        <div class="add-machine-step active" data-step="1"></div>
                        <div class="add-machine-step" data-step="2"></div>
                    </div>

                    <!-- Error Display -->
                    <div class="alert alert-danger d-none mb-3" id="addMachineErrors"></div>

                    <!-- Step 1: Basic Info -->
                    <div class="add-machine-content active" id="addMachineStep1">
                        <div class="mb-3">
                            <label class="compact-label">Name *</label>
                            <input type="text" class="form-control compact-input" id="machineName" name="name"
                                placeholder="e.g., RVM Mall Grand Indonesia" required>
                        </div>
                        <div class="mb-3">
                            <label class="compact-label">Initial Status</label>
                            <select class="form-select compact-input" id="machineStatus" name="status">
                                <option value="offline" selected>Offline</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="compact-label">Notes</label>
                            <textarea class="form-control compact-input" id="machineNotes" name="notes" rows="2"
                                placeholder="Optional notes..."></textarea>
                        </div>
                    </div>

                    <!-- Step 2: Location -->
                    <div class="add-machine-content" id="addMachineStep2">
                        <div class="search-row">
                            <input type="text" class="form-control compact-input" id="addMachineLocationSearch"
                                placeholder="Search location...">
                            <button type="button" class="btn-search" id="addMachineSearchBtn">
                                <i class="ti tabler-search"></i>
                            </button>
                        </div>
                        <div id="addMachineMap" class="compact-map"></div>
                        <div class="mb-2">
                            <label class="compact-label">Address</label>
                            <input type="text" class="form-control compact-input" id="machineAddress"
                                name="location_address" placeholder="Click map or type address...">
                        </div>
                        <div class="coords-grid">
                            <div>
                                <label class="compact-label">LAT</label>
                                <input type="text" class="form-control compact-input" id="machineLat" name="latitude"
                                    readonly>
                            </div>
                            <div>
                                <label class="compact-label">LNG</label>
                                <input type="text" class="form-control compact-input" id="machineLng" name="longitude"
                                    readonly>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <!-- Step 1 Buttons -->
                    <div id="addMachineStep1Buttons" class="w-100">
                        <button type="button" class="btn btn-wizard btn-wizard-primary"
                            onclick="machineWizard.goToStep(2)">
                            Next <i class="ti tabler-arrow-right"></i>
                        </button>
                    </div>
                    <!-- Step 2 Buttons -->
                    <div id="addMachineStep2Buttons" class="w-100 btn-row-split" style="display:none;">
                        <button type="button" class="btn btn-wizard btn-wizard-secondary"
                            onclick="machineWizard.goToStep(1)">
                            <i class="ti tabler-arrow-left"></i> Back
                        </button>
                        <button type="submit" class="btn btn-wizard btn-wizard-primary" id="addMachineSubmit">
                            <span class="spinner-border spinner-border-sm d-none me-1" id="addMachineSpinner"></span>
                            <i class="ti tabler-check"></i> Save
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Success Modal with Credentials - High Contrast Terminal Style -->
<div class="modal fade" id="machineSuccessModal" tabindex="-1" aria-hidden="true">
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
                    <h5 style="margin: 0; color: white; font-weight: 700; font-size: 16px;">RVM Berhasil Ditambahkan
                    </h5>
                    <p style="margin: 0; color: rgba(255,255,255,0.8); font-size: 12px;">API Credentials untuk
                        konfigurasi Edge</p>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
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
                        <input type="text" id="successSerialNumber" readonly
                            style="flex: 1; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 14px; font-size: 18px; font-weight: 700; color: #111827; letter-spacing: 0.5px; font-family: 'Inter', system-ui, sans-serif;">
                        <button class="btn btn-sm" onclick="machineWizard.copySerial()"
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
                        <code id="successApiKeyDisplay"
                            style="display: block; color: #4ade80; font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace; font-size: 13px; word-break: break-all; line-height: 1.6; letter-spacing: 0.5px;">
                            ••••••••••••••••••••••••••••••••
                        </code>
                        <input type="hidden" id="successApiKey" value="">
                    </div>
                    <p style="margin: 8px 0 0 0; font-size: 12px; color: #dc2626; font-style: italic;">
                        <i class="ti tabler-alert-triangle" style="margin-right: 4px;"></i>
                        Salin kunci ini sekarang. Kunci tidak akan ditampilkan lagi.
                    </p>

                    <!-- Action Buttons -->
                    <div style="display: flex; gap: 8px; margin-top: 12px;">
                        <button class="btn btn-sm" onclick="machineWizard.toggleApiKey()" id="btn-toggle-machine-apikey"
                            style="flex: 1; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 12px; color: #475569; font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                            <i class="ti tabler-eye"></i> Show
                        </button>
                        <button class="btn btn-sm" onclick="machineWizard.copyApiKey()"
                            style="flex: 1; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 12px; color: #475569; font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                            <i class="ti tabler-copy"></i> Copy
                        </button>
                    </div>
                </div>

                <!-- Download JSON Button -->
                <button class="btn w-100" onclick="machineWizard.downloadCredentials()"
                    style="background: linear-gradient(135deg, #10b981, #059669); border: none; border-radius: 10px; padding: 12px 16px; color: white; font-weight: 600; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s;">
                    <i class="ti tabler-download"></i> Download Credentials (JSON)
                </button>

                <!-- Done Button -->
                <button type="button" class="btn btn-light w-100 mt-3" data-bs-dismiss="modal"
                    style="background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 16px; color: #374151; font-weight: 500; font-size: 14px;">
                    Selesai
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content"
            style="border-radius: 16px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.2);">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="ti tabler-alert-triangle text-danger me-2"></i>
                    Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="delete-confirm-message" class="text-center py-3">
                    <!-- Dynamic content will be inserted here -->
                </div>
                <div id="delete-skipped-list" class="d-none">
                    <div class="alert alert-warning mb-0" style="border-radius: 10px;">
                        <small><strong>Dilewati (memiliki assignment):</strong></small>
                        <div id="skipped-names" class="mt-1" style="font-size: 13px;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    <i class="ti tabler-x me-1"></i>Batal
                </button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn">
                    <i class="ti tabler-trash me-1"></i>Hapus
                </button>
            </div>
        </div>
    </div>
</div>