/**
 * MyRVM Playground - JavaScript Module
 * Remote Engineering Lab Dashboard Logic
 * 
 * @requires apiHelper.js
 */

'use strict';

class PlaygroundManager {
    constructor(config) {
        this.machineId = config.machineId;
        this.machineName = config.machineName;
        this.apiBaseUrl = config.apiBaseUrl;
        this.csrfToken = config.csrfToken;
        
        // State
        this.models = [];
        this.selectedModel = null;
        this.components = [];
        this.selectedComponent = null;
        this.currentImage = null;
        this.imageList = [];
        this.isLiveMode = false;
        this.confidenceThreshold = 0.5;
        
        // Bind methods
        this.init = this.init.bind(this);
    }
    
    // =========================================================================
    // Initialization
    // =========================================================================
    async init() {
        try {
            await Promise.all([
                this.loadModels(),
                this.loadComponents(),
                this.loadShellCommands()
            ]);
            this.bindEvents();
            this.appendToTerminal('System initialized. Ready for commands.', 'info');
        } catch (error) {
            console.error('Playground init error:', error);
            this.appendToTerminal(`Initialization error: ${error.message}`, 'error');
        }
    }
    
    bindEvents() {
        // Toggle Live/Static
        document.querySelectorAll('.toggle-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleModeToggle(e));
        });
        
        // Viewport click (upload trigger)
        const viewport = document.getElementById('viewportMain');
        if (viewport) {
            viewport.addEventListener('click', () => this.triggerImageUpload());
            viewport.addEventListener('dragover', (e) => this.handleDragOver(e));
            viewport.addEventListener('drop', (e) => this.handleDrop(e));
        }
        
        // Image upload
        const imageUpload = document.getElementById('imageUpload');
        if (imageUpload) {
            imageUpload.addEventListener('change', (e) => this.handleImageUpload(e));
        }
        
        // Model dropdown
        const modelSelect = document.getElementById('modelSelect');
        if (modelSelect) {
            modelSelect.addEventListener('change', (e) => this.handleModelSelect(e));
        }
        
        // Download button
        const btnDownload = document.getElementById('btnDownloadModel');
        if (btnDownload) {
            btnDownload.addEventListener('click', () => this.downloadModel());
        }
        
        // Model upload
        const btnUploadModel = document.getElementById('btnUploadModel');
        const modelUpload = document.getElementById('modelUpload');
        if (btnUploadModel && modelUpload) {
            btnUploadModel.addEventListener('click', () => modelUpload.click());
            modelUpload.addEventListener('change', (e) => this.handleModelUpload(e));
        }
        
        // Confidence slider
        const confidenceSlider = document.getElementById('confidenceSlider');
        if (confidenceSlider) {
            confidenceSlider.addEventListener('input', (e) => {
                this.confidenceThreshold = parseFloat(e.target.value);
                document.getElementById('confidenceValue').textContent = this.confidenceThreshold.toFixed(2);
            });
        }
        
        // Run inference
        const btnInference = document.getElementById('btnRunInference');
        if (btnInference) {
            btnInference.addEventListener('click', () => this.runInference());
        }
        
        // Component select
        const componentSelect = document.getElementById('componentSelect');
        if (componentSelect) {
            componentSelect.addEventListener('change', (e) => this.handleComponentSelect(e));
        }
        
        // Read sensor
        const btnReadSensor = document.getElementById('btnReadSensor');
        if (btnReadSensor) {
            btnReadSensor.addEventListener('click', () => this.readSensor());
        }
        
        // Trigger actuator
        const btnTriggerOpen = document.getElementById('btnTriggerOpen');
        const btnTriggerClose = document.getElementById('btnTriggerClose');
        if (btnTriggerOpen) {
            btnTriggerOpen.addEventListener('click', () => this.triggerActuator('open'));
        }
        if (btnTriggerClose) {
            btnTriggerClose.addEventListener('click', () => this.triggerActuator('close'));
        }
        
        // Shell presets
        const shellPresets = document.getElementById('shellPresets');
        if (shellPresets) {
            shellPresets.addEventListener('change', (e) => {
                if (e.target.value) {
                    document.getElementById('terminalInput').value = e.target.value;
                }
            });
        }
        
