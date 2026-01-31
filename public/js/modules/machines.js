/**
 * RVM Machines Management Module
 * Handles machines grid, monitoring, stats
 */

class MachineManagement {
    constructor() {
        this.machines = [];
        this.viewMode = 'grid'; // grid or list
        this.bootstrapReady = false;
        this.pollingTimer = null;
        this.globalPollingTimer = null; // New timer for machine list
        this.init();
    }

    init() {
        document.addEventListener('pageLoaded', (e) => {
            if (e.detail.page === 'machines') {
                this.waitForBootstrap().then(() => {
                    this.setupEventListeners();
                    this.loadMachines();
                    this.startGlobalPolling(); // Start global polling
                });
            } else {
                this.stopGlobalPolling(); // Stop if navigating away
            }
        });

        if (window.location.pathname.includes('/machines')) {
            this.waitForBootstrap().then(() => {
                this.setupEventListeners();
                this.loadMachines();
                this.startGlobalPolling(); // Start global polling
            });
        }
    }

    startGlobalPolling() {
        if (this.globalPollingTimer) clearInterval(this.globalPollingTimer);
        this.globalPollingTimer = setInterval(() => {
            // Only poll if the machines grid exists (we are on the machines page)
            if (document.getElementById('machines-grid')) {
                this.loadMachines();
            } else {
                this.stopGlobalPolling();
            }
        }, 10000); // Poll every 10 seconds (optimized from 30s)
    }

    stopGlobalPolling() {
        if (this.globalPollingTimer) {
            clearInterval(this.globalPollingTimer);
            this.globalPollingTimer = null;
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

    setupEventListeners() {
        // Status filter
        const statusFilter = document.getElementById('status-filter');
        if (statusFilter) {
            statusFilter.addEventListener('change', () => this.loadMachines());
        }

        // Location filter
        const locationFilter = document.getElementById('location-filter');
        if (locationFilter) {
            locationFilter.addEventListener('input', this.debounce(() => this.loadMachines(), 500));
        }

        // Toggle view
        const toggleView = document.getElementById('toggle-view');
        if (toggleView) {
            toggleView.addEventListener('click', () => this.toggleView());
        }

        // Add Machine form submit - use event delegation since modal is moved to body by SPA
        document.addEventListener('submit', (e) => {
            if (e.target && e.target.id === 'addMachineForm') {
                e.preventDefault();
                e.stopPropagation();
                this.addMachine();
            }
        });

        // Reset form when modal is hidden - use event delegation
        document.addEventListener('hidden.bs.modal', (e) => {
            if (e.target && e.target.id === 'addMachineModal') {
                document.getElementById('addMachineForm')?.reset();
                document.getElementById('addMachineErrors')?.classList.add('d-none');
            }
            if (e.target && e.target.id === 'machineDetailModal') {
                if (this.pollingTimer) clearInterval(this.pollingTimer);
                this.pollingTimer = null;
            }
        });
    }

    async loadMachines() {
        try {
            const statusFilter = document.getElementById('status-filter')?.value || '';
            const locationFilter = document.getElementById('location-filter')?.value || '';

            const params = new URLSearchParams({ status: statusFilter, location: locationFilter });

            // Use apiHelper with Bearer Token for authenticated API call
            console.log('DEBUG: Fetching machines. Token length:', (window.API_TOKEN || '').length);
            const response = await apiHelper.get(`/api/v1/rvm-machines?${params}`);

            if (response) {
                console.log('DEBUG: API Response status:', response.status);
            } else {
                console.log('DEBUG: API Response is null (likely auth redirect)');
            }

            if (!response || !response.ok) {
                 const text = response ? await response.text() : 'No response';
                 console.error('DEBUG: API Error Body:', text);
                 throw new Error('Failed to load machines');
            }

            const data = await response.json();
            this.machines = data.data || data;

            // DEBUG: Log machine statuses to console
            console.log('DEBUG: Machine Statuses:', this.machines.map(m => ({ 
                name: m.name, 
                status: m.status,
                last_ping: m.last_ping,
                edge_status: m.edge_device?.status
            })));

            this.renderMachines();
            this.updateStats();

        } catch (error) {
            console.error('Error loading machines:', error);
            this.showError('Failed to load machines');
        }
    }

    renderMachines() {
        const grid = document.getElementById('machines-grid');
        if (!grid) return;

        if (this.machines.length === 0) {
            grid.innerHTML = `
                <div class="col-12">
                    <div class="empty-state">
                        <i class="ti tabler-device-desktop-analytics empty-state-icon"></i>
                        <div class="empty-state-title">No machines found</div>
                    </div>
                </div>
            `;
            return;
        }

        grid.innerHTML = this.machines.map(machine => {
            const edgeDevice = machine.edge_device;
            const edgeStatus = edgeDevice?.status || 'not_registered';
            const edgeStatusBadge = edgeDevice
                ? `<span class="badge bg-label-${edgeDevice.status === 'online' ? 'success' : 'secondary'}" data-bs-toggle="tooltip" title="Edge Device: ${edgeDevice.status}">
                    <i class="ti tabler-cpu"></i>
                   </span>`
                : `<span class="badge bg-label-warning" data-bs-toggle="tooltip" title="No Edge Device">
                    <i class="ti tabler-cpu-off"></i>
                   </span>`;

            // Check if machine can be deleted (no assignments)
            const canDelete = (machine.technicians_count || 0) === 0;
            const deleteBtn = canDelete
                ? `<button class="btn btn-sm btn-label-danger" onclick="event.stopPropagation(); machineManagement.deleteMachine(${machine.id}, '${this.escapeHtml(machine.name)}')" 
                    data-bs-toggle="tooltip" title="Delete Machine">
                    <i class="ti tabler-trash"></i>
                   </button>`
                : `<span class="badge bg-label-secondary" data-bs-toggle="tooltip" title="Cannot delete: Has ${machine.technicians_count} assignment(s)">
                    <i class="ti tabler-lock"></i>
                   </span>`;

            return `
            <div class="col-md-4">
                <div class="card card-hoverable position-relative">
                    <!-- Checkbox for multi-select -->
                    <div class="position-absolute" style="top: 10px; left: 10px; z-index: 10;">
                        <input type="checkbox" class="form-check-input machine-checkbox" 
                               data-id="${machine.id}" 
                               data-name="${this.escapeHtml(machine.name)}"
                               data-can-delete="${canDelete}"
                               onclick="event.stopPropagation(); machineManagement.updateBulkSelection()">
                    </div>
                    <div class="card-body" style="padding-left: 40px;" onclick="machineManagement.viewMachine(${machine.id})">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="card-title mb-0">${this.escapeHtml(machine.name)}</h6>
                            <div class="d-flex gap-1 align-items-center">
                                ${edgeStatusBadge}
                                <span class="badge badge-status-${machine.status || 'offline'}">
                                    ${machine.status || 'offline'}
                                </span>
                                ${deleteBtn}
                            </div>
                        </div>
                        <p class="text-muted small mb-2" title="${this.escapeHtml(machine.location_address || machine.location || '')}">
                            <i class="ti tabler-map-pin me-1"></i>
                            ${this.escapeHtml(this.truncateAddress(machine.location_address || machine.location || (machine.latitude && machine.longitude ? `${machine.latitude}, ${machine.longitude}` : 'No location')))}
                        </p>
                        
                        <!-- Capacity Bar -->
                        <div class="mb-2">
                            ${(() => {
                                const styles = this.getCapacityStyles(machine.capacity_percentage);
                                return `
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>Bin Capacity</span>
                                    <span style="color: ${styles.color}; font-weight: 600;">${styles.percentage}%</span>
                                </div>
                                <div class="progress ${styles.pulseClass}" style="height: 8px; background-color: rgba(0,0,0,0.05);">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: ${styles.percentage}%; background-color: ${styles.color}; transition: width 1s ease-in-out;" 
                                         aria-valuenow="${styles.percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                `;
                            })()}
                        </div>
                        
                        <!-- Stats -->
                        <div class="row g-2 text-center small">
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="fw-semibold">${machine.today_count || 0}</div>
                                    <div class="text-muted">Today</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="fw-semibold">${machine.total_count || 0}</div>
                                    <div class="text-muted">Total</div>
                                </div>
                            </div>
                        </div>

                        ${edgeDevice ? `
                        <div class="mt-2 pt-2 border-top small text-muted">
                            <i class="ti tabler-heart-rate-monitor me-1"></i>
                            Last heartbeat: ${this.getLastSeen(edgeDevice.updated_at)}
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `}).join('');

        // Initialize tooltips
        const tooltips = grid.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(el => new bootstrap.Tooltip(el));
    }

