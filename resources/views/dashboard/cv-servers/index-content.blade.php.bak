<div class="row">
    <div class="col-12">
        <!-- Training Jobs Monitor -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ti tabler-server me-2"></i>Training Jobs Monitor
                </h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTrainingModal">
                    <i class="ti tabler-plus me-1"></i>New Training
                </button>
            </div>

            <div class="card-body">
                <!-- Stats -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="card stats-card stats-card-warning mb-0">
                            <div class="card-body text-center">
                                <div class="avatar mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-label-warning">
                                        <i class="ti tabler-clock"></i>
                                    </span>
                                </div>
                                <h5 class="mb-0" id="jobs-queued">0</h5>
                                <small>Queued</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card stats-card stats-card-primary mb-0">
                            <div class="card-body text-center">
                                <div class="avatar mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-label-primary">
                                        <i class="ti tabler-loader"></i>
                                    </span>
                                </div>
                                <h5 class="mb-0" id="jobs-training">0</h5>
                                <small>Training</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card stats-card stats-card-success mb-0">
                            <div class="card-body text-center">
                                <div class="avatar mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-label-success">
                                        <i class="ti tabler-check"></i>
                                    </span>
                                </div>
                                <h5 class="mb-0" id="jobs-completed">0</h5>
                                <small>Completed</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card stats-card stats-card-danger mb-0">
                            <div class="card-body text-center">
                                <div class="avatar mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-label-danger">
                                        <i class="ti tabler-x"></i>
                                    </span>
                                </div>
                                <h5 class="mb-0" id="jobs-failed">0</h5>
                                <small>Failed</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Training Jobs Table -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Job ID</th>
                                <th>Model</th>
                                <th>Dataset</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th>Metrics</th>
                                <th>Started</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="training-jobs-table">
                            <!-- Loading -->
                            <tr>
                                <td colspan="8">
                                    <div class="skeleton skeleton-card"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Model Repository -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="ti tabler-database me-2"></i>Model Repository
                </h5>
            </div>

            <div class="card-body">
                <div class="row g-3" id="models-grid">
                    <!-- Loading skeleton -->
                    <div class="col-md-6">
                        <div class="skeleton skeleton-card"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="skeleton skeleton-card"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Training Detail Modal -->
<div class="modal fade" id="trainingDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Training Job Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="training-detail-content">
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Training Modal -->
<div class="modal fade" id="newTrainingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Start New Training</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="new-training-form">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Model Name</label>
                        <input type="text" name="model_name" class="form-control" placeholder="YOLO11-v4.0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dataset</label>
                        <select name="dataset_id" class="form-select" required>
                            <option value="">Select dataset...</option>
                            <!-- Dynamic options -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Epochs</label>
                        <input type="number" name="epochs" class="form-control" value="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Batch Size</label>
                        <input type="number" name="batch_size" class="form-control" value="16" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Start Training</button>
                </div>
            </form>
        </div>
    </div>
</div>