@extends('layouts.pam')

@section('title', 'Pembacaan Meter - ' . $pam->name)

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
            <i class="bi bi-speedometer2 me-1"></i>Pembacaan Meter
        </li>
    @endsection

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1">
                            <i class="bi bi-speedometer2 text-primary me-2"></i>
                            Pembacaan Meter Bulanan
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

    <!-- Current Month Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="dashboard-card text-center">
                <div class="text-primary mb-2">
                    <i class="bi bi-calendar-check fs-2"></i>
                </div>
                <h4 class="mb-1">{{ $currentMonthDisplay }}</h4>
                <p class="text-muted mb-0">Bulan Saat Ini</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-card text-center">
                <div class="text-info mb-2">
                    <i class="bi bi-speedometer2 fs-2"></i>
                </div>
                <h4 class="mb-1">{{ number_format($currentMonthStats['total_readings']) }}</h4>
                <p class="text-muted mb-0">Total Pembacaan</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-card text-center">
                <div class="text-success mb-2">
                    <i class="bi bi-droplet fs-2"></i>
                </div>
                <h4 class="mb-1">{{ number_format($currentMonthStats['total_usage'], 2) }} m³</h4>
                <p class="text-muted mb-0">Total Pemakaian</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-card text-center">
                <div class="text-warning mb-2">
                    <i class="bi bi-check-circle fs-2"></i>
                </div>
                <h4 class="mb-1">{{ $currentMonthStats['verification_rate'] }}%</h4>
                <p class="text-muted mb-0">Tingkat Verifikasi</p>
            </div>
        </div>
    </div>

    <!-- Monthly Overview -->
    <div class="row">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar3 me-2"></i>Ringkasan Bulanan
                    </h5>
                    <button class="btn btn-outline-primary btn-sm" onclick="refreshData()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                    </button>
                </div>

                @if ($monthsWithData)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Bulan</th>
                                    <th>Total Pembacaan</th>
                                    <th>Total Pemakaian (m³)</th>
                                    <th>Rata-rata Pemakaian (m³)</th>
                                    <th>Terverifikasi</th>
                                    <th>Menunggu Verifikasi</th>
                                    <th>Tingkat Verifikasi</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($monthsWithData as $month)
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">
                                                {{ $month['display'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-dark">
                                                {{ $month['total_readings'] ?? 0 }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success text-dark">
                                                {{ number_format($month['total_usage'] ?? 0, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-dark">
                                                {{ number_format($month['average_usage'] ?? 0, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">
                                                {{ $month['verified_readings'] ?? 0 }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-dark">
                                                {{ $month['pending_readings'] ?? 0 }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar
                                                    {{ ($month['verification_rate'] ?? 0) >= 90
                                                        ? 'bg-success'
                                                        : (($month['verification_rate'] ?? 0) >= 70
                                                            ? 'bg-warning'
                                                            : 'bg-danger') }}"
                                                    role="progressbar"
                                                    style="width: {{ $month['verification_rate'] ?? 0 }}%"
                                                    title="{{ $month['verification_rate'] ?? 0 }}%">
                                                    {{ $month['verification_rate'] ?? 0 }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('pam.meter-readings.month', [$pam->id, $month['value']]) }}"
                                                    class="btn btn-outline-primary" title="Lihat Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('pam.meter-readings.export', [$pam->id, $month['value']]) }}"
                                                    class="btn btn-outline-success" title="Export Excel">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="text-muted">
                            <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
                            <h5>Belum Ada Data Pembacaan</h5>
                            <p>Belum ada data pembacaan meter untuk periode apapun pada PAM ini.</p>
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
    .progress {
        background-color: #e9ecef;
    }

    .progress-bar {
        font-size: 0.75rem;
        line-height: 20px;
    }

    .badge {
        font-size: 0.8em;
    }
</style>
@endpush

@push('scripts')
<script>
    function refreshData() {
        location.reload();
    }
</script>
@endpush
