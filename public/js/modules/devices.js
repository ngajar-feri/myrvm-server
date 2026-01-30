/**
 * Edge Devices Management Module
 * Handles device registration, monitoring, telemetry, real-time data
 */

class DeviceManagement {
    constructor() {
        this.devices = [];
        this.telemetryInterval = null;
        this.map = null;
        this.marker = null;
        this.lastRegisteredDevice = null;
        this.currentStep = 1;
        this.totalSteps = 3;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadDevices();
        this.startAutoRefresh();

        // Initialize map AND load RVM machines when modal is shown (not before)
        const registerModal = document.getElementById('registerDeviceModal');
        if (registerModal) {
            registerModal.addEventListener('shown.bs.modal', () => {
                this.loadRvmMachines();
                // Only init map on step 3 to save resources
            });

            // Reset wizard on modal close
            registerModal.addEventListener('hidden.bs.modal', () => {
                this.resetWizard();
            });
        }

        // Wizard navigation buttons
        const nextBtn = document.getElementById('wizard-next');
        const backBtn = document.getElementById('wizard-back');

        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.nextStep());
        }
        if (backBtn) {
            backBtn.addEventListener('click', () => this.prevStep());
        }

        // Threshold slider value update
        const thresholdSlider = document.getElementById('threshold-slider');
        const thresholdValue = document.getElementById('threshold-value');
        if (thresholdSlider && thresholdValue) {
            thresholdSlider.addEventListener('input', (e) => {
                const value = parseInt(e.target.value);
                thresholdValue.textContent = value + '%';

                // Update badge color based on value
                thresholdValue.className = 'badge';
                if (value <= 40) {
                    thresholdValue.classList.add('bg-success');
                } else if (value <= 70) {
                    thresholdValue.classList.add('bg-warning');
                } else {
                    thresholdValue.classList.add('bg-danger');
                }
            });
        }

        // Initialize tooltips
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(el => new bootstrap.Tooltip(el));
    }

    // Wizard Step Navigation
    nextStep() {
        if (!this.validateCurrentStep()) return;

        if (this.currentStep < this.totalSteps) {
            this.currentStep++;
            this.goToStep(this.currentStep);
        } else {
            // Final step - submit form
            this.submitRegistration();
        }
    }

    prevStep() {
        if (this.currentStep > 1) {
            this.currentStep--;
            this.goToStep(this.currentStep);
        }
    }

    goToStep(step) {
        // Update step dots
        document.querySelectorAll('.step-dot').forEach((dot, index) => {
            dot.classList.remove('active', 'completed');
            if (index + 1 < step) dot.classList.add('completed');
            if (index + 1 === step) dot.classList.add('active');
        });

        // Update step content
        document.querySelectorAll('.step-content').forEach((content, index) => {
            content.classList.remove('active');
            if (index + 1 === step) content.classList.add('active');
        });

        // Update buttons
        const nextBtn = document.getElementById('wizard-next');
        const backBtn = document.getElementById('wizard-back');

        if (backBtn) {
            backBtn.style.display = step > 1 ? 'block' : 'none';
        }

        if (nextBtn) {
            if (step === this.totalSteps) {
                nextBtn.innerHTML = '<i class="ti tabler-key me-1"></i>Register';
            } else {
                nextBtn.innerHTML = 'Next<i class="ti tabler-arrow-right ms-1"></i>';
            }
        }

        // Initialize map only when reaching step 3
        if (step === 3 && !this.map) {
            setTimeout(() => this.initializeMap(), 100);
        }
    }

    validateCurrentStep() {
        if (this.currentStep === 1) {
            const rvmId = document.getElementById('rvm-machine-id')?.value;
            if (!rvmId) {
                window.showToast?.('Error', 'Please select an RVM Machine', 'error');
                return false;
            }
        }
        return true;
    }

    resetWizard() {
        this.currentStep = 1;
        this.goToStep(1);

        // Clear form
        const form = document.getElementById('register-device-form');
        if (form) form.reset();

        // Clear RVM selection
        const rvmSearch = document.getElementById('rvm-machine-search');
        const rvmId = document.getElementById('rvm-machine-id');
        const locationDisplay = document.getElementById('location-name-display');

        if (rvmSearch) rvmSearch.value = '';
        if (rvmId) rvmId.value = '';
        if (locationDisplay) locationDisplay.value = '';
    }

    async submitRegistration() {
        const form = document.getElementById('register-device-form');
        if (!form) return;

        // Create a fake event to reuse registerDevice
        const fakeEvent = { preventDefault: () => { } };
        await this.registerDevice(fakeEvent);
    }


    setupEventListeners() {
        const form = document.getElementById('register-device-form');
        if (form) {
            form.addEventListener('submit', (e) => this.registerDevice(e));
        }

        const statusFilter = document.getElementById('device-status-filter');
        if (statusFilter) {
            statusFilter.addEventListener('change', () => this.loadDevices());
        }

        // Location search
        const locationSearchBtn = document.getElementById('location-search-btn');
        const locationSearchInput = document.getElementById('location-search-input');
        if (locationSearchBtn) {
            locationSearchBtn.addEventListener('click', () => this.searchLocation());
        }
        if (locationSearchInput) {
            locationSearchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.searchLocation();
                }
            });
        }

        // RVM Machine search
        const rvmSearchInput = document.getElementById('rvm-machine-search');
        if (rvmSearchInput) {
            rvmSearchInput.addEventListener('input', (e) => this.searchRvmMachines(e.target.value));
            rvmSearchInput.addEventListener('focus', () => this.showRvmResults());
            document.addEventListener('click', (e) => {
                if (!e.target.closest('#rvm-machine-search') && !e.target.closest('#rvm-search-results')) {
                    this.hideRvmResults();
                }
            });
        }
    }

    async searchLocation() {
        const input = document.getElementById('location-search-input');
        const query = input?.value.trim();
        if (!query) {
            window.showToast?.('Error', 'Masukkan nama lokasi untuk dicari', 'error');
            return;
        }

        const btn = document.getElementById('location-search-btn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        btn.disabled = true;

        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&countrycodes=id`,
                { headers: { 'Accept-Language': 'id' } }
            );
            const results = await response.json();

            if (results.length > 0) {
                const { lat, lon, display_name } = results[0];
                const latlng = { lat: parseFloat(lat), lng: parseFloat(lon) };

                // Center map and place marker
                this.map.setView(latlng, 16);
                this.placeMarker(latlng);

                // Update address field
                document.getElementById('device-address').value = display_name;
                window.showToast?.('Found', `Lokasi ditemukan: ${display_name.substring(0, 50)}...`, 'success');
            } else {
                window.showToast?.('Not Found', 'Lokasi tidak ditemukan. Coba kata kunci lain.', 'warning');
            }
        } catch (error) {
            console.error('Location search error:', error);
            window.showToast?.('Error', 'Gagal mencari lokasi', 'error');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    initializeMap() {
        if (this.map) {
            // Already initialized, just refresh
            this.map.invalidateSize();
            return;
        }

        const mapContainer = document.getElementById('device-map');
        if (!mapContainer) {
            console.warn('Map container not found');
            return;
        }

        try {
            // Default to Jakarta, Indonesia
            const defaultLat = -6.2088;
            const defaultLng = 106.8456;

            this.map = L.map('device-map').setView([defaultLat, defaultLng], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(this.map);

            // Click to place marker
            this.map.on('click', (e) => this.placeMarker(e.latlng));

            // Multiple invalidateSize calls to handle modal animation
            setTimeout(() => this.map.invalidateSize(), 100);
            setTimeout(() => this.map.invalidateSize(), 300);
            setTimeout(() => this.map.invalidateSize(), 500);

            console.log('Map initialized successfully');
        } catch (error) {
            console.error('Failed to initialize map:', error);
        }
    }

    placeMarker(latlng) {
        const { lat, lng } = latlng;

        // Update or create marker
        if (this.marker) {
            this.marker.setLatLng(latlng);
        } else {
            this.marker = L.marker(latlng, { draggable: true }).addTo(this.map);
            this.marker.on('dragend', (e) => {
                const pos = e.target.getLatLng();
                this.updateCoordinates(pos.lat, pos.lng);
            });
        }

        this.updateCoordinates(lat, lng);
    }

    updateCoordinates(lat, lng) {
        document.getElementById('device-latitude').value = lat.toFixed(8);
        document.getElementById('device-longitude').value = lng.toFixed(8);

        // Reverse geocoding using Nominatim (OSM)
        this.reverseGeocode(lat, lng);
    }

    async reverseGeocode(lat, lng) {
        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,
                { headers: { 'Accept-Language': 'id' } }
            );
            const data = await response.json();
            if (data.display_name) {
                document.getElementById('device-address').value = data.display_name;
            }
        } catch (error) {
            console.warn('Reverse geocoding failed:', error);
        }
    }

    async loadDevices() {
        const statusFilter = document.getElementById('device-status-filter')?.value || '';
        const grid = document.getElementById('devices-grid');

        try {
            const params = statusFilter ? `?status=${statusFilter}` : '';
            const response = await fetch(`/api/v1/edge/devices${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin' // Include session cookies
            });

            if (!response.ok) throw new Error('Failed to load devices');

            const result = await response.json();
            this.devices = result.data || [];
            this.renderDevices();
            this.updateStats(result.stats);
        } catch (error) {
            console.error('Error loading devices:', error);
            grid.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger">
                        <i class="ti tabler-alert-circle me-2"></i>
                        Failed to load devices: ${error.message}
                    </div>
                </div>
            `;
        }
    }

    async loadRvmMachines() {
        // Load all RVM machines with edge device relationship
        try {
            const response = await fetch('/api/v1/rvm-machines?with_edge_device=1', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) return;

            const result = await response.json();
            this.rvmMachines = Array.isArray(result) ? result : (result.data || []);
            console.log(`Loaded ${this.rvmMachines.length} RVM machines`);
        } catch (error) {
            console.error('Failed to load RVM machines:', error);
            this.rvmMachines = [];
        }
    }

    searchRvmMachines(query) {
        const resultsContainer = document.getElementById('rvm-search-results');
        if (!resultsContainer) return;

        if (!this.rvmMachines || this.rvmMachines.length === 0) {
            this.loadRvmMachines().then(() => this.searchRvmMachines(query));
            return;
        }

        const filtered = this.rvmMachines.filter(m => {
            const searchStr = `${m.serial_number || ''} ${m.location_name || ''} ${m.location || ''}`.toLowerCase();
            return searchStr.includes(query.toLowerCase());
        });

        if (filtered.length === 0) {
            resultsContainer.innerHTML = '<div class="dropdown-item text-muted">No machines found</div>';
        } else {
            resultsContainer.innerHTML = filtered.map(machine => {
                const isInstalled = machine.edge_device && machine.edge_device.id;
                const edgeCode = isInstalled ? machine.edge_device.device_id || `ID:${machine.edge_device.id}` : null;
                const displayText = `${machine.serial_number || 'Unknown'} - ${machine.location_name || machine.location || 'N/A'}`;
                const locationName = machine.location_name || machine.location || '';

                return `
                    <div class="dropdown-item ${isInstalled ? 'disabled text-muted' : 'rvm-available'}" 
                        data-id="${machine.id}" 
                        data-installed="${isInstalled ? '1' : '0'}"
                        data-text="${this.escapeHtml(displayText)}"
                        data-location-name="${this.escapeHtml(locationName)}"
                        style="${isInstalled ? 'cursor: not-allowed; background-color: #f8f9fa; opacity: 0.7;' : 'cursor: pointer;'}">
                        <div class="d-flex align-items-center">
                            ${isInstalled
                        ? `<span class="badge bg-secondary text-white me-2" style="font-size: 0.7rem;"><i class="ti tabler-lock-filled me-1"></i>Installed ${this.escapeHtml(edgeCode)}</span>`
                        : '<span class="badge bg-success text-white me-2" style="font-size: 0.75rem; padding: 0.35em 0.65em;"><i class="ti tabler-circle-check-filled me-1"></i>Available</span>'}
                            <span class="${isInstalled ? 'text-muted' : 'fw-medium'}">${this.escapeHtml(displayText)}</span>
                        </div>
                    </div>
                `;
            }).join('');
        }

        this.showRvmResults();

        // Add click handlers for available machines
        resultsContainer.querySelectorAll('.dropdown-item:not(.disabled)').forEach(item => {
            item.addEventListener('click', () => {
                const id = item.dataset.id;
                const text = item.dataset.text;
                const locationName = item.dataset.locationName; // Get location_name from data attribute

                document.getElementById('rvm-machine-search').value = text;
                document.getElementById('rvm-machine-id').value = id;

                // Auto-fill Location Name from selected RVM Machine
                const locationField = document.getElementById('location-name-display');
                if (locationField) {
                    locationField.value = locationName || '';
                }

                // Auto-fill Search Location field and trigger map search
                const searchInput = document.getElementById('location-search-input');
                if (searchInput && locationName) {
                    searchInput.value = locationName;
                    // Trigger location search automatically (like clicking Search button)
                    setTimeout(() => {
                        this.searchLocation();
                    }, 100); // Small delay to ensure UI updates first
                }

                this.hideRvmResults();
            });
        });
    }

    showRvmResults() {
        const resultsContainer = document.getElementById('rvm-search-results');
        if (resultsContainer) {
            resultsContainer.style.display = 'block';
        }
    }

    hideRvmResults() {
        const resultsContainer = document.getElementById('rvm-search-results');
        if (resultsContainer) {
            resultsContainer.style.display = 'none';
        }
    }

    renderDevices() {
        const grid = document.getElementById('devices-grid');
        if (!grid) return;

        if (this.devices.length === 0) {
            grid.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        <i class="ti tabler-info-circle me-2"></i>
                        No Edge devices registered yet. Click "Register Device" to add one.
                    </div>
                </div>
            `;
            return;
        }

        grid.innerHTML = this.devices.map(device => this.renderDeviceCard(device)).join('');
    }

    renderDeviceCard(device) {
        const statusColors = {
            online: 'success',
            offline: 'danger',
            maintenance: 'warning',
            error: 'danger'
        };
        const statusColor = statusColors[device.status] || 'secondary';
        const healthMetrics = device.health_metrics || {};

        return `
            <div class="col-md-4">
                <div class="card h-100 device-card">
                    <div class="card-header d-flex justify-content-between align-items-center py-2" 
                         onclick="deviceManagement.monitorDevice(${device.id})" style="cursor: pointer;">
                        <span class="badge bg-${statusColor}">${this.escapeHtml(device.status || 'unknown')}</span>
                        <small class="text-muted">${this.getLastSeen(device.updated_at)}</small>
                    </div>
                    <div class="card-body py-3" onclick="deviceManagement.monitorDevice(${device.id})" style="cursor: pointer;">
                        <h6 class="mb-1">${this.escapeHtml(device.location_name || device.device_id || 'Unnamed Device')}</h6>
                        <small class="text-muted d-block mb-2">
                            <i class="ti tabler-cpu me-1"></i>${this.escapeHtml(device.controller_type || device.type || 'N/A')}
                        </small>
                        <div class="d-flex justify-content-between small">
                            <span><i class="ti tabler-network me-1"></i>${this.escapeHtml(device.tailscale_ip || device.ip_address_local || 'N/A')}</span>
                        </div>
                        ${healthMetrics.cpu_usage !== undefined ? `
                        <div class="mt-2">
                            <div class="d-flex justify-content-between small text-muted">
                                <span>CPU: ${healthMetrics.cpu_usage}%</span>
                                <span>Temp: ${healthMetrics.temperature || 'N/A'}°C</span>
                            </div>
                            <div class="progress mt-1" style="height: 4px;">
                                <div class="progress-bar bg-${healthMetrics.cpu_usage > 80 ? 'danger' : 'success'}" 
                                    style="width: ${healthMetrics.cpu_usage}%"></div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                    <div class="card-footer py-2 d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="ti tabler-building me-1"></i>
                            ${device.rvm_machine ? this.escapeHtml(device.rvm_machine.serial_number || device.rvm_machine.location_name) : 'Unassigned'}
                        </small>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" 
                                    onclick="event.stopPropagation()">
                                <i class="ti tabler-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); deviceManagement.editDevice(${device.id})">
                                    <i class="ti tabler-edit me-2"></i>Edit
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); deviceManagement.showRegenerateKeyModal(${device.id}, '${this.escapeHtml(device.device_id)}')">
                                    <i class="ti tabler-key me-2"></i>Regenerate API Key
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="event.preventDefault(); deviceManagement.confirmDeleteDevice(${device.id}, '${this.escapeHtml(device.device_id)}', '${this.escapeHtml(device.location_name || '')}')">
                                    <i class="ti tabler-trash me-2"></i>Delete
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    updateStats(stats) {
        if (!stats) return;
        document.getElementById('devices-online').textContent = stats.online || 0;
        document.getElementById('devices-offline').textContent = stats.offline || 0;
        document.getElementById('avg-cpu').textContent = `${stats.avg_cpu || 0}%`;
        document.getElementById('avg-gpu').textContent = `${stats.avg_gpu || 0}%`;
        document.getElementById('avg-temp').textContent = `${stats.avg_temp || 0}°C`;
        document.getElementById('total-devices').textContent = stats.total || 0;
    }

    async registerDevice(e) {
        if (e && e.preventDefault) e.preventDefault();
        if (e && e.stopPropagation) e.stopPropagation();

        // Get form directly by ID (not from event.target which may be undefined when called from wizard)
        const form = document.getElementById('register-device-form');
        if (!form) {
            console.error('[registerDevice] Form not found');
            window.showToast?.('Error', 'Form not found', 'error');
            return false;
        }

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Validate required fields
        if (!data.location_name) {
            window.showToast('Error', 'Location name is required', 'error');
            return false;
        }

        // Get button for loading state (wizard uses #wizard-next, not button[type="submit"])
        const submitBtn = document.getElementById('wizard-next') || form.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.innerHTML : '';

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Registering...';
        }

        try {
            // Get CSRF token from meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            const response = await fetch('/api/v1/edge/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    device_serial: `EDGE-${Date.now()}`, // Auto-generate serial
                    rvm_id: data.rvm_machine_id || null,
                    tailscale_ip: null,
                    hardware_info: {
                        controller_type: data.controller_type,
                        camera_id: data.camera_id,
                        threshold_full: parseInt(data.threshold_full) || 90
                    },
                    location_name: data.location_name,
                    inventory_code: data.inventory_code,
                    description: data.description,
                    latitude: data.latitude ? parseFloat(data.latitude) : null,
                    longitude: data.longitude ? parseFloat(data.longitude) : null,
                    address: data.address,
                    status: data.status,
                    ai_model_version: data.ai_model_version
                })
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Registration failed');
            }

            // Store for download config
            this.lastRegisteredDevice = {
                device_id: result.data.device_serial || result.data.edge_device_id,
                api_key: result.data.api_key,
                location_name: data.location_name,
                config: result.data.config
            };

            // Show success modal with API key
            document.getElementById('success-device-id').value = result.data.device_serial || result.data.edge_device_id;
            document.getElementById('success-api-key').value = result.data.api_key;

            console.log('Registration successful:', result.data);

            // Close register modal and show success modal
            bootstrap.Modal.getInstance(document.getElementById('registerDeviceModal')).hide();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('successModal')).show();

            form.reset();
            if (this.marker) {
                this.map.removeLayer(this.marker);
                this.marker = null;
            }

            window.showToast('Success', 'Device registered successfully!', 'success');
        } catch (error) {
            console.error('Registration error:', error);
            window.showToast('Error', error.message || 'Failed to register device', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
        return false;
    }

    monitorDevice(deviceId) {
        const device = this.devices.find(d => d.id === deviceId);
        if (!device) return;

        document.getElementById('device-serial').textContent = device.location_name || device.device_id || 'Device Monitor';

        const content = document.getElementById('device-monitor-content');
        const healthMetrics = device.health_metrics || {};

        content.innerHTML = `
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="ti tabler-id me-2"></i>Device Info</h6>
                            <dl class="row mb-0 small">
                                <dt class="col-5">Device ID:</dt>
                                <dd class="col-7"><code>${this.escapeHtml(device.device_id || 'N/A')}</code></dd>
                                <dt class="col-5">Controller:</dt>
                                <dd class="col-7">${this.escapeHtml(device.controller_type || device.type || 'N/A')}</dd>
                                <dt class="col-5">Status:</dt>
                                <dd class="col-7"><span class="badge bg-${device.status === 'online' ? 'success' : 'danger'}">${this.escapeHtml(device.status || 'unknown')}</span></dd>
                                <dt class="col-5">Tailscale IP:</dt>
                                <dd class="col-7"><code>${this.escapeHtml(device.tailscale_ip || 'N/A')}</code></dd>
                                <dt class="col-5">Local IP:</dt>
                                <dd class="col-7"><code>${this.escapeHtml(device.ip_address_local || 'N/A')}</code></dd>
                                <dt class="col-5">Camera:</dt>
                                <dd class="col-7">${this.escapeHtml(device.camera_id || 'N/A')}</dd>
                                <dt class="col-5">AI Model:</dt>
                                <dd class="col-7">${this.escapeHtml(device.ai_model_version || 'default')}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="ti tabler-activity me-2"></i>Health Metrics</h6>
                            ${Object.keys(healthMetrics).length > 0 ? `
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small>CPU Usage</small>
                                        <small>${healthMetrics.cpu_usage || 0}%</small>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-${(healthMetrics.cpu_usage || 0) > 80 ? 'danger' : 'success'}" 
                                            style="width: ${healthMetrics.cpu_usage || 0}%"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small>GPU Usage</small>
                                        <small>${healthMetrics.gpu_usage || 0}%</small>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-info" style="width: ${healthMetrics.gpu_usage || 0}%"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small>Temperature</small>
                                        <small>${healthMetrics.temperature || 0}°C</small>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-${(healthMetrics.temperature || 0) > 70 ? 'danger' : 'warning'}" 
                                            style="width: ${Math.min((healthMetrics.temperature || 0), 100)}%"></div>
                                    </div>
                                </div>
                            ` : '<p class="text-muted mb-0">No health metrics available</p>'}
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="ti tabler-map-pin me-2"></i>Location</h6>
                            <p class="mb-1"><strong>${this.escapeHtml(device.location_name || 'N/A')}</strong></p>
                            <p class="text-muted small mb-0">${this.escapeHtml(device.address || 'No address')}</p>
                            ${device.latitude && device.longitude ? `
                                <small class="text-muted">Coordinates: ${device.latitude}, ${device.longitude}</small>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;

        bootstrap.Modal.getOrCreateInstance(document.getElementById('deviceMonitorModal')).show();
    }

    startAutoRefresh() {
        // Refresh every 60 seconds
        setInterval(() => this.loadDevices(), 60000);
    }

    getLastSeen(lastSeen) {
        if (!lastSeen) return 'Never';
        const diff = Date.now() - new Date(lastSeen).getTime();
        const minutes = Math.floor(diff / 60000);
        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours}h ago`;
        return `${Math.floor(hours / 24)}d ago`;
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ========================
    // CRUD Methods
    // ========================

    async editDevice(id) {
        try {
            const response = await fetch(`/api/v1/edge/devices/${id}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error('Failed to fetch device');

            const result = await response.json();
            const device = result.data;

            // Populate form
            document.getElementById('edit-device-id').value = device.id;
            document.getElementById('edit-device-serial').value = device.device_id;
            document.getElementById('edit-location-name').value = device.location_name || '';
            document.getElementById('edit-status').value = device.status || 'offline';
            document.getElementById('edit-controller-type').value = device.controller_type || 'NVIDIA Jetson';
            document.getElementById('edit-threshold').value = device.threshold_full || 90;
            document.getElementById('edit-threshold-value').textContent = (device.threshold_full || 90) + '%';
            document.getElementById('edit-description').value = device.description || '';

            // Setup threshold slider
            const slider = document.getElementById('edit-threshold');
            slider.oninput = () => {
                document.getElementById('edit-threshold-value').textContent = slider.value + '%';
            };

            // Setup form submit
            const form = document.getElementById('edit-device-form');
            form.onsubmit = (e) => {
                e.preventDefault();
                this.updateDevice(device.id);
            };

            bootstrap.Modal.getOrCreateInstance(document.getElementById('editDeviceModal')).show();
        } catch (error) {
            console.error('Edit device error:', error);
            window.showToast?.('Error', error.message, 'error');
        }
    }

    async updateDevice(id) {
        const form = document.getElementById('edit-device-form');
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
        submitBtn.disabled = true;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const response = await fetch(`/api/v1/edge/devices/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    location_name: document.getElementById('edit-location-name').value,
                    status: document.getElementById('edit-status').value,
                    controller_type: document.getElementById('edit-controller-type').value,
                    threshold_full: parseInt(document.getElementById('edit-threshold').value),
                    description: document.getElementById('edit-description').value
                })
            });

            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Update failed');

            bootstrap.Modal.getInstance(document.getElementById('editDeviceModal')).hide();
            window.showToast?.('Success', 'Device updated successfully', 'success');
            this.loadDevices();
        } catch (error) {
            console.error('Update error:', error);
            window.showToast?.('Error', error.message, 'error');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    confirmDeleteDevice(id, deviceId, locationName) {
        document.getElementById('delete-device-id').value = id;
        document.getElementById('delete-device-info').innerHTML = `
            <strong>Device:</strong> ${this.escapeHtml(deviceId)}<br>
            <strong>Location:</strong> ${this.escapeHtml(locationName || 'N/A')}
        `;
        // Use getOrCreateInstance to prevent multiple backdrop accumulation
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteDeviceModal'));
        modal.show();
    }

    async deleteDevice() {
        const id = document.getElementById('delete-device-id').value;
        const modalEl = document.getElementById('deleteDeviceModal');
        const deleteBtn = modalEl.querySelector('.btn-danger');
        const originalText = deleteBtn.innerHTML;
        deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting...';
        deleteBtn.disabled = true;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const response = await fetch(`/api/v1/edge/devices/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });

            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Delete failed');

            // Hide modal properly
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) {
                modalInstance.hide();
            }

            // Clean up any stale backdrops (fix accumulation bug)
            this.cleanupModalBackdrops();

            window.showToast?.('Success', 'Device dipindahkan ke Kotak Sampah', 'success');
            this.loadDevices();
        } catch (error) {
            console.error('Delete error:', error);
            window.showToast?.('Error', error.message, 'error');
        } finally {
            // Reset button state
            deleteBtn.innerHTML = originalText;
            deleteBtn.disabled = false;
        }
    }

    /**
     * Clean up stale modal backdrops that may have accumulated
     */
    cleanupModalBackdrops() {
        setTimeout(() => {
            // Remove all stale modal backdrops
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                backdrop.remove();
            });
            // Remove modal-open class from body if no modals are shown
            if (!document.querySelector('.modal.show')) {
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            }
        }, 300); // Wait for modal hide animation
    }

    // ========================
    // Trash/Restore Methods
    // ========================

    /**
     * Refresh the current view (either active devices or trashed devices)
     * based on the current showingTrashed state
     */
    refreshCurrentView() {
        if (this.showingTrashed) {
            this.loadTrashedDevices();
        } else {
            this.loadDevices();
        }
    }

    toggleTrashedView() {
        this.showingTrashed = !this.showingTrashed;
        const btn = document.getElementById('toggle-trash-btn');

        if (this.showingTrashed) {
            btn.classList.remove('btn-label-warning');
            btn.classList.add('btn-warning');
            btn.innerHTML = '<i class="ti tabler-arrow-back me-1"></i>Back to Active';
            this.loadTrashedDevices();
        } else {
            btn.classList.remove('btn-warning');
            btn.classList.add('btn-label-warning');
            btn.innerHTML = '<i class="ti tabler-trash me-1"></i>Kotak Sampah';
            this.loadDevices();
        }
    }

    async loadTrashedDevices() {
        const grid = document.getElementById('devices-grid');
        grid.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div></div>';

        try {
            const response = await fetch('/api/v1/edge/devices/trashed', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });

            if (!response.ok) throw new Error('Failed to load trashed devices');

            const result = await response.json();
            const trashedDevices = result.data || [];

            if (trashedDevices.length === 0) {
                grid.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-info mb-0">
                            <i class="ti tabler-info-circle me-2"></i>
                            Kotak Sampah kosong. Tidak ada device yang dihapus.
                        </div>
                    </div>
                `;
                return;
            }

            grid.innerHTML = trashedDevices.map(device => this.renderTrashedCard(device)).join('');
        } catch (error) {
            console.error('Load trashed error:', error);
            grid.innerHTML = `<div class="col-12"><div class="alert alert-danger">${error.message}</div></div>`;
        }
    }

    renderTrashedCard(device) {
        const deletedAt = device.deleted_at ? new Date(device.deleted_at).toLocaleDateString('id-ID') : 'Unknown';
        return `
            <div class="col-md-4">
                <div class="card h-100 border-warning">
                    <div class="card-header bg-warning bg-opacity-10 d-flex justify-content-between py-2">
                        <span class="badge bg-warning text-dark">Deleted</span>
                        <small class="text-muted">${deletedAt}</small>
                    </div>
                    <div class="card-body py-3">
                        <h6 class="mb-1">${this.escapeHtml(device.location_name || device.device_id || 'Unnamed')}</h6>
                        <small class="text-muted d-block">
                            <i class="ti tabler-cpu me-1"></i>${this.escapeHtml(device.controller_type || 'N/A')}
                        </small>
                    </div>
                    <div class="card-footer py-2">
                        <button class="btn btn-sm btn-success w-100" onclick="deviceManagement.restoreDevice(${device.id})">
                            <i class="ti tabler-refresh me-1"></i>Restore
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    async restoreDevice(id) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const response = await fetch(`/api/v1/edge/devices/${id}/restore`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify({}) // No rvm_machine_id means restore without linking
            });

            const result = await response.json();
            if (!response.ok) {
                // RVM conflict - show specific error
                window.showToast?.('Restore Gagal', result.message, 'error');
                return;
            }

            window.showToast?.('Success', 'Device berhasil di-restore!', 'success');
            this.loadTrashedDevices(); // Refresh trash list
        } catch (error) {
            console.error('Restore error:', error);
            window.showToast?.('Error', error.message, 'error');
        }
    }

    // ========================
    // Regenerate API Key
    // ========================

    showRegenerateKeyModal(id, deviceId) {
        this.regenerateDeviceId = id;
        document.getElementById('regen-device-id').value = deviceId;
        document.getElementById('new-key-container').style.display = 'none';
        document.getElementById('regen-actions').style.display = 'grid';
        document.getElementById('download-actions').style.display = 'none';
        document.getElementById('btn-confirm-regen').disabled = false;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('regenerateKeyModal')).show();
    }

    async confirmRegenerateKey() {
        const btn = document.getElementById('btn-confirm-regen');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generating...';
        btn.disabled = true;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const response = await fetch(`/api/v1/edge/devices/${this.regenerateDeviceId}/regenerate-key`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });

            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Failed to regenerate key');

            // Show new key
            document.getElementById('regen-api-key').value = result.data.api_key;
            this.newApiKey = result.data.api_key;
            this.regenerateDeviceSerial = result.data.device_id;

            document.getElementById('new-key-container').style.display = 'block';
            document.getElementById('regen-actions').style.display = 'none';
            document.getElementById('download-actions').style.display = 'grid';

            window.showToast?.('Success', 'API Key baru berhasil di-generate!', 'success');
        } catch (error) {
            console.error('Regenerate key error:', error);
            window.showToast?.('Error', error.message, 'error');
            btn.innerHTML = '<i class="ti tabler-refresh me-1"></i>Generate New API Key';
            btn.disabled = false;
        }
    }

    downloadNewConfig() {
        if (!this.regenerateDeviceSerial) return;
        const downloadUrl = `/api/v1/edge/download-config/${encodeURIComponent(this.regenerateDeviceSerial)}`;
        window.open(downloadUrl, '_blank');
        window.showToast?.('Download', 'File konfigurasi sedang diunduh. Tambahkan API Key baru secara manual.', 'info');
    }
}


