@extends('layouts.blank')

@section('title', 'Maintenance Monitor - ' . $machine->name)

@section('content')
<div class="maintenance-window">
    <!-- Header -->
    <div class="mw-header">
        <div class="d-flex align-items-center">
            <div class="machine-icon me-3">
                <i class="ti tabler-robot"></i>
            </div>
            <div>
                <h4 class="mb-0 fw-bold text-white">{{ $machine->name }}</h4>
                <div class="text-white-50 small">Serial: {{ $machine->serial_number }}</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="connection-status">
                <span class="status-dot"></span>
                <span class="status-text">Connected</span>
            </div>
            <button id="btn-exit-maintenance" class="btn btn-danger btn-sm fw-bold">
                <i class="ti tabler-door-exit me-1"></i> Exit Maintenance
            </button>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="mw-grid">
        <!-- Live Camera (Left 50%) -->
        <div class="mw-card">
            <div class="mw-card-header d-flex justify-content-between align-items-center">
                <div><i class="ti ti-video me-2"></i> Live Camera</div>
                <div class="d-flex gap-2 align-items-center">
                     <span class="text-secondary small" id="label-camera-port" style="display:none; white-space: nowrap;">Choose the Camera Port</span>
                     <select class="form-select form-select-sm bg-dark text-white border-secondary" id="camera-select" style="width: auto; max-width: 120px; display:none;">
                        <option value="" selected disabled>Select</option>
                     </select>
                     <button class="btn btn-sm btn-outline-light" id="btn-refresh-camera" title="Restart Service">
                        <i class="ti ti-refresh"></i>
                     </button>
                </div>
            </div>
            <div class="mw-card-body p-2">
                <div class="row h-100 g-2">
                    <!-- Raw Viewport -->
                    <div class="col-6">
                        <div class="d-flex justify-content-between mb-1 text-uppercase">
                            <span class="small text-secondary fw-bold" style="font-size: 0.7rem;">RAW FEED</span>
                        </div>
                        <div class="camera-viewport border border-secondary rounded d-flex align-items-center justify-content-center bg-black overflow-hidden position-relative" id="viewport-raw" style="height: 200px; width: 100%;">
                            <!-- LIVE Indicator (Top Right) -->
                            <div class="position-absolute top-0 end-0 m-2" id="rec-indicator" style="display:none; z-index: 10;">
                                <span class="align-items-center fw-bold text-danger bg-dark bg-opacity-75 px-2 py-1 rounded small" style="font-size: 0.7rem; letter-spacing: 0.5px; backdrop-filter: blur(2px);">
                                    <span class="live-dot"></span>
                                    LIVE
                                </span>
                            </div>

                            <div class="text-secondary text-center" id="raw-placeholder">
                                <i class="ti ti-video-off" style="font-size: 2rem;"></i>
                                <div class="mt-1 small" style="font-size: 0.75rem;">Offline</div>
                                
                                <!-- Activation Button (Moved Inside) -->
                                <div class="mt-3" id="overlay-activate">
                                    <button class="btn btn-primary btn-sm rounded-pill px-3 py-1 shadow-lg d-flex align-items-center gap-2 mx-auto" id="btn-activate-camera" style="font-size: 0.8rem;">
                                        <i class="ti ti-camera"></i> 
                                        <span>Aktifkan</span>
                                    </button>
                                </div>
                            </div>

                             <!-- Capture Overlay (Hidden by default) -->
                            <div class="overlay-bottom" id="overlay-capture" style="display:none; width: 100%; justify-content: center; padding-bottom: 10px;">
                                <button class="btn btn-light btn-sm rounded-pill px-3 shadow-lg fw-bold text-primary d-flex align-items-center gap-1" id="btn-capture-camera" style="opacity: 0.5;">
                                    <i class="ti ti-aperture"></i> Cap
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Result Viewport -->
                    <div class="col-6">
                         <div class="d-flex justify-content-between mb-1 text-uppercase">
                            <span class="small text-secondary fw-bold" style="font-size: 0.7rem;">RESULT FEED</span>
                            <span class="small text-muted" id="result-meta" style="font-size: 0.7rem;"></span>
                        </div>
                         <div class="camera-viewport border border-secondary rounded d-flex align-items-center justify-content-center bg-dark" id="viewport-result" style="height: 200px; width: 100%;">
                            <div class="text-secondary text-center" id="result-placeholder">
                                <i class="ti ti-photo" style="font-size: 2rem; opacity: 0.5;"></i>
                            </div>
                            <img src="" class="img-fluid d-none" id="result-image" alt="Captured Result" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hardware Check (Right 50%) -->
        <div class="mw-card">
             <div class="mw-card-header">
                <i class="ti ti-camera me-2"></i> Hardware Check
            </div>
            <div class="mw-card-body">
                <div class="hardware-list">
                    <div class="hardware-item">
                        <span><i class="ti ti-camera text-success me-2"></i>Camera</span>
                        <span class="badge bg-success" id="status-camera">Ready</span>
                    </div>
                    <div class="hardware-item">
                        <span><i class="ti ti-engine text-success me-2"></i>Motors</span>
                        <span class="badge bg-success" id="status-motor">Ready</span>
                    </div>
                    <div class="hardware-item">
                        <span><i class="ti ti-wifi text-success me-2"></i>Network</span>
                        <span class="badge bg-success" id="status-network">Online</span>
                    </div>
                </div>
                
                <div class="mt-3 text-center">
                    <button class="btn btn-outline-light btn-sm w-100" onclick="window.location.reload()">
                        <i class="ti ti-refresh me-1"></i> Refresh Status
                    </button>
                </div>
            </div>
        </div>

        <!-- Activity Log -->
        <div class="mw-card span-full">
            <div class="mw-card-header d-flex justify-content-between">
                <div><i class="ti ti-list me-2"></i> Maintenance Log</div>
                <span class="badge bg-warning text-dark">Live</span>
            </div>
            <div class="mw-card-body p-0">
                <div class="log-container" id="log-container">
                    <div class="log-entry">
                        <span class="log-time text-muted">{{ now()->format('H:i:s') }}</span>
                        <span class="log-msg text-info">Maintenance Mode Window Opened</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --mw-bg: #0f172a;
        --mw-card-bg: #1e293b;
        --mw-text: #e2e8f0;
        --mw-accent: #3b82f6;
    }
    
    body {
        background-color: var(--mw-bg);
        color: var(--mw-text);
        font-family: 'Inter', sans-serif;
        height: 100vh;
        overflow: hidden;
    }

    .maintenance-window {
        display: flex;
        flex-direction: column;
        height: 100%;
        padding: 1rem;
    }

    .mw-header {
        background: var(--mw-card-bg);
        padding: 1rem;
        border-radius: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        border: 1px solid rgba(255,255,255,0.05);
    }

    .machine-icon {
        width: 48px;
        height: 48px;
        background: var(--mw-accent);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }
