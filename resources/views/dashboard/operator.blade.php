@extends('layouts.app')

@section('title', 'Dashboard Operator - Monitoring Mesin')

@section('content')
<div class="row">
    <!-- RVM Status (Prioritas Utama untuk Operator) -->
    <div class="col-12 mb-4">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white d-flex justify-content-between">
                <h5 class="card-title mb-0 text-white">Status Operasional Mesin (RVM)</h5>
                <span class="badge bg-white text-primary">Live Monitoring</span>
            </div>
            <div class="card-body mt-3">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Lokasi</th>
                                <th>Serial Number</th>
                                <th>Status</th>
                                <th>Kapasitas</th>
                                <th>Last Ping</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Stasiun Gambir</td>
                                <td>RVM-JKT-002</td>
                                <td><span class="badge bg-label-warning">Full Warning</span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress w-100 me-3" style="height: 15px;">
                                            <div class="progress-bar bg-danger" role="progressbar" style="width: 90%" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span class="fw-bold text-danger">90%</span>
                                    </div>
                                </td>
                                <td>1 hour ago</td>
                                <td><button class="btn btn-sm btn-danger">Dispatch Pickup</button></td>
                            </tr>
                            <tr>
                                <td>Mall Grand Indonesia</td>
                                <td>RVM-JKT-001</td>
                                <td><span class="badge bg-label-success">Online</span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress w-100 me-3" style="height: 15px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 45%" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span>45%</span>
                                    </div>
                                </td>
                                <td>2 mins ago</td>
                                <td><button class="btn btn-sm btn-secondary">Details</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Alerts -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
             <div class="card-header">
                <h5 class="card-title mb-0">System Alerts</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <span class="alert-icon text-warning me-2">
                        <i class="icon-base ti tabler-bell"></i>
                    </span>
                    RVM-JKT-002: Kapasitas mencapai 90%. Segera jadwalkan pengosongan.
                </div>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <span class="alert-icon text-danger me-2">
                        <i class="icon-base ti tabler-wifi-off"></i>
                    </span>
                    RVM-SBY-001: Lost connection sejak 2 jam lalu.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
