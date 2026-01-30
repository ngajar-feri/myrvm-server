/**
 * CV Servers Management Module
 * Handles training jobs, model repository
 */

class CVServerManagement {
    constructor() {
        this.trainingJobs = [];
        this.models = [];
        this.init();
    }

    init() {
        document.addEventListener('pageLoaded', (e) => {
            if (e.detail.page === 'cv-servers') {
                this.setupEventListeners();
                this.loadTrainingJobs();
                this.loadModels();
                this.startAutoRefresh();
            }
        });

        if (window.location.pathname.includes('/cv-servers')) {
            this.setupEventListeners();
            this.loadTrainingJobs();
            this.loadModels();
            this.startAutoRefresh();
        }
    }

    setupEventListeners() {
        const newTrainingForm = document.getElementById('new-training-form');
        if (newTrainingForm) {
            newTrainingForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.startTraining(new FormData(newTrainingForm));
            });
        }
    }

    async loadTrainingJobs() {
        try {
            const response = await fetch('/api/v1/cv/training-jobs', {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) throw new Error('Failed to load training jobs');

            const data = await response.json();
            this.trainingJobs = data.data || data;

            this.renderTrainingJobs();
            this.updateJobStats();

        } catch (error) {
            console.error('Error loading training jobs:', error);
            this.showError('Failed to load training jobs');
        }
    }

    async loadModels() {
        try {
            const response = await fetch('/api/v1/cv/models', {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) throw new Error('Failed to load models');

            const data = await response.json();
            this.models = data.data || data;

            this.renderModels();

        } catch (error) {
            console.error('Error loading models:', error);
        }
    }

    renderTrainingJobs() {
        const tbody = document.getElementById('training-jobs-table');
        if (!tbody) return;

        if (this.trainingJobs.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="empty-state">
                            <i class="ti tabler-server empty-state-icon"></i>
                            <div class="empty-state-title">No training jobs</div>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.trainingJobs.map(job => {
            const progress = job.progress || 0;
            const status = job.status || 'queued';

            return `
                <tr>
                    <td>
                        <span class="font-monospace">#TRN-${String(job.id).padStart(3, '0')}</span>
                    </td>
                    <td>${this.escapeHtml(job.model_name || job.version)}</td>
                    <td>${this.escapeHtml(job.dataset_name || 'Unknown')}</td>
                    <td>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar ${this.getProgressClass(status)} progress-bar-striped ${status === 'training' ? 'progress-bar-animated' : ''}" 
                                 style="width: ${progress}%">${progress}%</div>
                        </div>
                    </td>
                    <td>
                        <span class="badge ${this.getStatusBadge(status)}">
                            <i class="ti ${this.getStatusIcon(status)}"></i>
                            ${status}
                        </span>
                    </td>
                    <td>
                        <small>
                            ${job.loss ? `Loss: ${job.loss}<br>` : ''}
                            ${job.accuracy ? `mAP: ${job.accuracy}` : 'N/A'}
                        </small>
                    </td>
                    <td>${this.formatDate(job.created_at)}</td>
                    <td>
                        ${status === 'training' || status === 'queued' ? `
                            <button class="btn btn-sm btn-danger" onclick="cvServerManagement.cancelJob(${job.id})">
                                <i class="ti tabler-x"></i>
                            </button>
                        ` : `
                            <button class="btn btn-sm btn-outline-primary" onclick="cvServerManagement.viewJob(${job.id})">
                                <i class="ti tabler-eye"></i>
                            </button>
                        `}
                    </td>
                </tr>
            `;
        }).join('');
    }

    renderModels() {
        const grid = document.getElementById('models-grid');
        if (!grid) return;

        if (this.models.length === 0) {
            grid.innerHTML = `
                <div class="col-12">
                    <div class="empty-state">
                        <i class="ti tabler-database empty-state-icon"></i>
                        <div class="empty-state-title">No models found</div>
                    </div>
                </div>
            `;
            return;
        }

        grid.innerHTML = this.models.map(model => `
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">${this.escapeHtml(model.version || model.model_name)}</h6>
                            ${model.is_production ? '<span class="badge bg-success">Production</span>' : ''}
                            ${model.is_active ? '<span class="badge bg-primary">Active</span>' : ''}
                        </div>
                        <p class="text-muted small mb-2">${this.escapeHtml(model.description || 'No description')}</p>
                        
                        <div class="row g-2 text-center small">
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <div class="fw-semibold">${model.deployed_devices || 0}</div>
                                    <div class="text-muted">Deployed</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <div class="fw-semibold">${model.download_count || 0}</div>
                                    <div class="text-muted">Downloads</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <div class="fw-semibold">${this.formatFileSize(model.file_size)}</div>
                                    <div class="text-muted">Size</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-sm btn-outline-primary flex-fill">
                                <i class="ti tabler-download me-1"></i>Download
                            </button>
                            <button class="btn btn-sm btn-outline-secondary flex-fill">
                                <i class="ti tabler-refresh me-1"></i>Deploy
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    updateJobStats() {
        const queued = this.trainingJobs.filter(j => j.status === 'queued').length;
        const training = this.trainingJobs.filter(j => j.status === 'training').length;
        const completed = this.trainingJobs.filter(j => j.status === 'completed').length;
        const failed = this.trainingJobs.filter(j => j.status === 'failed').length;

        document.getElementById('jobs-queued').textContent = queued;
        document.getElementById('jobs-training').textContent = training;
        document.getElementById('jobs-completed').textContent = completed;
        document.getElementById('jobs-failed').textContent = failed;
    }

    async startTraining(formData) {
        try {
            const response = await fetch('/api/v1/cv/train', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData
            });

            if (!response.ok) throw new Error('Failed to start training');

            alert('Training job started successfully!');
            bootstrap.Modal.getInstance(document.getElementById('newTrainingModal')).hide();
            this.loadTrainingJobs();

        } catch (error) {
            console.error('Error starting training:', error);
            alert('Failed to start training');
        }
    }

    async cancelJob(jobId) {
        if (!confirm('Are you sure you want to cancel this training job?')) return;

        try {
            const response = await fetch(`/api/v1/cv/training-jobs/${jobId}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) throw new Error('Failed to cancel job');

            alert('Training job cancelled');
            this.loadTrainingJobs();

        } catch (error) {
            console.error('Error cancelling job:', error);
            alert('Failed to cancel job');
        }
    }

    viewJob(jobId) {
        // TODO: Implement job detail modal
        console.log('View job:', jobId);
    }

    startAutoRefresh() {
        // Refresh every 10 seconds for training jobs
        setInterval(() => {
            this.loadTrainingJobs();
        }, 10000);
    }

    getProgressClass(status) {
        if (status === 'completed') return 'bg-success';
        if (status === 'failed') return 'bg-danger';
        if (status === 'training') return 'bg-primary';
        return 'bg-warning';
    }

    getStatusBadge(status) {
        if (status === 'completed') return 'bg-success';
        if (status === 'failed') return 'bg-danger';
        if (status === 'training') return 'bg-primary';
        return 'bg-warning';
    }

    getStatusIcon(status) {
        if (status === 'completed') return 'tabler-check';
        if (status === 'failed') return 'tabler-x';
        if (status === 'training') return 'tabler-loader';
        return 'tabler-clock';
    }

    formatFileSize(bytes) {
        if (!bytes) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('id-ID', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showError(message) {
        console.error(message);
    }
}

const cvServerManagement = new CVServerManagement();