@push('css')
<style>
    .live-dot {
        width: 8px;
        height: 8px;
        background-color: #ef4444; /* Tailwind red-500 */
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
        animation: pulse-red 2s infinite;
    }

    @keyframes pulse-red {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }
</style>
@endpush
    .mw-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto 1fr;
        gap: 1rem;
        flex: 1;
        min-height: 0;
    }

    .mw-card {
        background: var(--mw-card-bg);
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,0.05);
        display: flex;
        flex-direction: column;
    }
    
    .span-full {
        grid-column: span 2;
    }

    .mw-card-header {
        padding: 1rem;
        font-weight: 600;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        background: rgba(0,0,0,0.1);
        border-radius: 12px 12px 0 0;
    }

    .mw-card-body {
        padding: 1rem;
        flex: 1;
        overflow-y: auto;
    }

    .metric-box {
        background: rgba(255,255,255,0.03);
        padding: 0.75rem;
        border-radius: 8px;
        transition: transform 0.2s;
    }
    
    .metric-box:hover {
        background: rgba(255,255,255,0.08);
        transform: translateY(-2px);
    }

    .metric-value {
        font-size: 1.25rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .metric-label {
        font-size: 0.75rem;
        color: #94a3b8;
    }
    
    .hardware-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .hardware-item {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem;
        background: rgba(255,255,255,0.03);
        border-radius: 8px;
    }

    .log-container {
        height: 100%;
        overflow-y: auto;
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.85rem;
    }

    .log-entry {
        padding: 0.5rem 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.03);
        display: flex;
        gap: 1rem;
    }
    
    .log-entry:hover {
        background: rgba(255,255,255,0.03);
    }

    .status-dot {
        width: 8px;
        height: 8px;
        background: #10b981;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 8px #10b981;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 0.5; transform: scale(0.9); }
        50% { opacity: 1; transform: scale(1.1); }
        100% { opacity: 0.5; transform: scale(0.9); }
    }
    /* Camera UI Styles */
    .camera-viewport {
        background: #000;
        min-height: 220px;
        position: relative;
    }
    .overlay-center {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 10;
    }
    .overlay-bottom {
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10;
        width: 100%;
        display: flex;
        justify-content: center;
        padding-bottom: 15px;
    }