// Global functions
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;

    navigator.clipboard.writeText(element.value).then(() => {
        window.showToast('Copied!', 'Value copied to clipboard', 'success');
    }).catch(err => {
        console.error('Copy failed:', err);
        element.select();
        document.execCommand('copy');
        window.showToast('Copied!', 'Value copied to clipboard', 'success');
    });
}

/**
 * Download Config (Server-Side Method)
 * Uses server endpoint with Content-Disposition header for reliable download.
 * API Key must be copied manually for security (not included in downloaded file).
 */
window.downloadConfig = function () {
    console.log('[downloadConfig] Starting server-side download...');

    // 1. Validate device data
    if (!window.deviceManagement || !window.deviceManagement.lastRegisteredDevice) {
        const msg = 'Data perangkat hilang. Silakan refresh halaman dan coba lagi.';
        console.error('[downloadConfig] No device data found');
        if (window.showToast) window.showToast('Error', msg, 'error');
        else alert(msg);
        return;
    }

    const device = window.deviceManagement.lastRegisteredDevice;
    const deviceId = device.device_id || 'unknown-device';
    console.log('[downloadConfig] Downloading config for device:', deviceId);

    // 2. Build server-side download URL
    const downloadUrl = `/api/v1/edge/download-config/${encodeURIComponent(deviceId)}`;

    // 3. Open in new window/tab - browser will handle Content-Disposition header
    try {
        window.open(downloadUrl, '_blank');
        console.log('[downloadConfig] Download initiated via:', downloadUrl);

        if (window.showToast) {
            window.showToast('Download', 'File konfigurasi sedang diunduh. Tambahkan API Key secara manual.', 'success');
        }
    } catch (err) {
        console.error('[downloadConfig] Download error:', err);
        // Fallback: try anchor link
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.target = '_blank';
        link.click();
    }
};

