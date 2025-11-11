@extends('layouts.pam')

@section('title', 'Detail Pembacaan Meter - ' . ($month ?? '') . ' - ' . $pam->name)

@section('content')
    <div class="container-fluid">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}" class="text-decoration-none">
                        <i class="bi bi-house me-1"></i>Dashboard
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('pam.show', $pam->id) }}" class="text-decoration-none">
                        <i class="bi bi-building me-1"></i>{{ $pam->name }}
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('pam.meter-readings.index', $pam->id) }}" class="text-decoration-none">
                        <i class="bi bi-speedometer2 me-1"></i>Pembacaan Meter
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="bi bi-calendar3 me-1"></i>{{ $month ?? '' }}
                </li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1">
                                <i class="bi bi-calendar3 text-primary me-2"></i>
                                Detail Pembacaan Meter
                            </h3>
                            <p class="text-muted mb-0">
                                PAM: {{ $pam->name }} ({{ $pam->code }}) | Periode: {{ $month ?? '' }}
                            </p>
                        </div>
                        <div class="btn-group">
                            <a href="{{ route('pam.meter-readings.index', $pam->id) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Kembali
                            </a>
                            <a href="{{ route('pam.meter-readings.export', ['pamId' => $pam->id, 'month' => $month]) }}"
                                class="btn btn-success">
                                <i class="bi bi-download me-1"></i>Export Excel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Meter Readings Table -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="row align-items-start mb-4">
                        <div class="col-md-6">
                            <h5 class="">
                                <i class="bi bi-list-columns me-2"></i>Detail Pembacaan Meter
                            </h5>
                            @if ($search)
                                <span class="badge bg-primary ms-1">
                                    <i class="bi bi-search me-1"></i>"{{ $search }}"
                                </span>
                            @endif
                            @if ($selectedAreaId)
                                <span class="badge bg-secondary ms-1">
                                    Area: {{ $areas->where('id', $selectedAreaId)->first()->name ?? 'Unknown' }}
                                </span>
                            @endif
                            @if ($selectedStatus)
                                <span class="badge bg-secondary ms-1">
                                    Status:
                                    @if ($selectedStatus == 'paid')
                                        Dibayar
                                    @elseif ($selectedStatus == 'pending')
                                        Menunggu
                                    @elseif ($selectedStatus == 'draft')
                                        Draft
                                    @endif
                                </span>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <form method="GET"
                                action="{{ route('pam.meter-readings.month', ['pamId' => $pam->id, 'month' => $month]) }}"
                                class="d-flex gap-2 align-items-end">
                                <input type="hidden" name="month" value="{{ $month }}">
                                <div class="flex-grow-1">
                                    <input type="text" name="search" id="search" class="form-control"
                                        placeholder="No. meter, nama pelanggan..." value="{{ $search ?? '' }}">
                                </div>
                                <div class="flex-grow-1">
                                    <select name="area_id" id="area_id" class="form-select">
                                        <option value="">Semua Area</option>
                                        @foreach ($areas ?? [] as $area)
                                            <option value="{{ $area->id }}"
                                                {{ ($selectedAreaId ?? null) == $area->id ? 'selected' : '' }}>
                                                {{ $area->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex-grow-1">
                                    <select name="status" id="status" class="form-select">
                                        <option value="">Semua Status</option>
                                        <option value="paid" {{ ($selectedStatus ?? null) == 'paid' ? 'selected' : '' }}>
                                            Dibayar</option>
                                        <option value="pending"
                                            {{ ($selectedStatus ?? null) == 'pending' ? 'selected' : '' }}>Menunggu
                                        </option>
                                        <option value="draft"
                                            {{ ($selectedStatus ?? null) == 'draft' ? 'selected' : '' }}>Draft</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-funnel me-1"></i>Filter
                                </button>
                                @if ($selectedAreaId || $selectedStatus || $search)
                                    <a href="{{ route('pam.meter-readings.month', ['pamId' => $pam->id, 'month' => $month]) }}"
                                        class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle me-1"></i>Reset
                                    </a>
                                @endif
                            </form>
                        </div>
                    </div>

                    @if ($meterReadings->count() > 0)
                        <!-- Simple Table -->
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>No.</th>
                                        <th><i class="bi bi-calendar3 me-1"></i>Tanggal</th>
                                        <th><i class="bi bi-calendar3 me-1"></i>Area</th>
                                        <th><i class="bi bi-speedometer2 me-1"></i>No. Meter</th>
                                        <th><i class="bi bi-person me-1"></i>Pelanggan</th>
                                        <th><i class="bi bi-arrow-down-up me-1"></i>Awal</th>
                                        <th><i class="bi bi-arrow-up me-1"></i>Akhir</th>
                                        <th><i class="bi bi-droplet me-1"></i>Pemakaian (m³)</th>
                                        <th><i class="bi bi-person-check me-1"></i>Petugas</th>
                                        <th><i class="bi bi-shield-check me-1"></i>Status</th>
                                        <th><i class="bi bi-chat-left-text me-1"></i>Catatan</th>
                                        <th class="text-center"><i class="bi bi-gear me-1"></i>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($meterReadings as $index => $reading)
                                        <tr>
                                            <td>{{ $meterReadings->firstItem() + $index }}
                                            </td>
                                            <td>{{ $reading->reading_at }}
                                            </td>
                                            <td>
                                                @if ($reading->customer->area->name)
                                                    <span
                                                        class="badge bg-secondary">{{ $reading->customer->area->name }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>

                                            <td>{{ $reading->meter->meter_number ?? '-' }}</td>
                                            <td>{{ $reading->meter->customer->name ?? '-' }}</td>
                                            <td>{{ number_format($reading->previous_reading ?? 0, 1) }}</td>
                                            <td>{{ number_format($reading->current_reading ?? 0, 1) }}</td>
                                            <td>
                                                <span
                                                    class="badge bg-primary">{{ number_format($reading->volume_usage ?? 0, 1) }}</span>
                                            </td>
                                            <td>{{ $reading->readingBy->name ?? '-' }}</td>
                                            <td>
                                                @if ($reading->status === 'paid')
                                                    <span class="badge bg-success">Dibayar</span>
                                                @elseif ($reading->status === 'pending')
                                                    <span class="badge bg-warning text-dark">Menunggu</span>
                                                @else
                                                    <span class="badge bg-secondary">Draft</span>
                                                @endif
                                            </td>
                                            <td>{{ $reading->notes ? Str::limit($reading->notes, 30) : '-' }}</td>
                                            <td class="text-end">
                                                <div role="group">
                                                    @if ($reading->status === 'draft')
                                                        <button type="button" class="btn btn-sm btn-outline-warning"
                                                            onclick="publishMeterReading({{ $reading->id }}, '{{ $reading->meter->customer->name ?? 'Pelanggan' }}', '{{ $reading->meter->meter_number ?? '-' }}', {{ number_format($reading->volume_usage ?? 0, 1) }})"
                                                            title="Terbitkan">
                                                            <i class="bi bi-caret-up-square"></i>
                                                        </button>
                                                        ||
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-primary edit-meter-reading"
                                                            title="Edit Pembacaan" data-id="{{ $reading->id }}"
                                                            data-reading='{
                                                            "customer_id": "{{ $reading->meter->customer->id }}",
                                                            "customer_name": "{{ $reading->meter->customer->name ?? '-' }}",
                                                            "meter_number": "{{ $reading->meter->meter_number ?? '-' }}",
                                                            "previous_reading": {{ $reading->previous_reading ?? 0 }},
                                                            "current_reading": {{ $reading->current_reading ?? 0 }},
                                                            "volume_usage": {{ $reading->volume_usage ?? 0 }},
                                                            "status": "{{ $reading->status }}",
                                                            "reading_at": "{{ $reading->reading_at }}",
                                                            "notes": "{{ Str::replace('"', '\\"', $reading->notes ?? '') }}",
                                                            "photo_url": "{{ $reading->photo_url ?? '' }}"}'>
                                                            <i class="bi bi-pencil-square"></i>
                                                        </button>
                                                    @endif
                                                    @if ($reading->status === 'pending')
                                                        @if (!is_null($reading->latestBill))
                                                            <button type="button" class="btn btn-sm btn-outline-success"
                                                                onclick="payBill({{ $reading->latestBill->id }}, '{{ $reading->meter->customer->name ?? 'Pelanggan' }}', '{{ $reading->latestBill->bill_number }}', {{ $reading->latestBill->total_bill }})"
                                                                title="Bayar">
                                                                <i class="bi bi-credit-card-fill"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                                onclick="cancelBilling({{ $reading->id }}, '{{ $reading->meter->customer->name ?? 'Pelanggan' }}', '{{ $reading->latestBill->bill_number }}', {{ $reading->latestBill->total_bill }})"
                                                                title="Batalkan Terbitkan Billing">
                                                                <i class="bi bi-caret-down-square"></i>
                                                            </button>
                                                            ||
                                                        @endif
                                                    @endif
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        onclick="deleteMeterReading({{ $reading->id }}, '{{ $reading->meter->customer->name ?? 'Pelanggan' }}', '{{ $reading->meter->meter_number ?? '-' }}')"
                                                        title="Hapus Pencatatan Meter">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-end align-items-center mt-3">
                            <div>
                                {{ $meterReadings->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="bi bi-inbox display-1 text-muted"></i>
                            </div>
                            <h5 class="text-muted">Tidak Ada Data Pembacaan</h5>
                            <p class="text-muted">
                                Belum ada data pembacaan meter untuk periode <strong>{{ $month ?? '' }}</strong>.
                                <br>Silakan pilih periode lain atau tambahkan data pembacaan terlebih dahulu.
                            </p>
                            <a href="{{ route('pam.meter-readings.index', $pam->id) }}" class="btn btn-primary">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Ringkasan
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @include('dashboard.pam.meter-readings.modals.edit')

    <!-- Publish Meter Reading Confirmation Modal -->
    <div class="modal fade" id="publishMeterReadingModal" tabindex="-1" aria-labelledby="publishMeterReadingModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="publishMeterReadingModalLabel">
                        <i class="bi bi-check-circle me-2"></i>Konfirmasi Terbitkan Pencatatan Meter
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <div>Anda akan menerbitkan pencatatan meter ini dan membuat tagihan.</div>
                    </div>

                    <div class="mb-3">
                        <strong>Informasi Pencatatan:</strong>
                        <ul class="mb-0 mt-2">
                            <li><strong>Pelanggan:</strong> <span id="publishCustomerName" class="text-success"></span>
                            </li>
                            <li><strong>No. Meter:</strong> <span id="publishMeterNumber" class="text-success"></span>
                            </li>
                            <li><strong>Pemakaian:</strong> <span id="publishVolumeUsage"
                                    class="text-primary font-weight-bold"></span></li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <strong>Tindakan ini akan:</strong>
                        <ul class="mb-0 mt-2">
                            <li><i class="bi bi-check-circle text-success me-1"></i> Mengubah status pencatatan menjadi
                                "Menunggu"</li>
                            <li><i class="bi bi-receipt text-info me-1"></i> <strong>Membuat tagihan</strong> dengan
                                perhitungan tarif</li>
                            <li><i class="bi bi-calculator text-warning me-1"></i> Menghitung biaya berdasarkan tier tariff
                            </li>
                            <li><i class="bi bi-plus-circle text-primary me-1"></i> Menambahkan biaya tetap yang aktif</li>
                        </ul>
                    </div>

                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="bi bi-calculator me-2"></i>
                        <div>Perhitungan tagihan: (Pemakaian × Tarif Tier) + Biaya Tetap Aktif</div>
                    </div>

                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="bi bi-shield-check me-2"></i>
                        <div>Setelah diterbitkan, tagihan akan muncul di daftar tagihan dan dapat dibayar.</div>
                    </div>

                    <input type="hidden" id="publishMeterReadingId" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Batal
                    </button>
                    <button type="button" class="btn btn-success" id="confirmPublishMeterReadingBtn"
                        onclick="confirmPublishMeterReading()">
                        <i class="bi bi-check-circle me-1"></i>Ya, Terbitkan & Buat Tagihan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Meter Reading Confirmation Modal -->
    <div class="modal fade" id="deleteMeterReadingModal" tabindex="-1" aria-labelledby="deleteMeterReadingModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteMeterReadingModalLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Hapus Pencatatan Meter
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div><strong>PERINGATAN:</strong> Anda akan menghapus pencatatan meter ini secara permanen!</div>
                    </div>

                    <div class="mb-3">
                        <strong>Informasi Pencatatan:</strong>
                        <ul class="mb-0 mt-2">
                            <li><strong>Pelanggan:</strong> <span id="deleteCustomerName" class="text-danger"></span></li>
                            <li><strong>No. Meter:</strong> <span id="deleteMeterNumber" class="text-danger"></span></li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <strong>Tindakan ini akan:</strong>
                        <ul class="mb-0 mt-2">
                            <li><i class="bi bi-trash text-danger me-1"></i> <strong>MENGHAPUS PERMANEN</strong> pencatatan
                                meter ini</li>
                            <li><i class="bi bi-receipt text-warning me-1"></i> Menghapus tagihan terkait (jika ada)</li>
                            <li><i class="bi bi-x-circle text-danger me-1"></i> Semua data pembacaan akan hilang</li>
                        </ul>
                    </div>

                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="bi bi-shield-exclamation me-2"></i>
                        <div><strong>Tindakan tidak dapat dibatalkan!</strong> Pastikan Anda yakin ingin melanjutkan.</div>
                    </div>

                    <input type="hidden" id="deleteMeterReadingId" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Batal
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteMeterReadingBtn"
                        onclick="confirmDeleteMeterReading()">
                        <i class="bi bi-trash me-1"></i>Ya, Hapus Pencatatan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Confirmation Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="paymentModalLabel">
                        <i class="bi bi-credit-card me-2"></i>Konfirmasi Pembayaran
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="paymentBillId">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Pelanggan:</label>
                        <p id="paymentCustomerName" class="mb-0"></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">No. Tagihan:</label>
                        <p id="paymentBillNumber" class="mb-0 text-primary"></p>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Total Tagihan:</label>
                        <h4 id="paymentTotalAmount" class="text-success mb-0"></h4>
                    </div>

                    <div class="mb-3">
                        <label for="paymentMethod" class="form-label fw-bold">Metode Pembayaran:</label>
                        <select id="paymentMethod" class="form-select">
                            <option value="cash">Tunai</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="ewallet">E-Wallet</option>
                        </select>
                    </div>

                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        <div>
                            Pembayaran akan mengubah status tagihan dan pembacaan meter menjadi <strong>Dibayar</strong>.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Batal
                    </button>
                    <button type="button" class="btn btn-success" id="confirmPaymentBtn" onclick="confirmPayment()">
                        <i class="bi bi-check-circle me-1"></i>Bayar Sekarang
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Billing Confirmation Modal -->
    <div class="modal fade" id="cancelBillingModal" tabindex="-1" aria-labelledby="cancelBillingModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="cancelBillingModalLabel">
                        <i class="bi bi-caret-down-square me-2"></i>Batalkan Terbitkan Billing
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cancelBillingMeterReadingId">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Pelanggan:</label>
                        <p id="cancelBillingCustomerName" class="mb-0"></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">No. Tagihan:</label>
                        <p id="cancelBillingBillNumber" class="mb-0 text-warning"></p>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Total Tagihan:</label>
                        <h4 id="cancelBillingTotalAmount" class="text-danger mb-0"></h4>
                    </div>

                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <div>
                            <strong>Perhatian:</strong> Tindakan ini akan:
                            <ul class="mb-0 mt-2">
                                <li>Menghapus tagihan yang telah dibuat secara permanen</li>
                                <li>Mengubah status pembacaan meter menjadi <strong>Draft</strong></li>
                                <li>Tagihan perlu diterbitkan kembali jika diperlukan</li>
                            </ul>
                        </div>
                    </div>

                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-shield-exclamation me-2"></i>
                        <div>
                            <strong>Tidak dapat dibatalkan!</strong> Setelah dikonfirmasi, perubahan tidak dapat diurungkan.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Tutup
                    </button>
                    <button type="button" class="btn btn-warning text-white" id="confirmCancelBillingBtn"
                        onclick="confirmCancelBilling()">
                        <i class="bi bi-caret-down-square me-1"></i>Ya, Batalkan Tagihan
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

    <!-- Scripts for Delete Functionality -->
    <style>
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


@endsection

@push('scripts')
    <script>
        function deleteMeterReading(readingId, customerName, meterNumber) {
            // Set modal data
            document.getElementById('deleteMeterReadingId').value = readingId;
            document.getElementById('deleteCustomerName').textContent = customerName;
            document.getElementById('deleteMeterNumber').textContent = meterNumber;

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('deleteMeterReadingModal'));
            modal.show();
        }

        function confirmDeleteMeterReading() {
            const readingId = document.getElementById('deleteMeterReadingId').value;

            // Show loading state
            const button = document.getElementById('confirmDeleteMeterReadingBtn');
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Menghapus...';
            button.disabled = true;

            // Send request to delete meter reading
            fetch(`{{ url('/pam/' . $pam->id . '/meter-readings') }}/${readingId}`, {
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
                        // Hide delete modal
                        bootstrap.Modal.getInstance(document.getElementById('deleteMeterReadingModal')).hide();

                        // Show success modal
                        showSuccessModal('Pencatatan Dihapus!', data.message);
                    } else {
                        // Show error modal
                        showErrorModal('Gagal Menghapus Pencatatan', data.message || 'Terjadi kesalahan');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorModal('Gagal Menghapus Pencatatan', 'Terjadi kesalahan jaringan.');
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

        function publishMeterReading(readingId, customerName, meterNumber, volumeUsage) {
            // Set modal data
            document.getElementById('publishMeterReadingId').value = readingId;
            document.getElementById('publishCustomerName').textContent = customerName;
            document.getElementById('publishMeterNumber').textContent = meterNumber;
            document.getElementById('publishVolumeUsage').textContent = volumeUsage + ' m³';

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('publishMeterReadingModal'));
            modal.show();
        }

        function confirmPublishMeterReading() {
            const readingId = document.getElementById('publishMeterReadingId').value;

            // Show loading state
            const button = document.getElementById('confirmPublishMeterReadingBtn');
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Menerbitkan...';
            button.disabled = true;

            // Send request to publish meter reading
            fetch(`{{ url('/pam/' . $pam->id . '/meter-readings') }}/${readingId}/publish`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Hide publish modal
                        bootstrap.Modal.getInstance(document.getElementById('publishMeterReadingModal')).hide();

                        // Show success modal
                        showSuccessModal('Pencatatan Diterbitkan!', data.message + '\nNo. Tagihan: ' + data.data
                            .bill.bill_number + '\nTotal: Rp ' + number_format(data.data.bill.total_bill, 0)
                        );
                    } else {
                        // Show error modal
                        showErrorModal('Gagal Menerbitkan Pencatatan', data.message || 'Terjadi kesalahan');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorModal('Gagal Menerbitkan Pencatatan', 'Terjadi kesalahan jaringan.');
                })
                .finally(() => {
                    // Restore button state
                    button.innerHTML = originalHtml;
                    button.disabled = false;
                });
        }

        function payBill(billId, customerName, billNumber, totalAmount) {
            // Set modal data
            document.getElementById('paymentBillId').value = billId;
            document.getElementById('paymentCustomerName').textContent = customerName;
            document.getElementById('paymentBillNumber').textContent = billNumber;
            document.getElementById('paymentTotalAmount').textContent = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(totalAmount);;
            document.getElementById('paymentMethod').value = 'cash';

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
            modal.show();
        }

        function confirmPayment() {
            const billId = document.getElementById('paymentBillId').value;
            const paymentMethod = document.getElementById('paymentMethod').value;

            // Show loading state
            const button = document.getElementById('confirmPaymentBtn');
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Memproses Pembayaran...';
            button.disabled = true;

            // Send request to process payment
            fetch(`{{ url('/pam/' . $pam->id . '/bills/pay-bulk') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify({
                        bill_ids: [parseInt(billId)],
                        payment_method: paymentMethod
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Hide payment modal
                        bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();

                        // Show success modal with payment details
                        const updatedBill = data.data.updated_bills[0];
                        const successMessage = `${data.message}\n\n` +
                            `No. Tagihan: ${updatedBill.bill_number}\n` +
                            `Pelanggan: ${updatedBill.customer_name}\n` +
                            `Total: Rp ${number_format(updatedBill.total_bill, 0)}\n` +
                            `Dibayar pada: ${new Date(updatedBill.paid_at).toLocaleString('id-ID')}`;

                        showSuccessModal('Pembayaran Berhasil!', successMessage);
                    } else {
                        // Hide payment modal
                        bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                        // Show error modal
                        showErrorModal('Pembayaran Gagal', data.message || 'Terjadi kesalahan');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Hide payment modal
                    bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                    showErrorModal('Pembayaran Gagal', 'Terjadi kesalahan jaringan.');
                })
                .finally(() => {
                    // Restore button state
                    button.innerHTML = originalHtml;
                    button.disabled = false;
                });
        }

        function cancelBilling(meterReadingId, customerName, billNumber, totalAmount) {
            // Set modal data
            document.getElementById('cancelBillingMeterReadingId').value = meterReadingId;
            document.getElementById('cancelBillingCustomerName').textContent = customerName;
            document.getElementById('cancelBillingBillNumber').textContent = billNumber;
            document.getElementById('cancelBillingTotalAmount').textContent = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(totalAmount);

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('cancelBillingModal'));
            modal.show();
        }

        function confirmCancelBilling() {
            const meterReadingId = document.getElementById('cancelBillingMeterReadingId').value;

            // Show loading state
            const button = document.getElementById('confirmCancelBillingBtn');
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Membatalkan...';
            button.disabled = true;

            // Send request to cancel billing
            fetch(`{{ url('/pam/' . $pam->id . '/meter-readings') }}/${meterReadingId}/cancel-billing`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Hide cancel billing modal
                        bootstrap.Modal.getInstance(document.getElementById('cancelBillingModal')).hide();

                        // Show success modal
                        const successMessage = `${data.message}\n\n` +
                            `No. Tagihan: ${data.data.bill_number}\n` +
                            `Pelanggan: ${data.data.customer_name}\n` +
                            `Total yang dibatalkan: Rp ${number_format(data.data.total_bill, 0)}`;

                        showSuccessModal('Tagihan Dibatalkan!', successMessage);
                    } else {
                        // Hide cancel billing modal
                        bootstrap.Modal.getInstance(document.getElementById('cancelBillingModal')).hide();
                        // Show error modal
                        showErrorModal('Gagal Membatalkan Tagihan', data.message || 'Terjadi kesalahan');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Hide cancel billing modal
                    bootstrap.Modal.getInstance(document.getElementById('cancelBillingModal')).hide();
                    showErrorModal('Gagal Membatalkan Tagihan', 'Terjadi kesalahan jaringan.');
                })
                .finally(() => {
                    // Restore button state
                    button.innerHTML = originalHtml;
                    button.disabled = false;
                });
        }
    </script>
@endpush
