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
                                    <th>Periode</th>
                                    <th>Status</th>
                                    <th>Total Pemakaian (m³)</th>
                                    <th>Total Tagihan</th>
                                    <th>Pembacaan</th>
                                    <th>Pelanggan</th>
                                    <th>Didaftarkan</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($monthsWithData as $month)
                                    <tr>
                                        <td>
                                            <div>
                                                <span
                                                    class="badge bg-primary">{{ date('M Y', strtotime($month->period)) }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span
                                                class="badge
                                                {{ $month->status === 'open' ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $month->status }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">
                                                <i class="bi bi-droplet me-1"></i>
                                                {{ number_format($month->total_usage, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-dark">
                                                <i class="bi bi-cash-stack me-1"></i>
                                                {{ number_format($month->total_bills, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <small class="text-muted me-1">Pembacaan:
                                                    <span
                                                        class="badge bg-primary">{{ $month->meter_readings_count ?? 0 }}
                                                    </span>
                                                </small>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <small class="text-muted me-1">Lunas:
                                                    <span
                                                        class="badge bg-success">{{ $month->paid_meter_readings_count ?? 0 }}
                                                    </span>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">
                                                <i class="bi bi-person me-1"></i>
                                                {{ number_format($month->total_customers) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <small class="text-muted d-block">
                                                    <i
                                                        class="bi bi-person me-1"></i>{{ $month->registeredBy->name ?? '-' }}
                                                </small>
                                                <small class="text-muted d-block">
                                                    <i
                                                        class="bi bi-calendar me-1"></i>{{ $month->created_at->format('d M Y') ?? '-' }}
                                                </small>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group-sm">
                                                <a href="{{ route('pam.meter-readings.month', ['pamId' => $pam->id, 'month' => $month->period]) }}"
                                                    class="btn btn-outline-primary" title="Lihat Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('pam.meter-readings.export', ['pamId' => $pam->id, 'month' => $month->period]) }}"
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
                            <h5>Belum Ada Data Bulanan Terdaftar</h5>
                            <p>Belum ada data bulanan yang terdaftar untuk PAM ini. Silakan daftarkan bulan terlebih
                                dahulu untuk mulai mencatat pembacaan meter.</p>
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