    updateStats() {
        const online = this.machines.filter(m => m.status === 'online').length;
        const offline = this.machines.filter(m => m.status === 'offline').length;
        const maintenance = this.machines.filter(m => m.status === 'maintenance').length;
        const totalTransactions = this.machines.reduce((sum, m) => sum + (m.total_count || 0), 0);

        document.getElementById('online-count').textContent = online;
        document.getElementById('offline-count').textContent = offline;
        document.getElementById('maintenance-count').textContent = maintenance;
        document.getElementById('total-transactions').textContent = totalTransactions;
    }

    async loadMachineDetailContext(machineId, contentContainer, isPolling = false) {
        try {
            const response = await apiHelper.get(`/api/v1/rvm-machines/${machineId}`);

            if (!response || !response.ok) throw new Error('Failed to load machine details');

            const data = await response.json();
            const machine = data.data || data;
            
            // Render content
            const html = this.renderMachineDetailTemplate(machine);
            
            // Update content
            contentContainer.innerHTML = html;

            // Re-init tooltips
            const tooltips = contentContainer.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(el => new bootstrap.Tooltip(el));

        } catch (error) {
            console.error('Error loading machine details:', error);
            if (!isPolling) {
                 contentContainer.innerHTML = '<div class="alert alert-danger">Failed to load machine details</div>';
            }
        }
    }

