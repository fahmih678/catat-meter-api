@extends('layouts.pam')

@section('title', 'Detail Pembacaan Meter - ' . $monthDisplay . ' - ' . $pam->name)

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
                    <i class="bi bi-calendar3 me-1"></i>{{ $monthDisplay }}
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
                                PAM: {{ $pam->name }} ({{ $pam->code }}) | Periode: {{ $monthDisplay }}
                            </p>
                        </div>
                        <div class="btn-group">
                            <a href="{{ route('pam.meter-readings.index', $pam->id) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Kembali
                            </a>
                            <a href="{{ route('pam.meter-readings.export', [$pam->id, $month]) }}" class="btn btn-success">
                                <i class="bi bi-download me-1"></i>Export Excel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="text-primary mb-2">
                        <i class="bi bi-speedometer2 fs-2"></i>
                    </div>
                    <h4 class="mb-1">{{ number_format($totalReadings) }}</h4>
                    <p class="text-muted mb-0">Total Pembacaan</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="text-success mb-2">
                        <i class="bi bi-droplet fs-2"></i>
                    </div>
                    <h4 class="mb-1">{{ number_format($totalUsage, 2) }} m³</h4>
                    <p class="text-muted mb-0">Total Pemakaian</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="text-info mb-2">
                        <i class="bi bi-graph-up fs-2"></i>
                    </div>
                    <h4 class="mb-1">{{ number_format($averageUsage, 2) }} m³</h4>
                    <p class="text-muted mb-0">Rata-rata Pemakaian</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="text-warning mb-2">
                        <i class="bi bi-check-circle fs-2"></i>
                    </div>
                    <h4 class="mb-1">{{ $statistics['verification_rate'] }}%</h4>
                    <p class="text-muted mb-0">Tingkat Verifikasi</p>
                </div>
            </div>
        </div>

        <!-- Additional Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="dashboard-card">
                    <h6 class="text-muted mb-3">Status Verifikasi</h6>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>✅ Terverifikasi</span>
                        <span class="badge bg-success">{{ $statistics['verified_readings'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>⏳ Menunggu Verifikasi</span>
                        <span class="badge bg-warning text-dark">{{ $statistics['pending_readings'] }}</span>
                    </div>
                    <div class="progress mt-2" style="height: 8px;">
                        <div class="progress-bar bg-success"
                            style="width: {{ $statistics['verified_readings'] && $statistics['total_readings']
                                ? ($statistics['verified_readings'] / $statistics['total_readings']) * 100
                                : 0 }}%">
                        </div>
                        <div class="progress-bar bg-warning"
                            style="width: {{ $statistics['pending_readings'] && $statistics['total_readings']
                                ? ($statistics['pending_readings'] / $statistics['total_readings']) * 100
                                : 0 }}%">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card">
                    <h6 class="text-muted mb-3">Informasi</h6>
                    <div class="mb-2">
                        <small class="text-muted">Total Meter</small>
                        <div class="fw-semibold">{{ number_format($totalMeters) }}</div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Pembacaan per Meter</small>
                        <div class="fw-semibold">
                            {{ $totalMeters > 0 ? round($totalReadings / $totalMeters, 1) : 0 }}
                        </div>
                    </div>
                    <div>
                        <small class="text-muted">Pembacaan Terakhir</small>
                        <div class="fw-semibold">{{ $statistics['last_reading_date'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card">
                    <h6 class="text-muted mb-3">Navigasi Bulan</h6>
                    <div class="btn-group w-100" role="group">
                        @php
                            $currentIndex = array_search($month, array_column($monthsWithData, 'value'));
                            $previousMonth = $currentIndex > 0 ? $monthsWithData[$currentIndex - 1] : null;
                            $nextMonth =
                                $currentIndex < count($monthsWithData) - 1 ? $monthsWithData[$currentIndex + 1] : null;
                        @endphp

                        @if ($previousMonth)
                            <a href="{{ route('pam.meter-readings.month', [$pam->id, $previousMonth['value']]) }}"
                                class="btn btn-outline-secondary" title="{{ $previousMonth['display'] }}">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        @else
                            <button class="btn btn-outline-secondary" disabled>
                                <i class="bi bi-chevron-left"></i>
                            </button>
                        @endif

                        <button class="btn btn-outline-primary" disabled>
                            {{ $monthDisplay }}
                        </button>

                        @if ($nextMonth)
                            <a href="{{ route('pam.meter-readings.month', [$pam->id, $nextMonth['value']]) }}"
                                class="btn btn-outline-secondary" title="{{ $nextMonth['display'] }}">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        @else
                            <button class="btn btn-outline-secondary" disabled>
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Meter Readings Details -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="bi bi-list-ul me-2"></i>Detail Pembacaan per Meter
                        </h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="expandAll()">
                                <i class="bi bi-arrows-expand"></i> Expand All
                            </button>
                            <button class="btn btn-outline-secondary" onclick="collapseAll()">
                                <i class="bi bi-arrows-collapse"></i> Collapse All
                            </button>
                        </div>
                    </div>

                    @if ($readingsByMeter)
                        <div class="accordion" id="meterReadingsAccordion">
                            @foreach ($readingsByMeter as $meterId => $meterData)
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading{{ $meterId }}">
                                        <button class="accordion-button collapsed" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#collapse{{ $meterId }}">
                                            <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-speedometer2 text-primary me-2"></i>
                                                    <span
                                                        class="fw-semibold">{{ $meterData['meter']->meter_number ?? 'Unknown' }}</span>
                                                    @if ($meterData['customer'])
                                                        <span
                                                            class="badge bg-secondary ms-2">{{ $meterData['customer']->name }}</span>
                                                    @else
                                                        <span class="badge bg-warning ms-2">No Customer</span>
                                                    @endif
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-info text-dark me-2">
                                                        {{ $meterData['readings']->count() }} pembacaan
                                                    </span>
                                                    <span class="badge bg-success text-dark me-2">
                                                        {{ number_format($meterData['total_usage'], 2) }} m³
                                                    </span>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse{{ $meterId }}" class="accordion-collapse collapse"
                                        data-bs-parent="#meterReadingsAccordion">
                                        <div class="accordion-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Tanggal</th>
                                                            <th>Angka Awal</th>
                                                            <th>Angka Akhir</th>
                                                            <th>Pemakaian</th>
                                                            <th>Petugas</th>
                                                            <th>Status</th>
                                                            <th>Catatan</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($meterData['readings'] as $reading)
                                                            <tr>
                                                                <td>{{ $reading->reading_at ? $reading->reading_at->format('d/m/Y') : '-' }}
                                                                </td>
                                                                <td>{{ $reading->previous_reading ?? 0 }}</td>
                                                                <td>{{ $reading->current_reading ?? 0 }}</td>
                                                                <td>
                                                                    <span class="badge bg-info text-dark">
                                                                        {{ $reading->volume_usage ?? 0 }} m³
                                                                    </span>
                                                                </td>
                                                                <td>{{ $reading->readingBy ? $reading->readingBy->name : '-' }}
                                                                </td>
                                                                <td>
                                                                    <span
                                                                        class="badge
                                                                    {{ $reading->status === 'verified' ? 'bg-success' : 'bg-warning text-dark' }}">
                                                                        {{ $reading->status === 'verified' ? 'Terverifikasi' : 'Menunggu' }}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <small class="text-muted">
                                                                        {{ $reading->notes ?? '-' }}
                                                                    </small>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="text-muted">
                                <i class="bi bi-x-circle fs-1 d-block mb-3"></i>
                                <h5>Tidak Ada Data</h5>
                                <p>Tidak ada data pembacaan meter untuk bulan {{ $monthDisplay }}.</p>
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
        .accordion-button:not(.collapsed) {
            background-color: #f8f9fa;
        }

        .accordion-button:focus {
            box-shadow: none;
            border-color: rgba(0, 0, 0, .125);
        }

        .badge {
            font-size: 0.75em;
        }

        .progress {
            background-color: #e9ecef;
        }

        .progress-bar {
            font-size: 0.75rem;
            line-height: 8px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        function expandAll() {
            document.querySelectorAll('.accordion-collapse').forEach(collapse => {
                collapse.classList.add('show');
            });
            document.querySelectorAll('.accordion-button').forEach(button => {
                button.classList.remove('collapsed');
                button.setAttribute('aria-expanded', 'true');
            });
        }

        function collapseAll() {
            document.querySelectorAll('.accordion-collapse').forEach(collapse => {
                collapse.classList.remove('show');
            });
            document.querySelectorAll('.accordion-button').forEach(button => {
                button.classList.add('collapsed');
                button.setAttribute('aria-expanded', 'false');
            });
        }
    </script>
@endpush