</style>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="exitConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-white" style="background: var(--mw-card-bg); border: 1px solid rgba(255,255,255,0.1);">
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <i class="ti tabler-alert-triangle text-warning" style="font-size: 48px;"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Exit Maintenance Mode?</h5>
                    <p class="text-white-50 mb-4">
                        Machine will return to <strong>Online</strong> status.<br>
                        Make sure all physical work is completed.
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-warning fw-bold" id="btn-confirm-exit">
                            Yes, Exit Mode
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    const MACHINE_ID = {{ $machine->id }};
    const API_BASE = '/api/v1';

    // UI Elements
    const exitBtn = document.getElementById('btn-exit-maintenance');
    const confirmBtn = document.getElementById('btn-confirm-exit');
    let exitModal;

    // Initialize Modal
    document.addEventListener('DOMContentLoaded', () => {
        exitModal = new bootstrap.Modal(document.getElementById('exitConfirmModal'));
    });

    // Open Modal
    exitBtn.addEventListener('click', () => {
        exitModal.show();
    });

    // Handle Confirmed Exit
    confirmBtn.addEventListener('click', async () => {
        // Disable button to prevent double clicks
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Exiting...';

        try {
            await fetch(`${API_BASE}/edge/devices/${MACHINE_ID}/command`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ action: 'EXIT_MAINTENANCE' })
            });
            
            // Log locally
            exitModal.hide();
            addLog('EXIT COMMAND SENT', 'text-warning fw-bold');
            addLog('Closing window in 3 seconds...', 'text-muted');
            
            setTimeout(() => window.close(), 3000);
            
        } catch (e) {
            addLog('Error: ' + e.message, 'text-danger');
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Yes, Exit Mode';
            exitModal.hide();
        }
    });
    
    function addLog(msg, cls = 'text-white') {
        const container = document.getElementById('log-container');
        const time = new Date().toLocaleTimeString();
        const html = `
            <div class="log-entry">
                <span class="log-time text-muted">${time}</span>
                <span class="log-msg ${cls}">${msg}</span>
            </div>
        `;
        container.insertAdjacentHTML('afterbegin', html);
    }
    
    // Initial enter maintenance trigger (idempotent)
    (async () => {
        try {
            await fetch(`${API_BASE}/edge/devices/${MACHINE_ID}/command`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ action: 'MAINTENANCE' })
            });
            addLog('Sent command: ENTER MAINTENANCE', 'text-success');
        } catch (e) {
            console.error(e);
        }
    })();
    
    // Polling simulation for UI demo (Real implementation would use Echo)
    setInterval(() => {
        // Here we would fetch status...
    }, 5000);

    // ==========================================
    // Camera UI Logic
    // ==========================================
    const btnActivate = document.getElementById('btn-activate-camera');
    const btnCapture = document.getElementById('btn-capture-camera');
    const overlayActivate = document.getElementById('overlay-activate');
    const overlayCapture = document.getElementById('overlay-capture');
    const viewportRaw = document.getElementById('viewport-raw');
    const rawPlaceholder = document.getElementById('raw-placeholder');
    const cameraSelect = document.getElementById('camera-select');
    const recIndicator = document.getElementById('rec-indicator');
    
    // 1. Activate Logic
    btnActivate.addEventListener('click', async () => {
        btnActivate.disabled = true;
        btnActivate.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Permissions...';
        
        addLog('Requesting Camera Permission...', 'text-info');
        
        try {
            // Mocking the handshake/permission delay
            await new Promise(r => setTimeout(r, 1500));
            
            // Server Command Trigger
            await fetch(`${API_BASE}/edge/devices/${MACHINE_ID}/command`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ action: 'CAMERA_PERMISSION' })
            });

            // Hide Activate Button Logic (Nested in placeholder, so hiding placeholder is enough)
            rawPlaceholder.style.display = 'none'; 
            
            // Clear Placeholder Content Completely
            viewportRaw.style.background = '#000'; // Pure black for feed
            rawPlaceholder.innerHTML = ''; // Removed text and icon as requested
            rawPlaceholder.style.display = 'none'; // Hide it
            
            // Show Controls
            overlayCapture.style.display = 'flex';
            cameraSelect.style.display = 'block';
            document.getElementById('label-camera-port').style.display = 'inline-block'; // Show label
            recIndicator.style.display = 'block'; // Show Live Pulse (absolute positioned now)
            
            // Populate Dropdown (Mock)
            cameraSelect.innerHTML = '<option value="/dev/video0" selected>/dev/video0 (Usb Camera)</option><option value="/dev/video2">/dev/video2 (CSI Camera)</option>';
            
            addLog('Camera Service Restarted. Feed Active.', 'text-success');

        } catch (e) {
            addLog('Failed to activate camera: ' + e.message, 'text-danger');
            btnActivate.disabled = false;
            rawPlaceholder.style.display = 'block'; // Ensure placeholder (and button) is visible again
            btnActivate.innerHTML = '<i class="ti ti-camera"></i> <span>Aktifkan</span>';
        }
    });

    // 2. Capture Logic
    btnCapture.addEventListener('click', async () => {
        const originalText = btnCapture.innerHTML;
        btnCapture.disabled = true;
        btnCapture.innerHTML = '<span class="spinner-grow spinner-grow-sm me-2"></span> Capturing...';
        
        addLog('Capturing image...', 'text-warning');
        
        try {
            // Trigger Capture Command
            await fetch(`${API_BASE}/edge/devices/${MACHINE_ID}/command`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ action: 'CAPTURE_IMAGE', port: cameraSelect.value })
            });
            
            // Record start time to ensure we only show NEW images
            const commandStartTime = new Date().getTime();
            
            // TWO-PHASE POLLING:
            // Phase 1: Poll lightweight /capture-status for `last_capture_at > startTime`
            // Phase 2: Once confirmed, fetch the actual image
            
            let attempts = 0;
            const maxAttempts = 60; // 60 * 2s = 120s timeout
            
            const pollCaptureStatus = setInterval(async () => {
                attempts++;
                if (attempts > maxAttempts) {
                    clearInterval(pollCaptureStatus);
                    addLog('Capture timeout: Device did not respond in time (Check Heartbeat).', 'text-danger');
                    btnCapture.disabled = false;
                    btnCapture.innerHTML = originalText;
                    return;
                }
                
                btnCapture.innerHTML = `<span class="spinner-grow spinner-grow-sm me-2"></span> Waiting for Device (${attempts*2}s)...`;

                try {
                    // Phase 1: Check capture status
                    const statusRes = await fetch(`${API_BASE}/rvm-machines/${MACHINE_ID}/capture-status`);
                    if (!statusRes.ok) return; // Skip this iteration
                    
                    const statusJson = await statusRes.json();
                    const lastCaptureAt = statusJson.data.last_capture_at;
                    
                    // DEBUG: Log comparison values
                    const lastCaptureTime = lastCaptureAt ? new Date(lastCaptureAt).getTime() : 0;
                    console.log(`DEBUG: commandStartTime=${commandStartTime}, lastCaptureAt=${lastCaptureAt}, lastCaptureTime=${lastCaptureTime}, diff=${lastCaptureTime - commandStartTime}`);
                    
                    // Check if capture is newer than button click
                    if (lastCaptureAt && lastCaptureTime > commandStartTime) {
                        addLog('Edge confirmed capture. Fetching image...', 'text-info');
                        
                        // Phase 2: Fetch actual image
                        const imageRes = await fetch(`${API_BASE}/rvm-machines/${MACHINE_ID}/latest-image`);
                        if (imageRes.ok) {
                            const json = await imageRes.json();
                            clearInterval(pollCaptureStatus);
                            
                            // Display image in RAW FEED (left viewport)
                            const rawViewport = document.getElementById('viewport-raw');
                            const rawPlaceholder = document.getElementById('raw-placeholder');
                            
                            // Create or update image element in raw viewport
                            let rawImg = document.getElementById('raw-feed-image');
                            if (!rawImg) {
                                rawImg = document.createElement('img');
                                rawImg.id = 'raw-feed-image';
                                rawImg.className = 'img-fluid';
                                // Force sRGB color space rendering for Chrome
                                rawImg.style.cssText = 'max-height: 100%; max-width: 100%; object-fit: contain; color-interpolation-filters: sRGB; image-rendering: -webkit-optimize-contrast;';
                                rawViewport.appendChild(rawImg);
                            }
                            
                            rawImg.src = `${json.data.url}?t=${new Date().getTime()}`;
                            rawPlaceholder.style.display = 'none';
                            
                            // Hide LIVE indicator after image is shown
                            const recIndicator = document.getElementById('rec-indicator');
                            if (recIndicator) {
                                recIndicator.style.display = 'none';
                            }
                            
                            // Update capture button to "Recapture"
                            const captureBtn = document.getElementById('btn-capture-camera');
                            if (captureBtn) {
                                captureBtn.innerHTML = '<i class="ti ti-camera-rotate"></i> Recapture';
                            }
                            
                            // Update timestamp (you can add a timestamp display in Raw Feed if needed)
                            addLog('Image captured and synced successfully.', 'text-success');
                            
                            btnCapture.disabled = false;
                            btnCapture.innerHTML = originalText;
                        }
                    }
                } catch (e) {
                    console.error("Polling error", e);
                }
            }, 2000); // Poll every 2 seconds

        } catch (e) {
            addLog('Capture command failed: ' + e.message, 'text-danger');
            btnCapture.disabled = false;
            btnCapture.innerHTML = originalText;
        }
    });
</script>
@endsection