    renderMachineDetailTemplate(machine) {
        const edgeDevice = machine.edge_device;
        const telemetry = edgeDevice?.telemetry || [];
        const hwInfo = edgeDevice?.hardware_info || {};
        const sensors = hwInfo.sensors || [];
        const actuators = hwInfo.actuators || [];
        const cameras = hwInfo.cameras || [];
        const mcu = hwInfo.microcontroller || {};

        return `
            <div class="row">
                <!-- Left Column: Stats -->
                <div class="col-md-8">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <span class="badge badge-status-${machine.status}">${machine.status}</span>
                                    <div class="small text-muted mt-1">Machine Status</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            ${(() => {
                                const styles = this.getCapacityStyles(machine.capacity_percentage);
                                return `
                                <div class="card text-center ${styles.pulseClass}" style="background-color: ${styles.bg}; border-color: ${styles.color}44; transition: all 0.5s ease;">
                                    <div class="card-body p-3">
                                        <h5 class="mb-1" style="color: ${styles.color}; font-weight: 700;">${styles.percentage}%</h5>
                                        <div class="small text-muted mb-2">Bin Capacity</div>
                                        <div class="progress" style="height: 6px; background-color: rgba(0,0,0,0.05); border-radius: 10px;">
                                            <div class="progress-bar" role="progressbar" 
                                                style="width: ${styles.percentage}%; background-color: ${styles.color}; transition: width 1s ease-in-out;" 
                                                aria-valuenow="${styles.percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                                `;
                            })()}
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="mb-0">${machine.today_count || 0}</h5>
                                    <div class="small text-muted">Today</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="mb-0">${machine.total_count || 0}</h5>
                                    <div class="small text-muted">All Time</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edge Device Section - Bio-Digital 2026 -->
                    ${edgeDevice ? `
                    <div class="card mb-3" style="border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.2); background: linear-gradient(to bottom right, #f0fdf4, #ffffff);">
                        <div class="card-header d-flex justify-content-between align-items-center" style="border-bottom: 1px solid rgba(16, 185, 129, 0.1); background: transparent;">
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-2" style="color: #065f46;"><i class="ti tabler-cpu me-2"></i>Edge Device</h6>
                                <div class="spinner-grow text-success spinner-grow-sm" role="status" style="width: 0.5rem; height: 0.5rem;" title="Live Updates Active"></div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button class="btn btn-xs btn-primary btn-manual-update" data-id="${machine.id}" style="font-size: 0.65rem; padding: 2px 8px;">
                                    <i class="ti tabler-refresh me-1"></i> Update
                                </button>
                                <button class="btn btn-xs btn-outline-warning btn-restart-edge" data-id="${machine.id}" style="font-size: 0.65rem; padding: 2px 8px;">
                                    <i class="ti tabler-power me-1"></i> Restart
                                </button>
                                <span class="badge badge-status-${edgeDevice.status || 'offline'}">${edgeDevice.status || 'offline'}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 mb-3">
                                <!-- Hardware Info -->
                                <div class="col-md-6">
                                    <dl class="row mb-0 small">
                                        <dt class="col-5 text-muted">Device ID:</dt>
                                        <dd class="col-7"><code style="background: #ecfdf5; padding: 2px 6px; border-radius: 4px;">${edgeDevice.device_id || 'N/A'}</code></dd>
                                        <dt class="col-5 text-muted">Firmware:</dt>
                                        <dd class="col-7">${edgeDevice.system_info?.firmware_version || edgeDevice.firmware_version || 'v1.0.0'}</dd>
                                        <dt class="col-5 text-muted">JetPack:</dt>
                                        <dd class="col-7">${(() => {
                                            const raw = edgeDevice.system_info?.jetpack_version || 'N/A';
                                            if (raw.includes('# R')) {
                                                const relMatch = raw.match(/# R(\d+)/);
                                                const revMatch = raw.match(/REVISION:\s*([\d.]+)/);
                                                if (relMatch && revMatch) return `L4T R${relMatch[1]}.${revMatch[1]}`;
                                                if (relMatch) return `L4T R${relMatch[1]}`;
                                            }
                                            return raw;
                                        })()}</dd>
                                        <dt class="col-5 text-muted">AI Model:</dt>
                                        <dd class="col-7">${edgeDevice.system_info?.ai_models?.model_version || edgeDevice.ai_model_version || 'v1.0.0'}</dd>
                                    </dl>
                                </div>
                                <!-- Network Info -->
                                <div class="col-md-6">
                                    <dl class="row mb-0 small">
                                        <dt class="col-5 text-muted">Tailscale IP:</dt>
                                        <dd class="col-7"><code style="background: #dbeafe; padding: 2px 6px; border-radius: 4px;">${edgeDevice.tailscale_ip || 'N/A'}</code></dd>
                                        <dt class="col-5 text-muted">Local IP:</dt>
                                        <dd class="col-7"><code style="background: #f3f4f6; padding: 2px 6px; border-radius: 4px;">${edgeDevice.ip_address_local || edgeDevice.ip_address || 'N/A'}</code></dd>
                                        <dt class="col-5 text-muted">Last Heartbeat:</dt>
                                        <dd class="col-7">${this.getLastSeen(edgeDevice.updated_at)}</dd>
                                    </dl>
                                </div>
                            </div>

                            ${edgeDevice.health_metrics || edgeDevice.hardware_info ? `
                            <!-- Health Metrics -->
                            <h6 class="small fw-semibold mb-2" style="color: #065f46;"><i class="ti tabler-activity-heartbeat me-1"></i>Health Metrics</h6>
                            <div class="row g-2 mb-3">
                                <div class="col-3">
                                    <div class="text-center p-2" style="background: linear-gradient(135deg, #ecfdf5, #d1fae5); border-radius: 10px;">
                                        <div class="fw-bold" style="color: #065f46;">${(edgeDevice.health_metrics?.cpu_usage_percent || 0).toFixed(1)}%</div>
                                        <small class="text-muted">CPU</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="text-center p-2" style="background: linear-gradient(135deg, #eff6ff, #dbeafe); border-radius: 10px;">
                                        <div class="fw-bold" style="color: #1e40af;">${(edgeDevice.health_metrics?.memory_usage_percent || 0).toFixed(1)}%</div>
                                        <small class="text-muted">Memory</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="text-center p-2" style="background: linear-gradient(135deg, #fef3c7, #fde68a); border-radius: 10px;">
                                        <div class="fw-bold" style="color: #92400e;">${(edgeDevice.health_metrics?.cpu_temperature || 0).toFixed(1)}°C</div>
                                        <small class="text-muted">Temp</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="text-center p-2" style="background: linear-gradient(135deg, #f3f4f6, #e5e7eb); border-radius: 10px;">
                                        <div class="fw-bold" style="color: #374151;">${(edgeDevice.health_metrics?.disk_usage_percent || 0).toFixed(1)}%</div>
                                        <small class="text-muted">Disk</small>
                                    </div>
                                </div>
                            </div>
                            ` : ''}

                            <!-- Detailed Hardware - Spec v2.0 -->
                            ${(sensors.length > 0 || actuators.length > 0) ? `
                            <nav>
                                <div class="nav nav-tabs nav-fill mb-3" id="nav-tab" role="tablist">
                                    <button class="nav-link active small py-1" id="nav-sensors-tab" data-bs-toggle="tab" data-bs-target="#nav-sensors" type="button" role="tab">Sensors (${sensors.length})</button>
                                    <button class="nav-link small py-1" id="nav-actuators-tab" data-bs-toggle="tab" data-bs-target="#nav-actuators" type="button" role="tab">Actuators (${actuators.length})</button>
                                    <button class="nav-link small py-1" id="nav-cameras-tab" data-bs-toggle="tab" data-bs-target="#nav-cameras" type="button" role="tab">Cameras (${cameras.length})</button>
                                </div>
                            </nav>
                            <div class="tab-content" id="nav-tabContent">
                                <div class="tab-pane fade show active" id="nav-sensors" role="tabpanel">
                                    <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                                        <table class="table table-sm table-striped small mb-0">
                                            <thead><tr><th>Name</th><th>Model</th><th>Unit</th><th>Interface</th></tr></thead>
                                            <tbody>
                                                ${sensors.map(s => `<tr><td>${s.friendly_name || s.name}</td><td>${s.model}</td><td>${s.unit || '-'}</td><td>${s.interface}</td></tr>`).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="nav-actuators" role="tabpanel">
                                    <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                                        <table class="table table-sm table-striped small mb-0">
                                            <thead><tr><th>Name</th><th>Model</th><th>Interface</th></tr></thead>
                                            <tbody>
                                                ${actuators.map(a => `<tr><td>${a.friendly_name || a.name}</td><td>${a.model}</td><td>${a.interface}</td></tr>`).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="nav-cameras" role="tabpanel">
                                    <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                                        <table class="table table-sm table-striped small mb-0">
                                            <thead><tr><th>Name</th><th>Path</th><th>Status</th></tr></thead>
                                            <tbody>
                                                ${cameras.map(c => `<tr><td>${c.name}</td><td>${c.path}</td><td><span class="badge bg-${c.status === 'ready' ? 'success' : 'danger'}">${c.status}</span></td></tr>`).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            ` : ''}

                        </div>
                    </div>
                    ` : `
                    <div class="alert mb-3" style="background: linear-gradient(to right, #fef3c7, #fff7ed); border: 1px solid #fcd34d; border-radius: 10px; color: #92400e;">
                        <i class="ti tabler-alert-circle me-1"></i>
                        <strong>Waiting for Handshake:</strong> Edge Device will auto-register when the physical machine connects using the API Key.
                    </div>
                    `}

                    <!-- Telemetry Section -->
                    ${telemetry.length > 0 ? `
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="ti tabler-chart-line me-2"></i>Latest Telemetry (${telemetry.length})</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>Sensor Data</th>
                                            <th>Sync</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${telemetry.map(t => `
                                        <tr>
                                            <td class="small">${new Date(t.client_timestamp).toLocaleString()}</td>
                                            <td><code class="small">${JSON.stringify(t.sensor_data).substring(0, 50)}...</code></td>
                                            <td><span class="badge bg-${t.sync_status === 'synced' ? 'success' : 'warning'}">${t.sync_status}</span></td>
                                        </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                </div>

                <!-- Right Column: Info -->
                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header"><h6 class="mb-0">Machine Information</h6></div>
                        <div class="card-body">
                            <dl class="row mb-0 small">
                                <dt class="col-5">Serial:</dt>
                                <dd class="col-7">${machine.serial_number || 'N/A'}</dd>
                                <dt class="col-5">Location:</dt>
                                <dd class="col-7">${machine.location_address || machine.location || (machine.latitude && machine.longitude ? `${machine.latitude}, ${machine.longitude}` : 'N/A')}</dd>
                                <dt class="col-5">Last Ping:</dt>
                                <dd class="col-7">${this.getLastSeen(machine.last_ping)}</dd>
                            </dl>
                        </div>
                    </div>

                    <!-- Components Overview (Summarized) -->
                    <div class="card">
                        <div class="card-header"><h6 class="mb-0">Components Summary</h6></div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge bg-label-primary" data-bs-toggle="tooltip" title="Edge Controller">
                                    <i class="ti tabler-cpu"></i> ${edgeDevice?.type || 'Edge'}
                                </span>
                                <span class="badge bg-label-${cameras.length > 0 ? 'info' : 'secondary'}" data-bs-toggle="tooltip" title="Cameras">
                                    <i class="ti tabler-camera"></i> ${cameras.length} Cam
                                </span>
                                <span class="badge bg-label-${sensors.length > 0 ? 'success' : 'secondary'}" data-bs-toggle="tooltip" title="Sensors">
                                    <i class="ti tabler-radar"></i> ${sensors.length} Sensors
                                </span>
                                <span class="badge bg-label-${actuators.length > 0 ? 'warning' : 'secondary'}" data-bs-toggle="tooltip" title="Actuators">
                                    <i class="ti tabler-engine"></i> ${actuators.length} Acts
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    async viewMachine(machineId) {
        await this.waitForBootstrap();

        // Clear any existing timer
        if (this.pollingTimer) clearInterval(this.pollingTimer);
        this.pollingTimer = null;

        const modalEl = document.getElementById('machineDetailModal');
        if (!modalEl) {
            console.error('Machine detail modal not found');
            return;
        }

        const modal = new bootstrap.Modal(modalEl);
        const content = document.getElementById('machine-detail-content');

        modal.show();
        content.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>';

        // Initial Load
        await this.loadMachineDetailContext(machineId, content);

        // Start Auto-Polling (every 10 seconds)
        this.pollingTimer = setInterval(() => {
            // Only update if modal is still open
            const currentModal = document.getElementById('machineDetailModal');
            if (currentModal && currentModal.classList.contains('show')) {
                this.loadMachineDetailContext(machineId, content, true);
            } else {
                if (this.pollingTimer) clearInterval(this.pollingTimer);
                this.pollingTimer = null;
            }
        }, 10000);
    }

    async _old_viewMachine(machineId) {
        await this.waitForBootstrap();

        const modalEl = document.getElementById('machineDetailModal');
        if (!modalEl) {
            console.error('Machine detail modal not found');
            return;
        }

        const modal = new bootstrap.Modal(modalEl);
        const content = document.getElementById('machine-detail-content');

        modal.show();
        content.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>';

        try {
            const response = await apiHelper.get(`/api/v1/rvm-machines/${machineId}`);

            if (!response || !response.ok) throw new Error('Failed to load machine details');

            const data = await response.json();
            const machine = data.data || data;
            const edgeDevice = machine.edge_device;
            const telemetry = edgeDevice?.telemetry || [];

            document.getElementById('machine-name').textContent = machine.name;

            content.innerHTML = `
                <div class="row">
                    <!-- Left Column: Stats -->
                    <div class="col-md-8">
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <span class="badge badge-status-${machine.status}">${machine.status}</span>
                                        <div class="small text-muted mt-1">Machine Status</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h5 class="mb-0">${machine.capacity_percentage || 0}%</h5>
                                        <div class="small text-muted">Bin Capacity</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h5 class="mb-0">${machine.today_count || 0}</h5>
                                        <div class="small text-muted">Today</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h5 class="mb-0">${machine.total_count || 0}</h5>
                                        <div class="small text-muted">All Time</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edge Device Section - Bio-Digital 2026 -->
                        ${edgeDevice ? `
                        <div class="card mb-3" style="border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.2); background: linear-gradient(to bottom right, #f0fdf4, #ffffff);">
                            <div class="card-header d-flex justify-content-between align-items-center" style="border-bottom: 1px solid rgba(16, 185, 129, 0.1); background: transparent;">
                                <div class="d-flex align-items-center">
                                    <h6 class="mb-0" style="color: #065f46;"><i class="ti tabler-cpu me-2"></i>Edge Device (Auto via Handshake)</h6>
                                    <div class="spinner-grow text-success spinner-grow-sm ms-2" role="status" style="width: 0.5rem; height: 0.5rem;" title="Live Updates Active"></div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button class="btn btn-xs btn-primary btn-manual-update" data-id="${machine.id}" style="font-size: 0.65rem; padding: 2px 8px;">
                                        <i class="ti tabler-refresh me-1"></i> Update
                                    </button>
                                    <button class="btn btn-xs btn-outline-warning btn-restart-edge" data-id="${machine.id}" style="font-size: 0.65rem; padding: 2px 8px;">
                                        <i class="ti tabler-power me-1"></i> Restart
                                    </button>
                                    <span class="badge badge-status-${edgeDevice.status || 'offline'}">${edgeDevice.status || 'offline'}</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <!-- Hardware Info -->
                                    <div class="col-md-6">
                                        <dl class="row mb-0 small">
                                            <dt class="col-5 text-muted">Device ID:</dt>
                                            <dd class="col-7"><code style="background: #ecfdf5; padding: 2px 6px; border-radius: 4px;">${edgeDevice.device_id || 'N/A'}</code></dd>
                                            <dt class="col-5 text-muted">Controller:</dt>
                                            <dd class="col-7">${edgeDevice.type || edgeDevice.controller_type || 'Jetson Orin Nano'}</dd>
                                            <dt class="col-5 text-muted">Firmware:</dt>
                                            <dd class="col-7">${edgeDevice.system_info?.firmware_version || edgeDevice.firmware_version || 'v1.0.0'}</dd>
                                            <dt class="col-5 text-muted">Camera:</dt>
                                            <dd class="col-7">${edgeDevice.hardware_config?.cameras?.[0]?.name || edgeDevice.camera_id || 'CSI Camera'}</dd>
                                        </dl>
                                    </div>
                                    <!-- Network Info -->
                                    <div class="col-md-6">
                                        <dl class="row mb-0 small">
                                            <dt class="col-5 text-muted">Tailscale IP:</dt>
                                            <dd class="col-7"><code style="background: #dbeafe; padding: 2px 6px; border-radius: 4px;">${edgeDevice.tailscale_ip || 'N/A'}</code></dd>
                                            <dt class="col-5 text-muted">Local IP:</dt>
                                            <dd class="col-7"><code style="background: #f3f4f6; padding: 2px 6px; border-radius: 4px;">${edgeDevice.ip_address_local || edgeDevice.ip_address || 'N/A'}</code></dd>
                                            <dt class="col-5 text-muted">AI Model:</dt>
                                            <dd class="col-7">${edgeDevice.system_info?.ai_models?.model_version || edgeDevice.ai_model_version || 'v1.0.0'}</dd>
                                            <dt class="col-5 text-muted">Last Heartbeat:</dt>
                                            <dd class="col-7">${this.getLastSeen(edgeDevice.updated_at)}</dd>
                                        </dl>
                                    </div>
                                </div>

                                ${edgeDevice.health_metrics || edgeDevice.hardware_info ? `
                                <!-- Health Metrics - Bio-Digital Cards -->
                                <hr style="border-color: rgba(16, 185, 129, 0.1);">
                                <h6 class="small fw-semibold mb-3" style="color: #065f46;"><i class="ti tabler-activity-heartbeat me-1"></i>Health Metrics</h6>
                                <div class="row g-2">
                                    <div class="col-3">
                                        <div class="text-center p-2" style="background: linear-gradient(135deg, #ecfdf5, #d1fae5); border-radius: 10px;">
                                            <div class="fw-bold" style="color: #065f46;">${(edgeDevice.health_metrics?.cpu_usage_percent || 0).toFixed(1)}%</div>
                                            <small class="text-muted">CPU</small>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="text-center p-2" style="background: linear-gradient(135deg, #eff6ff, #dbeafe); border-radius: 10px;">
                                            <div class="fw-bold" style="color: #1e40af;">${(edgeDevice.health_metrics?.memory_usage_percent || 0).toFixed(1)}%</div>
                                            <small class="text-muted">Memory</small>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="text-center p-2" style="background: linear-gradient(135deg, #fef3c7, #fde68a); border-radius: 10px;">
                                            <div class="fw-bold" style="color: #92400e;">${(edgeDevice.health_metrics?.cpu_temperature || 0).toFixed(1)}°C</div>
                                            <small class="text-muted">Temp</small>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="text-center p-2" style="background: linear-gradient(135deg, #f3f4f6, #e5e7eb); border-radius: 10px;">
                                            <div class="fw-bold" style="color: #374151;">${(edgeDevice.health_metrics?.disk_usage_percent || 0).toFixed(1)}%</div>
                                            <small class="text-muted">Disk</small>
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        ` : `
                        <div class="alert mb-3" style="background: linear-gradient(to right, #fef3c7, #fff7ed); border: 1px solid #fcd34d; border-radius: 10px; color: #92400e;">
                            <i class="ti tabler-alert-circle me-1"></i>
                            <strong>Waiting for Handshake:</strong> Edge Device will auto-register when the physical machine connects using the API Key.
                        </div>
                        `}

                        <!-- Telemetry Section -->
                        ${telemetry.length > 0 ? `
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="ti tabler-chart-line me-2"></i>Latest Telemetry (${telemetry.length})</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Timestamp</th>
                                                <th>Sensor Data</th>
                                                <th>Sync</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${telemetry.map(t => `
                                            <tr>
                                                <td class="small">${new Date(t.client_timestamp).toLocaleString()}</td>
                                                <td><code class="small">${JSON.stringify(t.sensor_data).substring(0, 50)}...</code></td>
                                                <td><span class="badge bg-${t.sync_status === 'synced' ? 'success' : 'warning'}">${t.sync_status}</span></td>
                                            </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                    </div>

                    <!-- Right Column: Info -->
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-header"><h6 class="mb-0">Machine Information</h6></div>
                            <div class="card-body">
                                <dl class="row mb-0 small">
                                    <dt class="col-5">Serial:</dt>
                                    <dd class="col-7">${machine.serial_number || 'N/A'}</dd>
                                    <dt class="col-5">Location:</dt>
                                    <dd class="col-7">${machine.location_address || machine.location || (machine.latitude && machine.longitude ? `${machine.latitude}, ${machine.longitude}` : 'N/A')}</dd>
                                    <dt class="col-5">Last Ping:</dt>
                                    <dd class="col-7">${this.getLastSeen(machine.last_ping)}</dd>
                                </dl>
                            </div>
                        </div>

                        <!-- Components Overview -->
                        <div class="card">
                            <div class="card-header"><h6 class="mb-0">Components</h6></div>
                            <div class="card-body">
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="badge bg-label-primary" data-bs-toggle="tooltip" title="Jetson Orin Nano">
                                        <i class="ti tabler-cpu"></i> Edge Device
                                    </span>
                                    <span class="badge bg-label-info" data-bs-toggle="tooltip" title="CSI Camera">
                                        <i class="ti tabler-camera"></i> Camera
                                    </span>
                                    <span class="badge bg-label-secondary" data-bs-toggle="tooltip" title="LCD Touch Screen">
                                        <i class="ti tabler-device-tablet"></i> LCD
                                    </span>
                                    <span class="badge bg-label-warning" data-bs-toggle="tooltip" title="ESP32 Controller">
                                        <i class="ti tabler-circuit-board"></i> ESP32
                                    </span>
                                    <span class="badge bg-label-success" data-bs-toggle="tooltip" title="Sensors">
                                        <i class="ti tabler-radar"></i> Sensors
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Initialize tooltips
            const tooltips = content.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(el => new bootstrap.Tooltip(el));

        } catch (error) {
            console.error('Error loading machine details:', error);
            content.innerHTML = '<div class="alert alert-danger">Failed to load machine details</div>';
        }
    }

    getCapacityStyles(percentage) {
        const cap = percentage || 0;
        let color = '#4CAF50'; // Green
        let bg = '#f0fdf4';
        let pulseClass = '';

        if (cap > 85) {
            color = '#EF5350'; // Soft Red
            bg = '#fef2f2';
            pulseClass = 'capacity-pulse';
        } else if (cap > 60) {
            color = '#FFB74D'; // Amber
            bg = '#fffbeb';
        }

        return { color, bg, pulseClass, percentage: cap };
    }

    getLastSeen(lastSeen) {
        if (!lastSeen) return 'Never';
        const minutes = Math.floor((new Date() - new Date(lastSeen)) / 60000);
        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes} min ago`;
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        return new Date(lastSeen).toLocaleDateString();
    }

    toggleView() {
        this.viewMode = this.viewMode === 'grid' ? 'list' : 'grid';
        this.renderMachines();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Truncate address for card display (shows first part + ellipsis)
     * @param {string} address Full address string
     * @param {number} maxLength Maximum length before truncating (default: 35)
     */
    truncateAddress(address, maxLength = 35) {
        if (!address || address.length <= maxLength) return address;
        return address.substring(0, maxLength).trim() + '...';
    }

    debounce(func, wait) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    showError(message) {
        console.error(message);
        // Show toast notification
        this.showToast(message, 'danger');
    }

    showSuccess(message) {
        this.showToast(message, 'success');
    }

    showToast(message, type = 'info') {
        const toastContainer = document.querySelector('.toast-container') || this.createToastContainer();
        const toast = document.createElement('div');
        toast.className = `toast show align-items-center text-bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        toastContainer.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }

    createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }

    async addMachine() {
        const form = document.getElementById('addMachineForm');
        const submitBtn = document.getElementById('addMachineSubmit');
        const spinner = document.getElementById('addMachineSpinner');
        const errorsDiv = document.getElementById('addMachineErrors');

        if (!form) return;

        // Show loading state
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');
        errorsDiv.classList.add('d-none');

        try {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);

            const response = await apiHelper.post('/api/v1/rvm-machines', data);
            const result = await response.json();

            if (response.ok) {
                // Success - close add modal
                const addModal = bootstrap.Modal.getInstance(document.getElementById('addMachineModal'));
                if (addModal) addModal.hide();

                // Reset wizard to step 1
                machineWizard.goToStep(1);
                form.reset();

                // Show toast notification with backend message
                this.showSuccess(result.message || 'RVM berhasil ditambahkan!');

                // Refresh machine list
                this.loadMachines();
            } else {
                // Validation errors
                let errorHtml = '<ul class="mb-0">';
                if (result.errors) {
                    Object.values(result.errors).forEach(errs => {
                        errs.forEach(e => errorHtml += `<li>${e}</li>`);
                    });
                } else {
                    errorHtml += `<li>${result.message || 'Failed to add machine'}</li>`;
                }
                errorHtml += '</ul>';
                errorsDiv.innerHTML = errorHtml;
                errorsDiv.classList.remove('d-none');
            }
        } catch (error) {
            console.error('Error adding machine:', error);
            errorsDiv.innerHTML = 'Network error. Please try again.';
            errorsDiv.classList.remove('d-none');
        } finally {
            submitBtn.disabled = false;
            spinner.classList.add('d-none');
        }
    }

    /**
     * Pending delete state
     */
    pendingDelete = {
        type: null, // 'single' or 'bulk'
        id: null,
        name: null,
        ids: [],
        names: []
    };

    /**
     * Delete single machine - shows confirmation modal
     */
    deleteMachine(id, name) {
        this.pendingDelete = {
            type: 'single',
            id: id,
            name: name,
            ids: [],
            names: []
        };

        const messageEl = document.getElementById('delete-confirm-message');
        const skippedEl = document.getElementById('delete-skipped-list');

        if (messageEl) {
            messageEl.innerHTML = `
                <div class="mb-3">
                    <i class="ti tabler-trash text-danger" style="font-size: 48px;"></i>
                </div>
                <p class="mb-1"><strong>Apakah Anda yakin ingin menghapus:</strong></p>
                <p class="text-danger fw-bold">"${name}"</p>
                <p class="text-muted small mb-0">Tindakan ini tidak dapat dibatalkan.</p>
            `;
        }
        skippedEl?.classList.add('d-none');

        const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        modal.show();
    }

    /**
     * Bulk delete - shows confirmation modal with list
     */
    bulkDelete() {
        const checkboxes = document.querySelectorAll('.machine-checkbox:checked');
        const selectedIds = [];
        const selectedNames = [];
        const skippedNames = [];

        checkboxes.forEach(cb => {
            const id = parseInt(cb.dataset.id);
            const name = cb.dataset.name;
            const canDelete = cb.dataset.canDelete === 'true';

            if (canDelete) {
                selectedIds.push(id);
                selectedNames.push(name);
            } else {
                skippedNames.push(name);
            }
        });

        if (selectedIds.length === 0) {
            this.showError('Tidak ada RVM yang dapat dihapus. Semua RVM yang dipilih memiliki assignment aktif.');
            return;
        }

        this.pendingDelete = {
            type: 'bulk',
            id: null,
            name: null,
            ids: selectedIds,
            names: selectedNames
        };

        const messageEl = document.getElementById('delete-confirm-message');
        const skippedEl = document.getElementById('delete-skipped-list');
        const skippedNamesEl = document.getElementById('skipped-names');

        if (messageEl) {
            messageEl.innerHTML = `
                <div class="mb-3">
                    <i class="ti tabler-trash text-danger" style="font-size: 48px;"></i>
                </div>
                <p class="mb-1"><strong>Apakah Anda yakin ingin menghapus ${selectedIds.length} RVM?</strong></p>
                <p class="text-danger small">${selectedNames.join(', ')}</p>
                <p class="text-muted small mb-0">Tindakan ini tidak dapat dibatalkan.</p>
            `;
        }

        if (skippedNames.length > 0) {
            skippedEl?.classList.remove('d-none');
            if (skippedNamesEl) skippedNamesEl.textContent = skippedNames.join(', ');
        } else {
            skippedEl?.classList.add('d-none');
        }

        const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        modal.show();
    }

    /**
     * Execute delete after confirmation
     */
    async executeDelete() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));

        if (this.pendingDelete.type === 'single') {
            try {
                const response = await apiHelper.delete(`/api/v1/rvm-machines/${this.pendingDelete.id}`);
                const result = await response.json();

                if (response.ok) {
                    modal?.hide();
                    this.showSuccess(result.message || `RVM "${this.pendingDelete.name}" berhasil dihapus.`);
                    this.loadMachines();
                } else {
                    this.showError(result.message || 'Gagal menghapus RVM.');
                }
            } catch (error) {
                console.error('Delete failed:', error);
                this.showError('Network error. Gagal menghapus RVM.');
            }
        } else if (this.pendingDelete.type === 'bulk') {
            try {
                const response = await apiHelper.post('/api/v1/rvm-machines/bulk-delete', { ids: this.pendingDelete.ids });
                const result = await response.json();

                if (response.ok) {
                    modal?.hide();
                    this.showSuccess(result.message);
                    this.clearSelection();
                    this.loadMachines();
                } else {
                    this.showError(result.message || 'Gagal menghapus RVM.');
                }
            } catch (error) {
                console.error('Bulk delete failed:', error);
                this.showError('Network error. Gagal menghapus RVM.');
            }
        }

        // Reset pending delete
        this.pendingDelete = { type: null, id: null, name: null, ids: [], names: [] };
    }

    /**
     * Update bulk selection UI
     * Uses static controls from blade template (matching Users page pattern)
     */
    updateBulkSelection() {
        const checkboxes = document.querySelectorAll('.machine-checkbox:checked');
        const deleteBtn = document.getElementById('delete-selected-btn');
        const clearBtn = document.getElementById('clear-selection-btn');
        const countSpan = document.getElementById('selected-count');

        if (checkboxes.length > 0) {
            deleteBtn?.classList.remove('d-none');
            clearBtn?.classList.remove('d-none');
            if (countSpan) countSpan.textContent = checkboxes.length;
        } else {
            deleteBtn?.classList.add('d-none');
            clearBtn?.classList.add('d-none');
            if (countSpan) countSpan.textContent = '0';
        }
    }

    /**
     * Clear selection
     */
    clearSelection() {
        document.querySelectorAll('.machine-checkbox:checked').forEach(cb => cb.checked = false);
        this.updateBulkSelection();
    }
}

