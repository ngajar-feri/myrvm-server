<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ti tabler-cpu me-2"></i>Edge Devices Management
                </h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#registerDeviceModal" data-bs-toggle="tooltip"
                    title="Register a new Edge Device (Jetson/ESP32)">
                    <i class="ti tabler-plus me-1"></i>Register Device
                </button>
            </div>

            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <select id="device-status-filter" class="form-select" data-bs-toggle="tooltip"
                            title="Filter devices by status">
                            <option value="">All Devices</option>
                            <option value="online">Online</option>
                            <option value="offline">Offline</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-label-secondary w-100"
                            onclick="deviceManagement.refreshCurrentView()" data-bs-toggle="tooltip"
                            title="Refresh device list">
                            <i class="ti tabler-refresh me-1"></i>Refresh
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-label-warning w-100" id="toggle-trash-btn"
                            onclick="deviceManagement.toggleTrashedView()" data-bs-toggle="tooltip"
                            title="View deleted devices (Trash)">
                            <i class="ti tabler-trash me-1"></i>Kotak Sampah
                        </button>
                    </div>
                </div>

                <!-- Stats -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-2">
                        <div class="card stats-card stats-card-success mb-0">
                            <div class="card-body text-center">
                                <div class="avatar mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-label-success">
                                        <i class="ti tabler-wifi"></i>
                                    </span>
                                </div>
                                <h5 class="mb-0" id="devices-online">0</h5>
                                <small>Online</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card stats-card stats-card-danger mb-0">
                            <div class="card-body text-center">
                                <div class="avatar mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-label-danger">
                                        <i class="ti tabler-wifi-off"></i>
                                    </span>
                                </div>
                                <h5 class="mb-0" id="devices-offline">0</h5>
                                <small>Offline</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card stats-card stats-card-primary mb-0">
                            <div class="card-body text-center">
                                <div class="avatar mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-label-primary">
                                        <i class="ti tabler-cpu"></i>
                                    </span>
                                </div>
                                <h6 class="mb-0" id="avg-cpu">0%</h6>
                                <small>Avg CPU</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card stats-card stats-card-info mb-0">
                            <div class="card-body text-center">
                                <div class="avatar mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-label-info">
                                        <i class="ti tabler-device-desktop"></i>
                                    </span>
                                </div>
                                <h6 class="mb-0" id="avg-gpu">0%</h6>
                                <small>Avg GPU</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card stats-card stats-card-warning mb-0">
                            <div class="card-body text-center">
                                <div class="avatar mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-label-warning">
                                        <i class="ti tabler-temperature"></i>
                                    </span>
                                </div>
                                <h6 class="mb-0" id="avg-temp">0°C</h6>
                                <small>Avg Temp</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card stats-card stats-card-primary mb-0">
                            <div class="card-body text-center">
                                <div class="avatar mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-label-primary">
                                        <i class="ti tabler-server"></i>
                                    </span>
                                </div>
                                <h5 class="mb-0" id="total-devices">0</h5>
                                <small>Total</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Devices Grid -->
                <div class="row g-3" id="devices-grid">
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

