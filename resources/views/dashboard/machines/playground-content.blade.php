<div class="playground-container" data-machine-id="{{ $machine->id }}">
    {{-- Header Bar --}}
    <header class="playground-header">
        <div class="header-left">
            <div class="header-icon">
                <i class="bi bi-cpu-fill"></i>
            </div>
            <div class="header-info">
                <h1 class="header-title">MyRVM Playground</h1>
                <span class="header-subtitle">{{ $machine->name }} ({{ $machine->serial_number }})</span>
            </div>
        </div>
        <div class="header-right">
            <span class="status-badge maintenance">
                <i class="bi bi-wrench"></i>
                Maintenance Mode
            </span>
            <button class="btn-exit-maintenance" id="btnExitMaintenance" data-bs-dismiss="modal">
                <i class="bi bi-x-lg"></i>
                Close Playground
            </button>
        </div>
    </header>

    {{-- Main Split Layout --}}
    <div class="playground-main">
        {{-- Left Column: Vision Simulation --}}
        <div class="playground-column left-column">
            {{-- Vision Simulation Panel --}}
            <div class="panel vision-panel">
                <div class="panel-header">
                    <h2 class="panel-title">
                        <i class="bi bi-eye"></i>
                        Vision Simulation
                    </h2>
                    <div class="toggle-switch">
                        <button class="toggle-btn active" data-mode="static">
                            <i class="bi bi-image"></i> Static
                        </button>
                        <button class="toggle-btn" data-mode="live">
                            <i class="bi bi-camera-video"></i> Live
                        </button>
                    </div>
                </div>
                <div class="panel-body">
                    {{-- Viewport Area --}}
                    <div class="viewport-container">
                        <div class="viewport-main" id="viewportMain">
                            <div class="viewport-placeholder">
                                <i class="bi bi-cloud-arrow-up"></i>
                                <p>Drag & drop image or click to upload</p>
                                <input type="file" id="imageUpload" accept="image/*" hidden>
                            </div>
                            <img id="viewportImage" src="" alt="Viewport" style="display: none;">
                            <canvas id="boundingBoxCanvas" style="display: none;"></canvas>
                        </div>
                        <div class="viewport-actions" id="viewportActions" style="display: none;">
                            <button class="btn-viewport" id="btnToggleBbox" title="Toggle Bounding Boxes">
                                <i class="bi bi-bounding-box"></i>
                            </button>
                            <button class="btn-viewport" id="btnSaveWithBbox" title="Save with Boxes">
                                <i class="bi bi-download"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Live Camera Controls (Hidden by default) --}}
                    <div class="live-controls" id="liveControls" style="display: none;">
                        <button class="btn-primary" id="btnActivateCamera">
                            <i class="bi bi-camera-video"></i> Aktifkan Kamera
                        </button>
                        <button class="btn-secondary" id="btnCapture" disabled>
                            <i class="bi bi-camera"></i> Capture
                        </button>
                        <button class="btn-secondary" id="btnRecord" disabled>
                            <i class="bi bi-record-circle"></i> Record
                        </button>
                    </div>

                    {{-- Image Table List --}}
                    <div class="image-table-container">
                        <h4 class="section-subtitle">Uploaded Images</h4>
                        <div class="table-responsive">
                            <table class="table table-sm image-table" id="imageTable">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">Thumbnail</th>
                                        <th>Filename</th>
                                        <th style="width: 80px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="imageTableBody">
                                    <tr class="empty-row">
                                        <td colspan="3" class="text-center text-muted py-3">No images uploaded yet</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Model Control Panel --}}
            <div class="panel model-panel">
                <div class="panel-header">
                    <h2 class="panel-title">
                        <i class="bi bi-box-seam"></i>
                        AI Model Manager
                    </h2>
                </div>
                <div class="panel-body">
                    <div class="model-selector">
                        <div class="model-dropdown-group">
                            <select class="form-select" id="modelSelect">
                                <option value="">Select AI Model...</option>
                            </select>
                            <button class="btn-model-action" id="btnDownloadModel" style="display: none;">
                                <i class="bi bi-cloud-download"></i> Download
                            </button>
                            <button class="btn-model-action downloaded" id="btnDownloaded" style="display: none;" disabled>
                                <i class="bi bi-check-circle-fill"></i> Downloaded
                            </button>
                            <button class="btn-upload-model" id="btnUploadModel" title="Upload Custom Model">
                                <i class="bi bi-upload"></i>
                            </button>
                            <input type="file" id="modelUpload" accept=".pt,.onnx,.engine" hidden>
                        </div>
                        <div class="model-progress" id="modelProgress" style="display: none;">
                            <div class="progress-bar">
                                <div class="progress-fill" id="progressFill"></div>
                            </div>
                            <span class="progress-text" id="progressText">Downloading...</span>
                        </div>
                    </div>
                    <div class="confidence-slider">
                        <label class="slider-label">
                            Confidence Threshold: <span id="confidenceValue">0.50</span>
                        </label>
                        <input type="range" class="form-range" id="confidenceSlider" min="0" max="1" step="0.05" value="0.5">
                    </div>
                    <button class="btn-run-inference" id="btnRunInference" disabled>
                        <i class="bi bi-play-fill"></i> Run Inference
                    </button>
                </div>
            </div>

            {{-- JSON Result Console --}}
            <div class="panel json-panel">
                <div class="panel-header">
                    <h2 class="panel-title">
                        <i class="bi bi-code-slash"></i>
                        Inference Result
                    </h2>
                    <button class="btn-copy" id="btnCopyJson" title="Copy JSON">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <div class="panel-body">
                    <pre class="json-console" id="jsonConsole">{ "message": "Run inference to see results" }</pre>
                </div>
            </div>
        </div>

        {{-- Right Column: Hardware & Shell --}}
        <div class="playground-column right-column">
            {{-- Component Inspector Panel --}}
            <div class="panel component-panel">
                <div class="panel-header">
                    <h2 class="panel-title">
                        <i class="bi bi-motherboard"></i>
                        IoT Component Inspector
                    </h2>
                </div>
                <div class="panel-body">
                    <div class="component-selector">
                        <select class="form-select" id="componentSelect">
                            <option value="">Select Component...</option>
                        </select>
                    </div>
                    <div class="component-actions" id="componentActions" style="display: none;">
                        <div class="sensor-actions" id="sensorActions" style="display: none;">
                            <button class="btn-action read" id="btnReadSensor">
                                <i class="bi bi-eyedropper"></i> Read Value
                            </button>
                        </div>
                        <div class="actuator-actions" id="actuatorActions" style="display: none;">
                            <button class="btn-action trigger" id="btnTriggerOpen">
                                <i class="bi bi-door-open"></i> Trigger / Open
                            </button>
                            <button class="btn-action reset" id="btnTriggerClose">
                                <i class="bi bi-door-closed"></i> Reset / Close
                            </button>
                        </div>
                    </div>
                    <div class="component-value-card" id="componentValueCard" style="display: none;">
                        <div class="value-display">
                            <span class="value-number" id="componentValue">--</span>
                            <span class="value-unit" id="componentUnit"></span>
                        </div>
                        <div class="value-timestamp" id="componentTimestamp">Last read: --</div>
                    </div>
                </div>
            </div>

            {{-- Remote System Shell Panel --}}
            <div class="panel shell-panel">
                <div class="panel-header">
                    <h2 class="panel-title">
                        <i class="bi bi-terminal"></i>
                        Remote System Shell
                    </h2>
                    <div class="shell-presets">
                        <select class="form-select form-select-sm" id="shellPresets">
                            <option value="">Quick Commands...</option>
                        </select>
                    </div>
                </div>
                <div class="panel-body shell-body">
                    <div class="terminal-output" id="terminalOutput">
                        <div class="terminal-line">
                            <span class="terminal-prompt">root@rvm-edge:~$</span>
                            <span class="terminal-text">Welcome to MyRVM Remote Shell</span>
                        </div>
                    </div>
                    <div class="terminal-input-line">
                        <span class="terminal-prompt">root@rvm-edge:~$</span>
                        <input type="text" class="terminal-input" id="terminalInput" placeholder="Enter command...">
                        <button class="btn-execute" id="btnExecuteCommand">
                            <i class="bi bi-play-fill"></i> Execute
                        </button>
                    </div>
                    <div class="shell-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span>Commands are executed directly on the Edge device. Use with caution.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.PLAYGROUND_CONFIG = {
        machineId: {{ $machine->id }},
        machineName: '{{ $machine->name }}',
        apiBaseUrl: '{{ url('/api/v1/playground') }}',
        csrfToken: '{{ csrf_token() }}'
    };
</script>
<script src="{{ asset('js/modules/playground.js') }}?v={{ time() }}"></script>