// Alias for backward compatibility
window.downloadJson = window.downloadConfig;

// Safe Initialization Function
function initDeviceManagement() {
    if (window.deviceManagement) return; // Prevent double init

    const deviceManagement = new DeviceManagement();

    // Expose to window for HTML onclick handlers
    window.deviceManagement = deviceManagement;
    window.copyToClipboard = copyToClipboard;

    console.log('[DeviceManagement] Initialized safely. DOM State:', document.readyState);
}

// Check DOM State before initializing
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDeviceManagement);
} else {
    initDeviceManagement();
}

// Ensure showToast exists
if (!window.showToast) {
    window.showToast = function (title, message, type = 'info') {
        // Create toast container if needed
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            // Ensure toast appears above navbar (z-index: 1050+) and has margin for navbar height
            container.style.zIndex = '1090';
            container.style.marginTop = '60px'; // Account for fixed navbar height
            document.body.appendChild(container);
        }

        const toastId = 'toast-' + Date.now();
        const icon = type === 'success' ? 'ti-check' : (type === 'error' ? 'ti-alert-circle' : 'ti-info-circle');
        const bgClass = type === 'success' ? 'text-success' : (type === 'error' ? 'text-danger' : 'text-primary');

        const html = `
            <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <i class="ti ${icon} ${bgClass} me-2"></i>
                    <strong class="me-auto">${title}</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);
        const toastEl = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastEl);
        toast.show();

        // Cleanup
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    };
}

// Ensure refreshPage exists
if (!window.refreshPage) {
    window.refreshPage = function () {
        window.location.reload();
    };
}
