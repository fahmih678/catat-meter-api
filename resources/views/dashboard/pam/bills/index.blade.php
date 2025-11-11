@extends('layouts.pam')

@section('title', 'Tagihan - ' . $pam->name)

@section('content')
    <div class="container-fluid">
        <!-- Breadcrumb -->
    @section('breadcrumb')
        <li class="breadcrumb-item">
            <a href="{{ route('pam.show', $pam->id) }}" class="text-decoration-none">
                <i class="bi bi-building me-1"></i>{{ $pam->name }}
            </a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">
            <i class="bi bi-receipt me-1"></i>Tagihan
        </li>
    @endsection

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1">
                            <i class="bi bi-receipt text-primary me-2"></i>
                            Daftar Tagihan
                        </h3>
                        <p class="text-muted mb-0">PAM: {{ $pam->name }} ({{ $pam->code }})</p>
                    </div>
                    <div class="btn-group">
                        <a href="{{ route('pam.show', $pam->id) }}" class="btn btn-primary">
                            <i class="bi bi-building me-1"></i>Detail PAM
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Month Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar3 me-2"></i>Filter Bulan
                    </h5>
                </div>

                <!-- Month Tabs -->
                <ul class="nav nav-pills month-tabs" role="tablist">
                    @foreach ($paidMonths as $key => $month)
                        <li class="nav-item" role="presentation">
                            <a href="{{ route('pam.bills.index', ['pamId' => $pam->id, 'month' => $key]) }}"
                                class="nav-link {{ $selectedMonth == $key ? 'active' : '' }}" role="tab">
                                {{ $month }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    <!-- Bills Table -->
    <div class="row">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="col-md-4">
                        <h5 class="mb-0">
                            <i class="bi bi-list-ul me-2"></i>Data Tagihan
                        </h5>
                    </div>
                    <div class="col-md-8">
                        <form method="GET" action="{{ route('pam.bills.index', $pam->id) }}">
                            <!-- Hidden month input to maintain current month -->
                            <input type="hidden" name="month" value="{{ $selectedMonth }}">

                            <div class="d-flex gap-2 align-items-end">
                                <!-- Search -->
                                <div class="flex-grow-1">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-search"></i>
                                        </span>
                                        <input type="text" class="form-control" id="search_input" name="search"
                                            placeholder="Cari nomor tagihan, referensi, nama pelanggan, atau nomor pelanggan..."
                                            value="{{ request('search') ?? ($search ?? '') }}">
                                    </div>
                                </div>
                                <!-- User Filter -->
                                <div class="flex-grow-1">
                                    <select class="form-select" id="user_filter" name="user">
                                        <option value="">Semua Pengguna</option>
                                        @foreach ($paidUsers as $user)
                                            <option value="{{ $user->id }}"
                                                {{ request('user') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-funnel me-1"></i>Terapkan Filter
                                </button>
                                @if (request('user') || $search)
                                    <a href="{{ route('pam.bills.index', ['pamId' => $pam->id, 'month' => $selectedMonth]) }}"
                                        class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle me-1"></i>Reset
                                    </a>
                                @endif
                                <!-- Download Report Button -->
                                <a href="{{ route('pam.bills.download.payment-report', ['pamId' => $pam->id, 'month' => $selectedMonth, 'user' => request('user')]) }}"
                                    class="btn btn-success"
                                    title="Download Laporan Pembayaran">
                                    <i class="bi bi-file-earmark-pdf me-1"></i>Download
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                @if ($bills->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">No.</th>
                                    <th>No. Tagihan</th>
                                    <th>No. Referensi</th>
                                    <th>Pelanggan</th>
                                    <th>Pemakaian (m³)</th>
                                    <th>Total Tagihan</th>
                                    <th>Status</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Dibayar</th>
                                    <th>Diterima Oleh</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bills as $index => $bill)
                                    <tr>
                                        <td>{{ $bills->firstItem() + $index }}
                                        <td>
                                            <span class="badge bg-primary">{{ $bill->bill_number }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $bill->reference_number ?? '-' }}</span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ $bill->customer->name ?? '-' }}</strong>
                                            </div>
                                            <small
                                                class="text-muted">{{ $bill->customer->customer_number ?? '-' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <i class="bi bi-droplet me-1"></i>
                                                {{ number_format($bill->volume_usage, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-dark">
                                                <i class="bi bi-cash-stack me-1"></i>
                                                Rp {{ number_format($bill->total_bill, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge {{ $bill->status === 'paid' ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $bill->status === 'paid' ? 'Lunas' : 'Menunggu' }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $bill->due_date->format('d M Y') }}</small>
                                        </td>
                                        <td>
                                            @if ($bill->paid_at)
                                                <div>
                                                    <small class="text-success d-block">
                                                        <i class="bi bi-check-circle me-1"></i>
                                                        {{ $bill->paid_at->format('d M Y H:i') }}
                                                    </small>
                                                    <small class="text-muted d-block">
                                                        {{ $bill->payment_method ?? '-' }}
                                                    </small>
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($bill->paidBy)
                                                <div>
                                                    <small class="d-block">{{ $bill->paidBy->name }}</small>
                                                    <small class="text-muted">{{ $bill->paidBy->email }}</small>
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group-sm">
                                                @if ($bill->status === 'paid')
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        onclick="deleteBill({{ $bill->id }}, '{{ $bill->bill_number }}', '{{ $bill->customer->name ?? 'Pelanggan' }}')"
                                                        title="Hapus Tagihan">
                                                        <i class="bi bi-window-x"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            <small>Halaman {{ $bills->currentPage() }} dari {{ $bills->lastPage() }}</small>
                        </div>
                        {{ $bills->links('vendor.pagination.bootstrap-5') }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="text-muted">
                            <i class="bi bi-receipt fs-1 d-block mb-3"></i>
                            <h5>Belum Ada Data Tagihan</h5>
                            <p>Belum ada data tagihan yang tersedia untuk filter yang dipilih.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .badge {
        font-size: 0.8em;
    }

    .table th {
        font-weight: 600;
        font-size: 0.9rem;
    }

    .table td {
        vertical-align: middle;
    }

    /* Month Tabs Styling */
    .month-tabs {
        border-bottom: 2px solid #dee2e6;
        margin-bottom: 0;
        flex-wrap: wrap;
    }

    .month-tabs .nav-item {
        margin-bottom: -2px;
    }

    .month-tabs .nav-link {
        border: none;
        border-radius: 8px 8px 0 0;
        margin-right: 4px;
        color: #6c757d;
        background-color: transparent;
        padding: 8px 16px;
        font-weight: 500;
        transition: all 0.3s ease;
        border-bottom: 2px solid transparent;
    }

    .month-tabs .nav-link:hover {
        background-color: #f8f9fa;
        color: #495057;
        border-bottom: 2px solid #e9ecef;
    }

    .month-tabs .nav-link.active {
        background-color: #0d6efd;
        color: white;
        border-bottom: 2px solid #0d6efd;
        box-shadow: 0 -2px 4px rgba(13, 110, 253, 0.25);
    }

    .month-tabs .nav-link:focus {
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .month-tabs .nav-link {
            padding: 6px 12px;
            font-size: 0.9rem;
        }
    }

    /* Loading spinner animation */
    .spin {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function deleteBill(billId, billNumber, customerName) {
        // Set modal data
        document.getElementById('deleteBillId').value = billId;
        document.getElementById('deleteBillNumber').textContent = billNumber;
        document.getElementById('deleteCustomerName').textContent = customerName;

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('deleteBillModal'));
        modal.show();
    }

    function confirmDeleteBill() {
        const billId = document.getElementById('deleteBillId').value;

        // Show loading state
        const button = document.getElementById('confirmDeleteBillBtn');
        const originalHtml = button.innerHTML;
        button.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Menghapus...';
        button.disabled = true;

        // Send request to delete bill
        fetch(`{{ url('/pam/' . $pam->id . '/bills') }}/${billId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Hide modal
                    bootstrap.Modal.getInstance(document.getElementById('deleteBillModal')).hide();

                    // Show success modal
                    showSuccessModal('Tagihan Dihapus!', data.message);
                } else {
                    // Show error modal
                    showErrorModal('Gagal Menghapus Tagihan', data.message || 'Terjadi kesalahan');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorModal('Gagal Menghapus Tagihan', 'Terjadi kesalahan jaringan.');
            })
            .finally(() => {
                // Restore button state
                button.innerHTML = originalHtml;
                button.disabled = false;
            });
    }

    function showSuccessModal(title, message) {
        document.getElementById('successModalTitle').textContent = title;
        document.getElementById('successModalMessage').textContent = message;
        const modal = new bootstrap.Modal(document.getElementById('successModal'));
        modal.show();

        // Auto hide and reload after 2 seconds
        setTimeout(() => {
            bootstrap.Modal.getInstance(document.getElementById('successModal')).hide();
            window.location.reload();
        }, 2000);
    }

    function showErrorModal(title, message) {
        document.getElementById('errorModalTitle').textContent = title;
        document.getElementById('errorModalMessage').textContent = message;
        const modal = new bootstrap.Modal(document.getElementById('errorModal'));
        modal.show();
    }
</script>
@endpush

<!-- Delete Bill Confirmation Modal -->
<div class="modal fade" id="deleteBillModal" tabindex="-1" aria-labelledby="deleteBillModalLabel"
aria-hidden="true">
<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header bg-danger text-white">
            <h5 class="modal-title" id="deleteBillModalLabel">
                <i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Hapus Tagihan
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><strong>PERINGATAN:</strong> Anda akan menghapus tagihan ini secara permanen!</div>
            </div>

            <div class="mb-3">
                <strong>Informasi Tagihan:</strong>
                <ul class="mb-0 mt-2">
                    <li><strong>No. Tagihan:</strong> <span id="deleteBillNumber" class="text-danger"></span></li>
                    <li><strong>Pelanggan:</strong> <span id="deleteCustomerName" class="text-danger"></span></li>
                </ul>
            </div>

            <div class="mb-3">
                <strong>Tindakan ini akan:</strong>
                <ul class="mb-0 mt-2">
                    <li><i class="bi bi-trash text-danger me-1"></i> <strong>MENGHAPUS PERMANEN</strong> tagihan
                        ini</li>
                    <li><i class="bi bi-file-earmark text-warning me-1"></i> Mengubah status pembacaan meter
                        menjadi "Draft"</li>
                    <li><i class="bi bi-x-circle text-danger me-1"></i> Semua data pembayaran akan hilang</li>
                </ul>
            </div>

            <div class="alert alert-warning d-flex align-items-center" role="alert">
                <i class="bi bi-shield-exclamation me-2"></i>
                <div><strong>Tindakan tidak dapat dibatalkan!</strong> Pastikan Anda yakin ingin melanjutkan.</div>
            </div>

            <input type="hidden" id="deleteBillId" value="">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                <i class="bi bi-x-circle me-1"></i>Batal
            </button>
            <button type="button" class="btn btn-danger" id="confirmDeleteBillBtn"
                onclick="confirmDeleteBill()">
                <i class="bi bi-trash me-1"></i>Ya, Hapus Tagihan
            </button>
        </div>
    </div>
</div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
<div class="modal-dialog modal-sm">
    <div class="modal-content">
        <div class="modal-header bg-success text-white">
            <h5 class="modal-title" id="successModalLabel">
                <i class="bi bi-check-circle me-2"></i><span id="successModalTitle">Berhasil!</span>
            </h5>
        </div>
        <div class="modal-body text-center">
            <div class="mb-3">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
            </div>
            <p id="successModalMessage" class="mb-0">Operasi berhasil dilakukan.</p>
        </div>
    </div>
</div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
<div class="modal-dialog modal-sm">
    <div class="modal-content">
        <div class="modal-header bg-danger text-white">
            <h5 class="modal-title" id="errorModalLabel">
                <i class="bi bi-x-circle me-2"></i><span id="errorModalTitle">Error!</span>
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
            <div class="mb-3">
                <i class="bi bi-x-circle-fill text-danger" style="font-size: 3rem;"></i>
            </div>
            <p id="errorModalMessage" class="mb-0">Terjadi kesalahan saat melakukan operasi.</p>
            <button type="button" class="btn btn-secondary mt-3" data-bs-dismiss="modal">Tutup</button>
        </div>
    </div>
</div>
</div>
