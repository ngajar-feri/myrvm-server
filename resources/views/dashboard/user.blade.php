@extends('layouts.app')

@section('title', 'Dashboard Saya - MyRVM')

@section('content')
<div class="row">
    <!-- Welcome Card -->
    <div class="col-12 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h4 class="text-white">Halo, {{ auth()->user()->name ?? 'User' }}! 👋</h4>
                <p class="mb-0">Terima kasih telah berkontribusi menyelamatkan lingkungan. Berikut statistik daur ulang Anda.</p>
            </div>
        </div>
    </div>

    <!-- Personal Stats -->
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="avatar avatar-xl mx-auto mb-3">
                    <span class="avatar-initial rounded-circle bg-label-success"><i class="icon-base ti tabler-leaf icon-lg"></i></span>
                </div>
                <h3 class="mb-1">1,250</h3>
                <p class="text-muted">Poin Terkumpul</p>
                <button class="btn btn-primary w-100">Tukar Poin</button>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="avatar avatar-xl mx-auto mb-3">
                    <span class="avatar-initial rounded-circle bg-label-info"><i class="icon-base ti tabler-bottle icon-lg"></i></span>
                </div>
                <h3 class="mb-1">45</h3>
                <p class="text-muted">Botol Didaur Ulang</p>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Riwayat Transaksi Terakhir</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Setor Botol Plastik (5 pcs)</h6>
                            <small class="text-muted">Hari ini, 10:30 @ Grand Indonesia</small>
                        </div>
                        <span class="badge bg-success">+50 Poin</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Setor Kaleng (2 pcs)</h6>
                            <small class="text-muted">Kemarin, 14:15 @ Stasiun Gambir</small>
                        </div>
                        <span class="badge bg-success">+30 Poin</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
