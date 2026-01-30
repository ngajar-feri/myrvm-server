@extends('layouts.app')

@section('title', 'API Documentation')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card" style="height: 85vh;">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <h5 class="card-title mb-0">API Documentation (Swagger UI)</h5>
                <a href="{{ url('/api/documentation') }}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="icon-base ti tabler-external-link me-1"></i> Open in New Tab
                </a>
            </div>
            <div class="card-body p-0">
                <iframe src="{{ url('/api/documentation') }}" 
                        style="width: 100%; height: 100%; border: none;"
                        title="API Documentation">
                </iframe>
            </div>
        </div>
    </div>
</div>
@endsection