        // Execute command
        const btnExecute = document.getElementById('btnExecuteCommand');
        const terminalInput = document.getElementById('terminalInput');
        if (btnExecute) {
            btnExecute.addEventListener('click', () => this.executeCommand());
        }
        if (terminalInput) {
            terminalInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') this.executeCommand();
            });
        }
        
        // Copy JSON
        const btnCopyJson = document.getElementById('btnCopyJson');
        if (btnCopyJson) {
            btnCopyJson.addEventListener('click', () => this.copyJsonResult());
        }
        
        // Toggle bounding boxes
        const btnToggleBbox = document.getElementById('btnToggleBbox');
        if (btnToggleBbox) {
            btnToggleBbox.addEventListener('click', () => this.toggleBoundingBoxes());
        }
        
        // Exit maintenance
        const btnExit = document.getElementById('btnExitMaintenance');
        if (btnExit) {
            btnExit.addEventListener('click', () => this.exitMaintenance());
        }
        
        // Live camera controls
        const btnActivateCamera = document.getElementById('btnActivateCamera');
        if (btnActivateCamera) {
            btnActivateCamera.addEventListener('click', () => this.activateCamera());
        }
        
        const btnCapture = document.getElementById('btnCapture');
        if (btnCapture) {
            btnCapture.addEventListener('click', () => this.captureImage());
        }
    }
    
    // =========================================================================
    // Vision Simulation
    // =========================================================================
    handleModeToggle(e) {
        const btn = e.currentTarget;
        const mode = btn.dataset.mode;
        
        document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        this.isLiveMode = (mode === 'live');
        
        const liveControls = document.getElementById('liveControls');
        const viewport = document.getElementById('viewportMain');
        
        if (this.isLiveMode) {
            liveControls.style.display = 'flex';
            viewport.querySelector('.viewport-placeholder p').textContent = 'Click "Aktifkan Kamera" to start live feed';
        } else {
            liveControls.style.display = 'none';
            viewport.querySelector('.viewport-placeholder p').textContent = 'Drag & drop image or click to upload';
        }
    }
    
    triggerImageUpload() {
        if (!this.isLiveMode) {
            document.getElementById('imageUpload').click();
        }
    }
    
    handleDragOver(e) {
        e.preventDefault();
        e.currentTarget.classList.add('drag-over');
    }
    
    handleDrop(e) {
        e.preventDefault();
        e.currentTarget.classList.remove('drag-over');
        
        const files = e.dataTransfer.files;
        if (files.length > 0 && files[0].type.startsWith('image/')) {
            this.processImage(files[0]);
        }
    }
    
    handleImageUpload(e) {
        const file = e.target.files[0];
        if (file) {
            this.processImage(file);
        }
    }
    
    async processImage(file) {
        // Show preview
        const reader = new FileReader();
        reader.onload = (e) => {
            const viewportImage = document.getElementById('viewportImage');
            const placeholder = document.querySelector('.viewport-placeholder');
            const viewport = document.getElementById('viewportMain');
            
            viewportImage.src = e.target.result;
            viewportImage.style.display = 'block';
            placeholder.style.display = 'none';
            viewport.classList.add('has-image');
            
            document.getElementById('viewportActions').style.display = 'flex';
            document.getElementById('btnRunInference').disabled = !this.selectedModel;
        };
        reader.readAsDataURL(file);
        
        this.currentImage = file;
        
        // Add to image list
        this.addToImageList(file);
    }
    
    addToImageList(file) {
        const imageList = document.getElementById('imageList');
        const empty = imageList.querySelector('.image-list-empty');
        if (empty) empty.remove();
        
        const reader = new FileReader();
        reader.onload = (e) => {
            const thumb = document.createElement('img');
            thumb.src = e.target.result;
            thumb.className = 'image-thumb selected';
            thumb.dataset.file = file.name;
            thumb.onclick = () => this.selectImage(thumb, file);
            
            // Remove selected from others
            imageList.querySelectorAll('.image-thumb').forEach(t => t.classList.remove('selected'));
            
            imageList.prepend(thumb);
            this.imageList.push({ file, element: thumb });
        };
        reader.readAsDataURL(file);
    }
    
    selectImage(thumb, file) {
        document.querySelectorAll('.image-thumb').forEach(t => t.classList.remove('selected'));
        thumb.classList.add('selected');
        this.currentImage = file;
        
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('viewportImage').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
    
    activateCamera() {
        this.appendToTerminal('Activating camera on Edge device...', 'info');
        // TODO: Implement WebRTC or polling for live feed
        Swal.fire({
            icon: 'info',
            title: 'Live Camera',
            text: 'Live camera feed requires Edge device WebRTC setup. Coming soon!',
            confirmButtonColor: '#10b981'
        });
    }
    
    captureImage() {
        this.appendToTerminal('Capturing image from live feed...', 'info');
        // TODO: Implement capture from live feed
    }
    
    // =========================================================================
    // Model Management
    // =========================================================================
    async loadModels() {
        try {
            const response = await this.apiRequest('GET', '/models');
            this.models = response.data || [];
            this.populateModelDropdown();
        } catch (error) {
            console.error('Failed to load models:', error);
        }
    }
    
    populateModelDropdown() {
        const select = document.getElementById('modelSelect');
        if (!select) return;
        
        select.innerHTML = '<option value="">Select AI Model...</option>';
        
        this.models.forEach(model => {
            const option = document.createElement('option');
            option.value = model.slug;
            option.textContent = model.name;
            option.dataset.status = model.status;
            select.appendChild(option);
        });
    }
    
    handleModelSelect(e) {
        const slug = e.target.value;
        const option = e.target.selectedOptions[0];
        const status = option?.dataset.status;
        
        const btnDownload = document.getElementById('btnDownloadModel');
        const btnDownloaded = document.getElementById('btnDownloaded');
        
        btnDownload.style.display = 'none';
        btnDownloaded.style.display = 'none';
        
        if (!slug) {
            this.selectedModel = null;
            document.getElementById('btnRunInference').disabled = true;
            return;
        }
        
        this.selectedModel = this.models.find(m => m.slug === slug);
        
        if (status === 'ready') {
            btnDownloaded.style.display = 'flex';
            document.getElementById('btnRunInference').disabled = !this.currentImage;
        } else if (status === 'available') {
            btnDownload.style.display = 'flex';
            document.getElementById('btnRunInference').disabled = true;
        } else if (status === 'downloading') {
            this.showDownloadProgress();
            document.getElementById('btnRunInference').disabled = true;
        }
    }
    
    async downloadModel() {
        if (!this.selectedModel) return;
        
        const btnDownload = document.getElementById('btnDownloadModel');
        btnDownload.disabled = true;
        btnDownload.innerHTML = '<i class="bi bi-hourglass-split"></i> Downloading...';
        
        this.showDownloadProgress();
        
        try {
            await this.apiRequest('POST', `/models/${this.selectedModel.slug}/download`, {
                machine_id: this.machineId
            });
            
            this.appendToTerminal(`Model download queued: ${this.selectedModel.name}`, 'info');
            
            // Simulate progress (real implementation would poll status)
            await this.simulateDownloadProgress();
            
            // Update status
            this.selectedModel.status = 'ready';
            const option = document.querySelector(`#modelSelect option[value="${this.selectedModel.slug}"]`);
            if (option) option.dataset.status = 'ready';
            
            document.getElementById('modelProgress').style.display = 'none';
            document.getElementById('btnDownloadModel').style.display = 'none';
            document.getElementById('btnDownloaded').style.display = 'flex';
            document.getElementById('btnRunInference').disabled = !this.currentImage;
            
            Swal.fire({
                icon: 'success',
                title: 'Download Complete',
                text: `${this.selectedModel.name} is ready to use!`,
                timer: 2000,
                showConfirmButton: false
            });
            
        } catch (error) {
            console.error('Download failed:', error);
            btnDownload.disabled = false;
            btnDownload.innerHTML = '<i class="bi bi-cloud-download"></i> Download';
            document.getElementById('modelProgress').style.display = 'none';
            
            Swal.fire({
                icon: 'error',
                title: 'Download Failed',
                text: error.message
            });
        }
    }
    
    showDownloadProgress() {
        document.getElementById('modelProgress').style.display = 'block';
    }
    
    async simulateDownloadProgress() {
        const fill = document.getElementById('progressFill');
        const text = document.getElementById('progressText');
        
        for (let i = 0; i <= 100; i += 10) {
            fill.style.width = `${i}%`;
            text.textContent = `Downloading... ${i}%`;
            await new Promise(r => setTimeout(r, 300));
        }
    }
    
    async handleModelUpload(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const formData = new FormData();
        formData.append('model_file', file);
        formData.append('name', file.name.replace(/\.[^/.]+$/, ''));
        
        try {
            Swal.fire({
                title: 'Uploading Model...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            
            const response = await this.apiRequest('POST', '/models/upload', formData, true);
            
            Swal.fire({
                icon: 'success',
                title: 'Upload Complete',
                text: response.message,
                timer: 2000,
                showConfirmButton: false
            });
            
            await this.loadModels();
            
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Upload Failed',
                text: error.message
            });
        }
    }
    
    // =========================================================================
    // Inference
    // =========================================================================
    async runInference() {
        if (!this.currentImage || !this.selectedModel) return;
        
        const btn = document.getElementById('btnRunInference');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
        
        try {
            const formData = new FormData();
            formData.append('image', this.currentImage);
            formData.append('model_slug', this.selectedModel.slug);
            formData.append('confidence', this.confidenceThreshold);
            
            const response = await this.apiRequest(
                'POST', 
                `/machines/${this.machineId}/inference`, 
                formData, 
                true
            );
            
            this.appendToTerminal(`Inference queued for model: ${this.selectedModel.name}`, 'info');
            
            // Display mock result (real implementation would poll for results)
            const mockResult = {
                detections: [
                    { class: 'mineral', confidence: 0.98, bbox: [120, 80, 200, 160] },
                    { class: 'organic', confidence: 0.85, bbox: [250, 100, 180, 140] }
                ],
                model: this.selectedModel.name,
                inference_time: '42ms'
            };
            
            this.displayResults(mockResult);
            
        } catch (error) {
            console.error('Inference failed:', error);
            this.displayResults({ error: error.message });
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-play-fill"></i> Run Inference';
        }
    }
    
    displayResults(data) {
        const console = document.getElementById('jsonConsole');
        console.textContent = JSON.stringify(data, null, 2);
        
        // Draw bounding boxes if available
        if (data.detections && data.detections.length > 0) {
            this.drawBoundingBoxes(data.detections);
        }
    }
    
    drawBoundingBoxes(detections) {
        const canvas = document.getElementById('boundingBoxCanvas');
        const img = document.getElementById('viewportImage');
        
        canvas.style.display = 'block';
        canvas.width = img.offsetWidth;
        canvas.height = img.offsetHeight;
        
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Scale factor
        const scaleX = img.offsetWidth / img.naturalWidth;
        const scaleY = img.offsetHeight / img.naturalHeight;
        
        detections.forEach(det => {
            const [x, y, w, h] = det.bbox;
            const sx = x * scaleX;
            const sy = y * scaleY;
            const sw = w * scaleX;
            const sh = h * scaleY;
            
            // Box
            ctx.strokeStyle = '#10b981';
            ctx.lineWidth = 2;
            ctx.strokeRect(sx, sy, sw, sh);
            
            // Label background
            ctx.fillStyle = '#10b981';
            ctx.fillRect(sx, sy - 22, sw, 22);
            
            // Label text
            ctx.fillStyle = 'white';
            ctx.font = '12px Inter, sans-serif';
            ctx.fillText(`${det.class} ${(det.confidence * 100).toFixed(0)}%`, sx + 4, sy - 6);
        });
    }
    
    toggleBoundingBoxes() {
        const canvas = document.getElementById('boundingBoxCanvas');
        canvas.style.display = canvas.style.display === 'none' ? 'block' : 'none';
    }
    
    copyJsonResult() {
        const json = document.getElementById('jsonConsole').textContent;
        navigator.clipboard.writeText(json).then(() => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Copied to clipboard',
                showConfirmButton: false,
                timer: 1500
            });
        });
    }
    
    // =========================================================================
    // Component Inspector
    // =========================================================================
    async loadComponents() {
        try {
            const response = await this.apiRequest('GET', `/machines/${this.machineId}/components`);
            this.components = response.data || [];
            this.populateComponentDropdown();
        } catch (error) {
            console.error('Failed to load components:', error);
        }
    }
    
    populateComponentDropdown() {
        const select = document.getElementById('componentSelect');
        if (!select) return;
        
        select.innerHTML = '<option value="">Select Component...</option>';
        
        this.components.forEach(comp => {
            const option = document.createElement('option');
            option.value = comp.name;
            option.textContent = `${comp.name} (${comp.type})`;
            option.dataset.type = comp.type;
            select.appendChild(option);
        });
    }
    
    handleComponentSelect(e) {
        const name = e.target.value;
        const option = e.target.selectedOptions[0];
        const type = option?.dataset.type;
        
        const actions = document.getElementById('componentActions');
        const sensorActions = document.getElementById('sensorActions');
        const actuatorActions = document.getElementById('actuatorActions');
        const valueCard = document.getElementById('componentValueCard');
        
        if (!name) {
            actions.style.display = 'none';
            valueCard.style.display = 'none';
            this.selectedComponent = null;
            return;
        }
        
        this.selectedComponent = this.components.find(c => c.name === name);
        actions.style.display = 'block';
        
        if (type === 'sensor') {
            sensorActions.style.display = 'flex';
            actuatorActions.style.display = 'none';
        } else {
            sensorActions.style.display = 'none';
            actuatorActions.style.display = 'flex';
        }
    }
    
    async readSensor() {
        if (!this.selectedComponent) return;
        
        const btn = document.getElementById('btnReadSensor');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Reading...';
        
        try {
            await this.apiRequest('POST', `/machines/${this.machineId}/components/read`, {
                component_name: this.selectedComponent.name
            });
            
            this.appendToTerminal(`Reading sensor: ${this.selectedComponent.name}`, 'info');
            
            // Mock value (real implementation would poll for result)
            const mockValue = (Math.random() * 100).toFixed(1);
            this.displayComponentValue(mockValue, 'cm');
            
        } catch (error) {
            console.error('Read failed:', error);
            Swal.fire({
                icon: 'error',
                title: 'Read Failed',
                text: error.message
            });
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-eyedropper"></i> Read Value';
        }
    }
    
    async triggerActuator(action) {
        if (!this.selectedComponent) return;
        
        try {
            await this.apiRequest('POST', `/machines/${this.machineId}/components/trigger`, {
                component_name: this.selectedComponent.name,
                action: action
            });
            
            this.appendToTerminal(`Triggered: ${this.selectedComponent.name} → ${action.toUpperCase()}`, 'info');
            this.displayComponentValue(action.toUpperCase(), '');
            
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `${action.toUpperCase()} sent!`,
                showConfirmButton: false,
                timer: 1500
            });
            
        } catch (error) {
            console.error('Trigger failed:', error);
            Swal.fire({
                icon: 'error',
                title: 'Trigger Failed',
                text: error.message
            });
        }
    }
    
    displayComponentValue(value, unit) {
        const card = document.getElementById('componentValueCard');
        const valueEl = document.getElementById('componentValue');
        const unitEl = document.getElementById('componentUnit');
        const timestamp = document.getElementById('componentTimestamp');
        
        card.style.display = 'block';
        valueEl.textContent = value;
        unitEl.textContent = unit;
        timestamp.textContent = `Last read: ${new Date().toLocaleTimeString()}`;
    }
    
    // =========================================================================
    // Remote Shell
    // =========================================================================
    async loadShellCommands() {
        try {
            const response = await this.apiRequest('GET', '/shell/commands');
            const grouped = response.data || {};
            this.populateShellPresets(grouped);
        } catch (error) {
            console.error('Failed to load shell commands:', error);
        }
    }
    
    populateShellPresets(grouped) {
        const select = document.getElementById('shellPresets');
        if (!select) return;
        
        select.innerHTML = '<option value="">Quick Commands...</option>';
        
        Object.entries(grouped).forEach(([category, commands]) => {
            const optgroup = document.createElement('optgroup');
            optgroup.label = category.charAt(0).toUpperCase() + category.slice(1);
            
            commands.forEach(cmd => {
                const option = document.createElement('option');
                option.value = cmd.command;
                option.textContent = cmd.label;
                if (cmd.is_dangerous) {
                    option.textContent += ' ⚠️';
                }
                optgroup.appendChild(option);
            });
            
            select.appendChild(optgroup);
        });
    }
    
    async executeCommand() {
        const input = document.getElementById('terminalInput');
        const command = input.value.trim();
        
        if (!command) return;
        
        // Echo command
        this.appendToTerminal(command, 'command');
        input.value = '';
        
        try {
            await this.apiRequest('POST', `/machines/${this.machineId}/shell/execute`, {
                command: command
            });
            
            // Mock output (real implementation would poll for result)
            setTimeout(() => {
                if (command.includes('df')) {
                    this.appendToTerminal('Filesystem      Size  Used Avail Use% Mounted on\n/dev/root        29G  5.2G   22G  19% /', 'output');
                } else if (command.includes('free')) {
                    this.appendToTerminal('              total        used        free\nMem:           3.7Gi       1.2Gi       2.1Gi', 'output');
                } else {
                    this.appendToTerminal('Command queued for execution on Edge device.', 'info');
                }
            }, 500);
            
        } catch (error) {
            this.appendToTerminal(`Error: ${error.message}`, 'error');
        }
    }
    
    appendToTerminal(text, type = 'output') {
        const output = document.getElementById('terminalOutput');
        const line = document.createElement('div');
        line.className = 'terminal-line';
        
        if (type === 'command') {
            line.innerHTML = `<span class="terminal-prompt">root@rvm-edge:~$</span><span class="terminal-text">${this.escapeHtml(text)}</span>`;
        } else {
            const textClass = type === 'error' ? 'terminal-text error' : 
                              type === 'info' ? 'terminal-text info' : 'terminal-text';
            line.innerHTML = `<span class="${textClass}">${this.escapeHtml(text)}</span>`;
        }
        
        output.appendChild(line);
        output.scrollTop = output.scrollHeight;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // =========================================================================
    // Exit Maintenance
    // =========================================================================
    async exitMaintenance() {
        const result = await Swal.fire({
            title: 'Exit Maintenance Mode?',
            text: 'The machine will return to normal operation.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, Exit',
            cancelButtonText: 'Cancel'
        });
        
        if (result.isConfirmed) {
            try {
                // Call exit maintenance API
                await fetch(`/api/v1/edge/${this.machineId}/command`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: JSON.stringify({ action: 'EXIT_MAINTENANCE' })
                });
                
                Swal.fire({
                    icon: 'success',
                    title: 'Exited Maintenance',
                    text: 'Returning to dashboard...',
                    timer: 2000,
                    showConfirmButton: false
                });
                
                setTimeout(() => {
                    window.close();
                    // Fallback if window.close doesn't work
                    window.location.href = '/dashboard/machines';
                }, 2000);
                
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Failed',
                    text: error.message
                });
            }
        }
    }
    
    // =========================================================================
    // API Helper
    // =========================================================================
    async apiRequest(method, endpoint, data = null, isFormData = false) {
        const url = this.apiBaseUrl + endpoint;
        const options = {
            method: method,
            headers: {
                'X-CSRF-TOKEN': this.csrfToken,
                'Accept': 'application/json'
            }
        };
        
        if (data) {
            if (isFormData) {
                options.body = data;
            } else {
                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(data);
            }
        }
        
        const response = await fetch(url, options);
        const json = await response.json();
        
        if (!response.ok) {
            throw new Error(json.message || 'Request failed');
        }
        
        return json;
    }
}

// =========================================================================
// Initialize on DOM Ready
// =========================================================================
document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.PLAYGROUND_CONFIG !== 'undefined') {
        const playground = new PlaygroundManager(window.PLAYGROUND_CONFIG);
        playground.init();
        
        // Expose for debugging
        window.playground = playground;
    }
});
