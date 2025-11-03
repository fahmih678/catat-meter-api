@php
    use Illuminate\Support\Str;
@endphp

@extends('layouts.main')

@section('title', 'Pelanggan - ' . ($pam->name ?? 'PAM Not Found'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('pam.index') }}">PAM Management</a></li>
    <li class="breadcrumb-item"><a href="{{ route('pam.show', $pam->id) }}">{{ $pam->name }}</a></li>
    <li class="breadcrumb-item active">Pelanggan</li>
@endsection

@section('content')
    <div class="container-fluid p-0">
        <!-- PAM Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="d-flex align-items-center mb-2">
                                <div
                                    class="avatar avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="bi bi-building"></i>
                                </div>
                                <div>
                                    <h2 class="mb-1">{{ $pam->name }}</h2>
                                    <p class="text-muted mb-0">{{ $pam->code }} | {{ $pam->address }}</p>
                                    <p class="text-muted mb-0 small">
                                        <i class="bi bi-people me-1"></i>Manajemen Pelanggan
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('pam.show', $pam->id) }}" class="btn btn-primary me-2">
                                <i class="bi bi-building me-2"></i>Detail PAM
                            </a>
                            <button class="btn btn-success" onclick="showAddCustomerModal()">
                                <i class="bi bi-plus-circle me-2"></i>Tambah Pelanggan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <form method="GET" action="{{ route('pam.customers', $pam->id) }}" id="filterForm">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label fw-semibold">
                                    <i class="bi bi-search me-1"></i>Cari Pelanggan
                                </label>
                                <input type="text" class="form-control" id="search" name="search"
                                    value="{{ $search }}"
                                    placeholder="Nama, Nomor Pelanggan, Telepon, atau Alamat...">
                            </div>
                            <div class="col-md-3">
                                <label for="area_id" class="form-label fw-semibold">
                                    <i class="bi bi-geo-alt me-1"></i>Filter Area
                                </label>
                                <select class="form-select" id="area_id" name="area_id">
                                    <option value="">Semua Area</option>
                                    @foreach ($areas as $area)
                                        <option value="{{ $area->id }}" {{ $areaId == $area->id ? 'selected' : '' }}>
                                            {{ $area->name }} ({{ $area->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label fw-semibold">
                                    <i class="bi bi-toggle-on me-1"></i>Status
                                </label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="active" {{ $status == 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="inactive" {{ $status == 'inactive' ? 'selected' : '' }}>Tidak Aktif
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="per_page" class="form-label fw-semibold">
                                    <i class="bi bi-list me-1"></i>Tampilkan
                                </label>
                                <select class="form-select" id="per_page" name="per_page">
                                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label d-block">&nbsp;</label>
                                <div class="btn-group w-100">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-funnel"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="bi bi-people me-2"></i>Daftar Pelanggan
                            @if ($search || $areaId || $status)
                                <small class="text-muted">({{ $customers->count() }} dari {{ $customers->total() }}
                                    hasil)</small>
                            @else
                                <small class="text-muted">({{ $customers->total() }} pelanggan)</small>
                            @endif
                        </h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-success" onclick="exportCustomers()">
                                <i class="bi bi-download me-1"></i>Export
                            </button>
                            <button class="btn btn-outline-primary" onclick="refreshTable()">
                                <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="customersTable">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>No. Pelanggan</th>
                                    <th>Nama</th>
                                    <th>Area</th>
                                    <th>Grup Tarif</th>
                                    <th>Pengguna</th>
                                    <th>Kontak</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customers as $index => $customer)
                                    <tr>
                                        <td>
                                            <span class="badge text-dark">{{ $customers->firstItem() + $index }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-dark">{{ $customer->customer_number }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar bg-light text-dark rounded-circle me-2"
                                                    style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-person fs-6"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold">{{ $customer->name }}</div>
                                                    @if ($customer->address)
                                                        <small
                                                            class="text-muted d-block">{{ Str::limit($customer->address, 50) }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($customer->area)
                                                <span class="badge bg-secondary">{{ $customer->area->name }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($customer->tariffGroup)
                                                <span
                                                    class="badge bg-warning text-dark">{{ $customer->tariffGroup->name }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($customer->user)
                                                <span class="badge bg-success">{{ $customer->user->name }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div>
                                                @if ($customer->phone)
                                                    <small class="d-block">{{ $customer->phone }}</small>
                                                @else
                                                    <small class="text-muted">-</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if ($customer->is_active)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-warning">Tidak Aktif</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary"
                                                    onclick="viewCustomer({{ $customer->id }})" title="Lihat Detail">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-warning"
                                                    onclick="editCustomer({{ $customer->id }})" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-outline-info"
                                                    onclick="viewMeterReadings({{ $customer->id }})"
                                                    title="Lihat Bacaan Meter">
                                                    <i class="bi bi-speedometer2"></i>
                                                </button>
                                                <button class="btn btn-outline-danger"
                                                    onclick="deleteCustomer({{ $customer->id }}, '{{ $customer->name }}')"
                                                    title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bi bi-people fs-1 d-block mb-2"></i>
                                                @if ($search || $areaId || $status)
                                                    Tidak ada pelanggan yang cocok dengan filter yang dipilih
                                                @else
                                                    Belum ada pelanggan untuk PAM ini
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if ($customers->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted">
                                Menampilkan {{ $customers->firstItem() }} hingga {{ $customers->lastItem() }} dari
                                {{ $customers->total() }} pelanggan
                            </div>
                            {{ $customers->links('pagination::bootstrap-4') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal (Placeholder) -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addCustomerModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Pelanggan Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Fitur tambah pelanggan akan segera tersedia.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .stat-card-primary,
        .stat-card-success,
        .stat-card-warning,
        .stat-card-info {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card-primary:hover,
        .stat-card-success:hover,
        .stat-card-warning:hover,
        .stat-card-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
            background-color: #f8f9fa;
        }

        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .avatar {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-submit form when filters change
            const filterInputs = ['search', 'area_id', 'status', 'per_page'];
            filterInputs.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.addEventListener('change', function() {
                        if (id !== 'search') {
                            document.getElementById('filterForm').submit();
                        }
                    });
                }
            });

            // Search on Enter key
            const searchInput = document.getElementById('search');
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        document.getElementById('filterForm').submit();
                    }, 500);
                });
            }
        });

        function resetFilters() {
            const form = document.getElementById('filterForm');
            form.querySelectorAll('input, select').forEach(element => {
                element.value = '';
            });
            form.submit();
        }

        function refreshTable() {
            window.location.reload();
        }

        function exportCustomers() {
            const url = new URL(window.location);
            url.searchParams.set('export', 'excel');
            window.open(url.toString(), '_blank');
        }

        function showAddCustomerModal() {
            const modal = new bootstrap.Modal(document.getElementById('addCustomerModal'));
            modal.show();
        }

        function viewCustomer(id) {
            showNotification('Lihat detail pelanggan #' + id + ' - Fitur coming soon', 'info');
        }

        function editCustomer(id) {
            showNotification('Edit pelanggan #' + id + ' - Fitur coming soon', 'info');
        }

        function viewMeterReadings(id) {
            showNotification('Lihat bacaan meter pelanggan #' + id + ' - Fitur coming soon', 'info');
        }

        function deleteCustomer(id, name) {
            if (confirm(`Apakah Anda yakin ingin menghapus pelanggan "${name}"?`)) {
                showNotification('Hapus pelanggan #' + id + ' - Fitur coming soon', 'warning');
            }
        }

        function showNotification(message, type = 'info') {
            const toastHtml = `
                <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;

            const container = document.createElement('div');
            container.innerHTML = toastHtml;
            container.style.position = 'fixed';
            container.style.top = '20px';
            container.style.right = '20px';
            container.style.zIndex = '9999';

            document.body.appendChild(container);

            const toast = new bootstrap.Toast(container.querySelector('.toast'));
            toast.show();

            setTimeout(() => {
                container.remove();
            }, 3000);
        }
    </script>
@endpush
