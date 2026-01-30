@extends('layouts.app')

@section('title', 'Dashboard Tenant - Kelola Voucher')

@section('content')
<div class="row">
    <!-- Header -->
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold py-3 mb-0">Manajemen Voucher & Promosi</h4>
                <p class="text-muted">Kelola voucher yang akan tampil di aplikasi pengguna.</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createVoucherModal">
                <i class="icon-base ti tabler-plus me-1"></i> Buat Voucher Baru
            </button>
        </div>
    </div>

    <!-- Alert Success -->
    @if(session('success'))
    <div class="col-12 mb-4">
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    @endif

    <!-- Voucher List -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Daftar Voucher Aktif</h5>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Judul Voucher</th>
                            <th>Kode</th>
                            <th>Poin Required</th>
                            <th>Berlaku Hingga</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(auth()->user()->vouchers as $voucher)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-2">
                                        <span class="avatar-initial rounded bg-label-primary"><i class="icon-base ti tabler-ticket"></i></span>
                                    </div>
                                    <div>
                                        <strong>{{ $voucher->title }}</strong><br>
                                        <small class="text-muted">{{ \Illuminate\Support\Str::limit($voucher->description, 30) }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><code>{{ $voucher->code ?? '-' }}</code></td>
                            <td>{{ $voucher->points_required }} Poin</td>
                            <td>{{ $voucher->valid_until?->format('d M Y') ?? '-' }}</td>
                            <td>
                                @if($voucher->status == 'active')
                                    <span class="badge bg-label-success">Active</span>
                                @else
                                    <span class="badge bg-label-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#editVoucherModal{{ $voucher->id }}">
                                            <i class="icon-base ti tabler-pencil me-1"></i> Edit
                                        </a>
                                        <form action="{{ route('vouchers.destroy', $voucher->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus voucher ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="icon-base ti tabler-trash me-1"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editVoucherModal{{ $voucher->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Voucher</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form action="{{ route('vouchers.update', $voucher->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Judul Voucher</label>
                                                <input type="text" name="title" class="form-control" value="{{ $voucher->title }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Deskripsi</label>
                                                <textarea name="description" class="form-control" rows="3">{{ $voucher->description }}</textarea>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Poin Dibutuhkan</label>
                                                    <input type="number" name="points_required" class="form-control" value="{{ $voucher->points_required }}" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Berlaku Hingga</label>
                                                    <input type="date" name="valid_until" class="form-control" value="{{ $voucher->valid_until?->format('Y-m-d') ?? '' }}" required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-select">
                                                    <option value="active" {{ $voucher->status == 'active' ? 'selected' : '' }}>Active</option>
                                                    <option value="inactive" {{ $voucher->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="text-muted">Belum ada voucher yang dibuat. Silakan buat voucher pertama Anda!</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createVoucherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buat Voucher Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('vouchers.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Judul Voucher</label>
                        <input type="text" name="title" class="form-control" placeholder="Contoh: Diskon 50% Kopi Kenangan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Syarat dan ketentuan voucher..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kode Voucher (Opsional)</label>
                        <input type="text" name="code" class="form-control" placeholder="Contoh: KOPI50">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Poin Dibutuhkan</label>
                            <input type="number" name="points_required" class="form-control" value="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Berlaku Hingga</label>
                            <input type="date" name="valid_until" class="form-control" required>
                        </div>
                    </div>
                    <input type="hidden" name="status" value="active">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Buat Voucher</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
