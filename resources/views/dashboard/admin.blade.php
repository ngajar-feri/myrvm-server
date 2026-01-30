@extends('layouts.app')

@section('title', 'Dashboard Monitoring Real-time')

@section('content')
<div class="row">
    <!-- Website Analytics -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
             <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Total Sampah Terkumpul</h5>
                <small class="text-muted">Bulan Ini</small>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex flex-column align-items-center gap-1">
                        <h2 class="mb-2">4,200 kg</h2>
                        <span>Total Berat</span>
                    </div>
                    <div id="wasteStatisticsChart"></div>
                </div>
                <ul class="p-0 m-0">
                    <li class="d-flex mb-4 pb-1">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-primary"><i class="icon-base ti tabler-bottle"></i></span>
                        </div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                                <h6 class="mb-0">Plastik (PET)</h6>
                                <small class="text-muted">Botol minuman</small>
                            </div>
                            <div class="user-progress">
                                <small class="fw-semibold">2,500 kg</small>
                            </div>
                        </div>
                    </li>
                    <li class="d-flex mb-4 pb-1">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-info"><i class="icon-base ti tabler-cup"></i></span>
                        </div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                                <h6 class="mb-0">Kaleng (Aluminium)</h6>
                                <small class="text-muted">Minuman kaleng</small>
                            </div>
                            <div class="user-progress">
                                <small class="fw-semibold">1,200 kg</small>
                            </div>
                        </div>
                    </li>
                    <li class="d-flex">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-secondary"><i class="icon-base ti tabler-glass"></i></span>
                        </div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                                <h6 class="mb-0">Kaca</h6>
                                <small class="text-muted">Botol kaca</small>
                            </div>
                            <div class="user-progress">
                                <small class="fw-semibold">500 kg</small>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <!--/ Website Analytics -->

    <!-- Statistics -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Statistik Hari Ini</h5>
                <small class="text-muted">Update Real-time: {{ date('H:i') }} WIB</small>
            </div>
            <div class="card-body">
                <div class="row gy-3">
                    <div class="col-md-4 col-6">
                        <div class="d-flex align-items-center">
                            <div class="badge rounded-pill bg-label-primary me-3 p-2">
                                <i class="icon-base ti tabler-chart-pie-2 icon-lg"></i>
                            </div>
                            <div class="card-info">
                                <h5 class="mb-0">230</h5>
                                <small>Transaksi</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-6">
                        <div class="d-flex align-items-center">
                            <div class="badge rounded-pill bg-label-info me-3 p-2">
                                <i class="icon-base ti tabler-users icon-lg"></i>
                            </div>
                            <div class="card-info">
                                <h5 class="mb-0">15</h5>
                                <small>User Baru</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-6">
                        <div class="d-flex align-items-center">
                            <div class="badge rounded-pill bg-label-danger me-3 p-2">
                                <i class="icon-base ti tabler-alert-circle icon-lg"></i>
                            </div>
                            <div class="card-info">
                                <h5 class="mb-0">3</h5>
                                <small>Alert Mesin</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-4">
                         <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <span class="alert-icon text-warning me-2">
                                <i class="icon-base ti tabler-bell"></i>
                            </span>
                            RVM-JKT-002 di Stasiun Gambir hampir penuh (90%).
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--/ Statistics -->

    <!-- RVM Status -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5 class="card-title mb-0">Status Operasional Mesin (RVM)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Lokasi</th>
                                <th>Serial Number</th>
                                <th>Status</th>
                                <th>Kapasitas</th>
                                <th>Last Ping</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Mall Grand Indonesia</td>
                                <td>RVM-JKT-001</td>
                                <td><span class="badge bg-label-success">Online</span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress w-100 me-3" style="height: 8px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 45%" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span>45%</span>
                                    </div>
                                </td>
                                <td>2 mins ago</td>
                            </tr>
                            <tr>
                                <td>Stasiun Gambir</td>
                                <td>RVM-JKT-002</td>
                                <td><span class="badge bg-label-warning">Full Warning</span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress w-100 me-3" style="height: 8px;">
                                            <div class="progress-bar bg-danger" role="progressbar" style="width: 90%" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span>90%</span>
                                    </div>
                                </td>
                                <td>1 hour ago</td>
                            </tr>
                            <tr>
                                <td>Kantor Pusat MyRVM</td>
                                <td>RVM-JKT-003</td>
                                <td><span class="badge bg-label-success">Online</span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress w-100 me-3" style="height: 8px;">
                                            <div class="progress-bar bg-info" role="progressbar" style="width: 15%" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span>15%</span>
                                    </div>
                                </td>
                                <td>5 mins ago</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!--/ RVM Status -->
    
    <!-- Map Placeholder -->
     <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Peta Sebaran RVM</h5>
            </div>
            <div class="card-body">
                <div class="rounded-3" style="height: 400px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; border: 2px dashed #d9dee3;">
                    <div class="text-center">
                         <i class="icon-base ti tabler-map-2 icon-xl text-muted mb-2"></i>
                        <h6 class="text-muted">Peta Interaktif Lokasi RVM</h6>
                        <small>Menampilkan status clustering dan heat map area</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