const machineManagement = new MachineManagement();

// Wire up delete confirmation button
document.addEventListener('DOMContentLoaded', () => {
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', () => machineManagement.executeDelete());
    }
});

/**
 * Machine Wizard - Multi-Step Add Machine with Map
 * Bio-Digital Minimalism 2026
 */
const machineWizard = {
    map: null,
    marker: null,
    currentStep: 1,
    lastCredentials: null,
    lastMachineId: null,
    apiKeyVisible: false,

    /**
     * Navigate to wizard step
     */
    goToStep(step) {
        this.currentStep = step;

        // Update step indicators
        document.querySelectorAll('.add-machine-step').forEach((dot, i) => {
            dot.classList.remove('active', 'completed');
            if (i + 1 < step) dot.classList.add('completed');
            if (i + 1 === step) dot.classList.add('active');
        });

        // Show/hide content
        document.querySelectorAll('.add-machine-content').forEach((content, i) => {
            content.classList.toggle('active', i + 1 === step);
        });

        // Show/hide buttons
        document.getElementById('addMachineStep1Buttons').style.display = step === 1 ? 'block' : 'none';
        document.getElementById('addMachineStep2Buttons').style.display = step === 2 ? 'grid' : 'none';

        // Initialize map when entering step 2
        if (step === 2) {
            setTimeout(() => this.initMap(), 100);
        }
    },

    /**
     * Initialize Leaflet map
     */
    initMap() {
        const mapContainer = document.getElementById('addMachineMap');
        if (!mapContainer || this.map) return;

        // Default: Jakarta
        const defaultLat = -6.2088;
        const defaultLng = 106.8456;

        this.map = L.map('addMachineMap').setView([defaultLat, defaultLng], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OSM'
        }).addTo(this.map);

        // Click to place marker
        this.map.on('click', (e) => {
            this.setMarker(e.latlng.lat, e.latlng.lng);
            this.reverseGeocode(e.latlng.lat, e.latlng.lng);
        });

        // Search button
        document.getElementById('addMachineSearchBtn')?.addEventListener('click', () => this.searchLocation());
        document.getElementById('addMachineLocationSearch')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.searchLocation();
            }
        });
    },

    /**
     * Set marker on map
     */
    setMarker(lat, lng) {
        if (this.marker) {
            this.marker.setLatLng([lat, lng]);
        } else {
            this.marker = L.marker([lat, lng]).addTo(this.map);
        }
        this.map.setView([lat, lng], 15);

        // Update form fields
        document.getElementById('machineLat').value = lat.toFixed(7);
        document.getElementById('machineLng').value = lng.toFixed(7);
    },

    /**
     * Search location using Nominatim
     */
    async searchLocation() {
        const query = document.getElementById('addMachineLocationSearch')?.value;
        if (!query) return;

        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`);
            const results = await response.json();

            if (results.length > 0) {
                const { lat, lon, display_name } = results[0];
                this.setMarker(parseFloat(lat), parseFloat(lon));
                document.getElementById('machineAddress').value = display_name;
            }
        } catch (error) {
            console.error('Search error:', error);
        }
    },

    /**
     * Reverse geocode lat/lng to address
     */
    async reverseGeocode(lat, lng) {
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
            const result = await response.json();
            if (result.display_name) {
                document.getElementById('machineAddress').value = result.display_name;
            }
        } catch (error) {
            console.error('Reverse geocode error:', error);
        }
    },

    /**
     * Copy API Key to clipboard
     */
    async copyApiKey() {
        // Fallback to hidden input if lastCredentials was reset
        const apiKey = this.lastCredentials?.api_key || document.getElementById('successApiKey')?.value;
        if (!apiKey) {
            machineManagement.showError('API Key tidak tersedia');
            return;
        }

        try {
            await navigator.clipboard.writeText(apiKey);
            machineManagement.showSuccess('API Key copied to clipboard!');
        } catch (error) {
            console.error('Copy failed:', error);
        }
    },

    /**
     * Copy Serial Number to clipboard
     */
    async copySerial() {
        // Fallback to input value if lastCredentials was reset
        const serial = this.lastCredentials?.serial_number || document.getElementById('successSerialNumber')?.value;
        if (!serial) {
            machineManagement.showError('Serial Number tidak tersedia');
            return;
        }

        try {
            await navigator.clipboard.writeText(serial);
            machineManagement.showSuccess('Serial Number copied to clipboard!');
        } catch (error) {
            console.error('Copy failed:', error);
        }
    },

    /**
     * Toggle API Key visibility
     */
    toggleApiKey() {
        this.apiKeyVisible = !this.apiKeyVisible;
        const display = document.getElementById('successApiKeyDisplay');
        const btn = document.getElementById('btn-toggle-machine-apikey');
        // Fallback to hidden input if lastCredentials was reset
        const apiKey = this.lastCredentials?.api_key || document.getElementById('successApiKey')?.value;

        if (this.apiKeyVisible) {
            display.textContent = apiKey || '(API Key tidak tersedia)';
            btn.innerHTML = '<i class="ti tabler-eye-off"></i> Hide';
        } else {
            display.textContent = '••••••••••••••••••••••••••••••••';
            btn.innerHTML = '<i class="ti tabler-eye"></i> Show';
        }
    },

    /**
     * Download credentials as JSON
     */
    downloadCredentials() {
        // Fallback to DOM elements if lastCredentials was reset
        const serial = this.lastCredentials?.serial_number || document.getElementById('successSerialNumber')?.value;
        const apiKey = this.lastCredentials?.api_key || document.getElementById('successApiKey')?.value;

        if (!serial || !apiKey) {
            machineManagement.showError('Credentials tidak tersedia');
            return;
        }

        const data = {
            serial_number: serial,
            api_key: apiKey,
            generated_at: new Date().toISOString()
        };

        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `rvm-credentials-${serial}.json`;
        a.click();
        URL.revokeObjectURL(url);

        machineManagement.showSuccess('Credentials downloaded!');
    },

    /**
     * Reset wizard state
     */
    reset() {
        this.currentStep = 1;
        // Do NOT clear credentials here - they are needed for success modal
        // Credentials will be naturally overwritten on next machine creation
        if (this.marker) {
            this.map.removeLayer(this.marker);
            this.marker = null;
        }
        this.goToStep(1);
    }
};

// Manual Command Handlers (Manual Update / Restart)
document.addEventListener('click', async (e) => {
    const btnUpdate = e.target.closest('.btn-manual-update');
    const btnRestart = e.target.closest('.btn-restart-edge');
    
    if (!btnUpdate && !btnRestart) return;
    
    const id = btnUpdate ? btnUpdate.dataset.id : btnRestart.dataset.id;
    const action = btnUpdate ? 'GIT_PULL' : 'RESTART';
    const label = btnUpdate ? 'Git Pull' : 'Restart';

    try {
        const response = await apiHelper.post(`/api/v1/edge/devices/${id}/command`, { action });
        
        if (!response || !response.ok) {
            const errorData = response ? await response.json() : { message: 'Network error' };
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        if (result.status === 'success') {
            machineManagement.showSuccess(`Perintah ${label} berhasil dikirim ke antrean! Perangkat akan mengeksekusi pada heartbeat berikutnya.`);
        } else {
            machineManagement.showError(`Gagal mengirim perintah: ${result.message}`);
        }
    } catch (error) {
        machineManagement.showError(`Error: ${error.message}`);
    }
});

// Reset wizard when modal is hidden
document.addEventListener('hidden.bs.modal', (e) => {
    if (e.target?.id === 'addMachineModal') {
        machineWizard.reset();
    }
    // Clear credentials when SUCCESS modal is closed
    if (e.target?.id === 'machineSuccessModal') {
        machineWizard.lastCredentials = null;
        machineWizard.apiKeyVisible = false;
    }

    // Force cleanup any lingering modal backdrops
    // This fixes the overlay bug where backdrop stays after modal close
    setTimeout(() => {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        const openModals = document.querySelectorAll('.modal.show');
        if (openModals.length === 0 && backdrops.length > 0) {
            backdrops.forEach(b => b.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }
    }, 100);
});