<!-- Device Monitoring Modal -->
<div class="modal fade" id="deviceMonitorModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="device-serial">Device Monitoring</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="device-monitor-content">
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Register Device Modal (Bio-Digital Minimalism 2026) -->
<style>
    /* Bio-Digital Minimalism Styles */
    .modal-minimalist .modal-dialog {
        max-width: 380px;
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

    /* Threshold Slider with Gradient */
    .threshold-slider {
        -webkit-appearance: none;
        appearance: none;
        width: 100%;
        height: 10px;
        border-radius: 5px;
        background: linear-gradient(to right,
                #22c55e 0%,
                /* Green - Success */
                #22c55e 25%,
                #eab308 50%,
                /* Yellow - Warning */
                #ef4444 85%,
                /* Red - Danger */
                #ef4444 100%);
        outline: none;
        cursor: pointer;
    }

    .threshold-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #fff;
        border: 3px solid #7c3aed;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        cursor: pointer;
        transition: transform 0.15s ease;
    }

    .threshold-slider::-webkit-slider-thumb:hover {
        transform: scale(1.15);
    }

    .threshold-slider::-moz-range-thumb {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #fff;
        border: 3px solid #7c3aed;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        cursor: pointer;
    }

    /* Compact Map */
    .modal-minimalist #device-map {
        height: 180px;
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
</style>

<div class="modal fade modal-minimalist" id="registerDeviceModal" tabindex="-1"
    aria-labelledby="registerDeviceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="registerDeviceModalLabel">Register Edge Device</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="register-device-form" action="javascript:void(0);">
                <div class="modal-body">
                    <!-- Step Indicator -->
                    <div class="step-indicator">
                        <div class="step-dot active" data-step="1"></div>
                        <div class="step-dot" data-step="2"></div>
                        <div class="step-dot" data-step="3"></div>
                    </div>

                    <!-- Step 1: Select RVM -->
                    <div class="step-content active" id="step-1">
                        <div class="step-title">1. Select RVM Machine</div>

                        <div class="mb-3">
                            <label class="form-label">RVM Machine</label>
                            <div class="position-relative">
                                <input type="text" id="rvm-machine-search" class="form-control"
                                    placeholder="Search machine..." autocomplete="off" required>
                                <input type="hidden" name="rvm_machine_id" id="rvm-machine-id" required>
                                <div id="rvm-search-results" class="dropdown-menu w-100"
                                    style="max-height: 200px; overflow-y: auto; display: none; border-radius: 12px;">
                                </div>
                            </div>
                            <div class="form-text">Select the RVM for this device</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location_name" id="location-name-display" class="form-control"
                                placeholder="Auto-filled from RVM" readonly style="background: #f9fafb;">
                        </div>
                    </div>

                    <!-- Step 2: Configure -->
                    <div class="step-content" id="step-2">
                        <div class="step-title">2. Configuration</div>

                        <div class="mb-3">
                            <label class="form-label">Controller Type</label>
                            <select name="controller_type" class="form-select" required>
                                <option value="NVIDIA Jetson" selected>NVIDIA Jetson</option>
                                <option value="RaspberryPI">Raspberry PI</option>
                                <option value="ESP32">ESP32</option>
                                <option value="Arduino">Arduino</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <!-- Threshold Slider with Gradient -->
                        <div class="mb-3">
                            <label class="form-label">Threshold Full <span id="threshold-value"
                                    class="badge bg-warning">90%</span></label>
                            <input type="range" name="threshold_full" id="threshold-slider"
                                class="form-range threshold-slider" value="90" min="0" max="100" step="5">
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-success fw-semibold">0%</small>
                                <small class="text-warning fw-semibold">50%</small>
                                <small class="text-danger fw-semibold">100%</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes (Optional)</label>
                            <textarea name="description" class="form-control" rows="2"
                                placeholder="Additional notes..."></textarea>
                        </div>

                        <!-- Hidden fields -->
                        <input type="hidden" name="camera_id" value="">
                        <input type="hidden" name="ai_model_version" value="">
                        <input type="hidden" name="status" value="maintenance">
                        <input type="hidden" name="inventory_code" id="inventory-code" value="">
                    </div>

                    <!-- Step 3: Location -->
                    <div class="step-content" id="step-3">
                        <div class="step-title">3. Set Location</div>

                        <div class="mb-2">
                            <div class="input-group">
                                <input type="text" id="location-search-input" class="form-control"
                                    placeholder="Search location..." style="border-radius: 12px 0 0 12px;">
                                <button class="btn btn-primary" type="button" id="location-search-btn"
                                    style="border-radius: 0 12px 12px 0;">
                                    <i class="ti tabler-search"></i>
                                </button>
                            </div>
                        </div>

                        <div id="device-map"></div>

                        <div class="coords-box">
                            <div class="coords-label mb-1">Address</div>
                            <input type="text" name="address" id="device-address"
                                class="form-control form-control-sm mb-2" readonly placeholder="Click map...">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="coords-label">Lat</div>
                                    <input type="text" name="latitude" id="device-latitude"
                                        class="form-control form-control-sm" readonly>
                                </div>
                                <div class="col-6">
                                    <div class="coords-label">Lng</div>
                                    <input type="text" name="longitude" id="device-longitude"
                                        class="form-control form-control-sm" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <div class="d-flex gap-2 w-100">
                        <button type="button" class="btn btn-outline-secondary btn-wizard" id="wizard-back"
                            style="display: none;">
                            <i class="ti tabler-arrow-left me-1"></i>Back
                        </button>
                        <button type="button" class="btn btn-primary btn-wizard flex-grow-1" id="wizard-next">
                            Next<i class="ti tabler-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Success Modal (API Key Display) -->
<div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="ti tabler-check me-2"></i>Device Registered Successfully!</h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning mb-3">
                    <i class="ti tabler-alert-triangle me-1"></i>
                    <strong>Important:</strong> Copy the API Key below. It will NOT be shown again!
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Device ID</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="success-device-id" readonly>
                        <button class="btn btn-outline-secondary" type="button"
                            onclick="copyToClipboard('success-device-id')" data-bs-toggle="tooltip"
                            title="Copy Device ID">
                            <i class="ti tabler-copy"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">API Key <span class="badge bg-danger">Show Once</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" id="success-api-key" readonly>
                        <button class="btn btn-outline-secondary" type="button"
                            onclick="copyToClipboard('success-api-key')" data-bs-toggle="tooltip" title="Copy API Key">
                            <i class="ti tabler-copy"></i>
                        </button>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary" data-bs-toggle="tooltip"
                        title="Download device configuration as JSON file" onclick="downloadConfig()">
                        <i class="ti tabler-download me-1"></i>Download Config File (.json)
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal" onclick="window.refreshPage()">
                    <i class="ti tabler-check me-1"></i>Done
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Include Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Edit Device Modal -->
<div class="modal fade" id="editDeviceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti tabler-edit me-2"></i>Edit Device</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="edit-device-form">
                <div class="modal-body">
                    <input type="hidden" id="edit-device-id">

                    <div class="mb-3">
                        <label class="form-label">Device ID</label>
                        <input type="text" class="form-control" id="edit-device-serial" readonly disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Location Name</label>
                        <input type="text" class="form-control" id="edit-location-name" name="location_name">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="edit-status" name="status">
                            <option value="online">Online</option>
                            <option value="offline">Offline</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Controller Type</label>
                        <select class="form-select" id="edit-controller-type" name="controller_type">
                            <option value="NVIDIA Jetson">NVIDIA Jetson</option>
                            <option value="RaspberryPI">Raspberry PI</option>
                            <option value="ESP32">ESP32</option>
                            <option value="Arduino">Arduino</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Threshold Full <span id="edit-threshold-value"
                                class="badge bg-warning">90%</span></label>
                        <input type="range" class="form-range" id="edit-threshold" name="threshold_full" min="0"
                            max="100" step="5" value="90">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="edit-description" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti tabler-check me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteDeviceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="ti tabler-trash me-2"></i>Hapus Device</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="delete-device-id">

                <div class="alert alert-warning">
                    <i class="ti tabler-alert-triangle me-1"></i>
                    <strong>Perhatian:</strong> Device akan dipindahkan ke <strong>Kotak Sampah (Trash)</strong>.
                    RVM Machine yang terhubung akan di-unlink dan tersedia untuk registrasi baru.
                </div>

                <p class="mb-2">Anda yakin ingin menghapus device ini?</p>
                <p class="text-muted mb-0" id="delete-device-info"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" onclick="deviceManagement.deleteDevice()">
                    <i class="ti tabler-trash me-1"></i>Hapus ke Trash
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Regenerate API Key Modal -->
<div class="modal fade" id="regenerateKeyModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="ti tabler-key me-2"></i>Regenerate API Key</h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger mb-3">
                    <i class="ti tabler-alert-circle me-1"></i>
                    <strong>Warning:</strong> API Key lama akan dihapus dan tidak bisa digunakan lagi!
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Device ID</label>
                    <input type="text" class="form-control" id="regen-device-id" readonly>
                </div>

                <div class="mb-3" id="new-key-container" style="display: none;">
                    <label class="form-label fw-semibold">New API Key <span class="badge bg-danger">Show
                            Once</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" id="regen-api-key" readonly>
                        <button class="btn btn-outline-secondary" type="button"
                            onclick="copyToClipboard('regen-api-key')">
                            <i class="ti tabler-copy"></i>
                        </button>
                    </div>
                </div>

                <div class="d-grid gap-2" id="regen-actions">
                    <button class="btn btn-warning" id="btn-confirm-regen"
                        onclick="deviceManagement.confirmRegenerateKey()">
                        <i class="ti tabler-refresh me-1"></i>Generate New API Key
                    </button>
                </div>

                <div class="d-grid gap-2" id="download-actions" style="display: none;">
                    <button class="btn btn-outline-primary" onclick="deviceManagement.downloadNewConfig()">
                        <i class="ti tabler-download me-1"></i>Download Config File
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal"
                    onclick="deviceManagement.loadDevices()">
                    <i class="ti tabler-check me-1"></i>Done
                </button>
            </div>
        </div>
    </div>
</div>