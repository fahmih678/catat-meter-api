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
                            <a href="{{ route('pam.meter-readings.export', ['pamId' => $pam->id, 'month' => $month]) }}"
                                class="btn btn-success">
                                <i class="bi bi-download me-1"></i>Export Excel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card stats-card-primary">
                    <div class="stats-card-body">
                        <div class="stats-card-icon">
                            <i class="bi bi-speedometer2"></i>
                        </div>
                        <div class="stats-card-content">
                            <h3 class="stats-card-number">{{ number_format($totalReadings) }}</h3>
                            <p class="stats-card-label">Total Pembacaan</p>
                            <div class="stats-card-progress">
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar bg-primary" style="width: 100%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card stats-card-success">
                    <div class="stats-card-body">
                        <div class="stats-card-icon">
                            <i class="bi bi-droplet-fill"></i>
                        </div>
                        <div class="stats-card-content">
                            <h3 class="stats-card-number">{{ number_format($totalUsage, 1) }} <small>m³</small></h3>
                            <p class="stats-card-label">Total Pemakaian</p>
                            <div class="stats-card-progress">
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar bg-success"
                                        style="width: min(100%, {{ ($totalUsage / 1000) * 100 }}%)"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card stats-card-info">
                    <div class="stats-card-body">
                        <div class="stats-card-icon">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <div class="stats-card-content">
                            <h3 class="stats-card-number">{{ number_format($averageUsage, 1) }} <small>m³</small></h3>
                            <p class="stats-card-label">Rata-rata Pemakaian</p>
                            <div class="stats-card-progress">
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar bg-info"
                                        style="width: min(100%, {{ ($averageUsage / 50) * 100 }}%)"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card stats-card-warning">
                    <div class="stats-card-body">
                        <div class="stats-card-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="stats-card-content">
                            <h3 class="stats-card-number">{{ $statistics['verification_rate'] }}<small>%</small></h3>
                            <p class="stats-card-label">Tingkat Verifikasi</p>
                            <div class="stats-card-progress">
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar
                                        {{ $statistics['verification_rate'] >= 90
                                            ? 'bg-success'
                                            : ($statistics['verification_rate'] >= 70
                                                ? 'bg-warning'
                                                : 'bg-danger') }}"
                                        style="width: {{ $statistics['verification_rate'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
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
            <div class="col-md-3">
                <div class="dashboard-card">
                    <h6 class="text-muted mb-3">Informasi Periode</h6>
                    <div class="mb-2">
                        <small class="text-muted">Total Pelanggan</small>
                        <div class="fw-semibold">{{ number_format($statistics['total_customers'] ?? 0) }}</div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Total Tagihan</small>
                        <div class="fw-semibold">Rp {{ number_format($statistics['total_bills'] ?? 0, 2) }}</div>
                    </div>
                    <div>
                        <small class="text-muted">Status Periode</small>
                        <div>
                            <span class="badge {{ $statistics['status'] === 'open' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $statistics['status_display'] }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card">
                    <h6 class="text-muted mb-3">Informasi Pembacaan</h6>
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
            <div class="col-md-3">
                <div class="dashboard-card">
                    <h6 class="text-muted mb-3">Informasi Pendaftaran</h6>
                    <div class="mb-2">
                        <small class="text-muted">Didaftarkan oleh</small>
                        <div class="fw-semibold">{{ $statistics['registered_by'] }}</div>
                    </div>
                    <div>
                        <small class="text-muted">Tanggal Daftar</small>
                        <div class="fw-semibold">{{ $statistics['registered_at'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <h6 class="text-muted mb-3">Navigasi Bulan</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="btn-group" role="group">
                            @php
                                $currentIndex = array_search($month, array_column($monthsWithData, 'value'));
                                $previousMonth = $currentIndex > 0 ? $monthsWithData[$currentIndex - 1] : null;
                                $nextMonth =
                                    $currentIndex < count($monthsWithData) - 1
                                        ? $monthsWithData[$currentIndex + 1]
                                        : null;
                            @endphp

                            @if ($previousMonth)
                                <a href="{{ route('pam.meter-readings.month', ['pamId' => $pam->id, 'month' => $previousMonth['value']]) }}"
                                    class="btn btn-outline-secondary" title="{{ $previousMonth['display'] }}">
                                    <i class="bi bi-chevron-left me-1"></i>
                                    {{ $previousMonth['display'] }}
                                </a>
                            @else
                                <button class="btn btn-outline-secondary" disabled>
                                    <i class="bi bi-chevron-left me-1"></i>
                                    Bulan Sebelumnya
                                </button>
                            @endif

                            <button class="btn btn-outline-primary" disabled>
                                <i class="bi bi-calendar3 me-1"></i>
                                {{ $monthDisplay }}
                                <span
                                    class="badge {{ $statistics['status'] === 'open' ? 'bg-success' : 'bg-secondary' }} ms-2">
                                    {{ $statistics['status_display'] }}
                                </span>
                            </button>

                            @if ($nextMonth)
                                <a href="{{ route('pam.meter-readings.month', ['pamId' => $pam->id, 'month' => $nextMonth['value']]) }}"
                                    class="btn btn-outline-secondary" title="{{ $nextMonth['display'] }}">
                                    {{ $nextMonth['display'] }}
                                    <i class="bi bi-chevron-right ms-1"></i>
                                </a>
                            @else
                                <button class="btn btn-outline-secondary" disabled>
                                    Bulan Berikutnya
                                    <i class="bi bi-chevron-right ms-1"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Meter Readings Details -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="mb-1">
                                <i class="bi bi-list-columns me-2"></i>Detail Pembacaan per Meter
                            </h5>
                            <p class="text-muted mb-0">Daftar lengkap pembacaan meter untuk periode {{ $monthDisplay }}
                            </p>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="expandAll()" title="Expand All (Tekan 'E')">
                                <i class="bi bi-arrows-expand"></i> Expand All
                            </button>
                            <button class="btn btn-outline-secondary" onclick="collapseAll()"
                                title="Collapse All (Tekan 'C')">
                                <i class="bi bi-arrows-collapse"></i> Collapse All
                            </button>
                        </div>
                    </div>

                    @if ($readingsByMeter)
                        <!-- Summary Cards -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="info-card info-card-primary">
                                    <div class="info-card-body">
                                        <div class="info-card-icon">
                                            <i class="bi bi-speedometer2"></i>
                                        </div>
                                        <div class="info-card-content">
                                            <h4>{{ count($readingsByMeter) }}</h4>
                                            <p>Total Meter</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-card info-card-success">
                                    <div class="info-card-body">
                                        <div class="info-card-icon">
                                            <i class="bi bi-check-circle"></i>
                                        </div>
                                        <div class="info-card-content">
                                            <h4>{{ $statistics['verified_readings'] }}</h4>
                                            <p>Terverifikasi</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-card info-card-warning">
                                    <div class="info-card-body">
                                        <div class="info-card-icon">
                                            <i class="bi bi-clock"></i>
                                        </div>
                                        <div class="info-card-content">
                                            <h4>{{ $statistics['pending_readings'] }}</h4>
                                            <p>Menunggu Verifikasi</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced Accordion -->
                        <div class="enhanced-accordion" id="meterReadingsAccordion">
                            @foreach ($readingsByMeter as $meterId => $meterData)
                                <div class="enhanced-accordion-item" data-meter-id="{{ $meterId }}">
                                    <div class="enhanced-accordion-header" id="heading{{ $meterId }}">
                                        <button class="enhanced-accordion-button collapsed" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#collapse{{ $meterId }}"
                                            aria-expanded="false" aria-controls="collapse{{ $meterId }}">

                                            <div class="enhanced-accordion-content">
                                                <div class="meter-info">
                                                    <div class="meter-icon">
                                                        <i class="bi bi-speedometer2"></i>
                                                    </div>
                                                    <div class="meter-details">
                                                        <div class="meter-number">
                                                            <span class="meter-label">No. Meter</span>
                                                            <span
                                                                class="meter-value">{{ $meterData['meter']->meter_number ?? 'Unknown' }}</span>
                                                        </div>
                                                        @if ($meterData['customer'])
                                                            <div class="customer-name">
                                                                <span class="customer-label">Pelanggan</span>
                                                                <span
                                                                    class="customer-value">{{ $meterData['customer']->name }}</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="reading-stats">
                                                    <div class="stat-item">
                                                        <span
                                                            class="stat-value">{{ $meterData['readings']->count() }}</span>
                                                        <span class="stat-label">Pembacaan</span>
                                                    </div>
                                                    <div class="stat-item stat-item-success">
                                                        <span
                                                            class="stat-value">{{ number_format($meterData['total_usage'], 1) }}</span>
                                                        <span class="stat-label">Total (m³)</span>
                                                    </div>
                                                    <div class="stat-item">
                                                        <span
                                                            class="stat-value">{{ $meterData['readings']->count() > 0 ? number_format($meterData['total_usage'] / $meterData['readings']->count(), 1) : 0 }}</span>
                                                        <span class="stat-label">Rata-rata (m³)</span>
                                                    </div>
                                                </div>

                                                <div class="accordion-toggle">
                                                    <i class="bi bi-chevron-down"></i>
                                                </div>
                                            </div>
                                        </button>
                                    </div>

                                    <div id="collapse{{ $meterId }}" class="enhanced-accordion-collapse collapse"
                                        data-bs-parent="#meterReadingsAccordion">
                                        <div class="enhanced-accordion-body">
                                            <!-- Enhanced Table -->
                                            <div class="enhanced-table-container">
                                                <table class="enhanced-table">
                                                    <thead>
                                                        <tr>
                                                            <th><i class="bi bi-calendar3 me-1"></i>Tanggal</th>
                                                            <th><i class="bi bi-arrow-down-up me-1"></i>Angka Awal</th>
                                                            <th><i class="bi bi-arrow-up me-1"></i>Angka Akhir</th>
                                                            <th><i class="bi bi-droplet me-1"></i>Pemakaian</th>
                                                            <th><i class="bi bi-person me-1"></i>Petugas</th>
                                                            <th><i class="bi bi-shield-check me-1"></i>Status</th>
                                                            <th><i class="bi bi-chat-left-text me-1"></i>Catatan</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($meterData['readings'] as $reading)
                                                            <tr
                                                                class="reading-row {{ $reading->status === 'verified' ? 'reading-verified' : 'reading-pending' }}">
                                                                <td>
                                                                    <div class="date-cell">
                                                                        <i class="bi bi-calendar-date"></i>
                                                                        <span>{{ $reading->reading_at ? $reading->reading_at->format('d/m/Y') : '-' }}</span>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <span
                                                                        class="reading-value reading-start">{{ number_format($reading->previous_reading ?? 0, 1) }}</span>
                                                                </td>
                                                                <td>
                                                                    <span
                                                                        class="reading-value reading-end">{{ number_format($reading->current_reading ?? 0, 1) }}</span>
                                                                </td>
                                                                <td>
                                                                    <div class="usage-cell">
                                                                        <span
                                                                            class="usage-badge {{ $reading->volume_usage > 0 ? 'usage-high' : 'usage-zero' }}">
                                                                            {{ number_format($reading->volume_usage ?? 0, 1) }}
                                                                            m³
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="officer-cell">
                                                                        <i class="bi bi-person-circle"></i>
                                                                        <span>{{ $reading->readingBy ? $reading->readingBy->name : '-' }}</span>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <span
                                                                        class="status-badge status-{{ $reading->status }}">
                                                                        <i
                                                                            class="bi {{ $reading->status === 'verified' ? 'bi-check-circle-fill' : 'bi-clock-fill' }}"></i>
                                                                        {{ $reading->status === 'verified' ? 'Terverifikasi' : 'Menunggu' }}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <div class="notes-cell">
                                                                        @if ($reading->notes)
                                                                            <i class="bi bi-chat-left-text"></i>
                                                                            <span>{{ Str::limit($reading->notes, 50) }}</span>
                                                                        @else
                                                                            <span class="text-muted">-</span>
                                                                        @endif
                                                                    </div>
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
                        <div class="empty-state">
                            <div class="empty-state-content">
                                <div class="empty-state-icon">
                                    <i class="bi bi-inbox"></i>
                                </div>
                                <h4 class="empty-state-title">Tidak Ada Data Pembacaan</h4>
                                <p class="empty-state-text">
                                    Belum ada data pembacaan meter untuk periode <strong>{{ $monthDisplay }}</strong>.
                                    <br>Silakan pilih periode lain atau tambahkan data pembacaan terlebih dahulu.
                                </p>
                                <div class="empty-state-actions">
                                    <a href="{{ route('pam.meter-readings.index', $pam->id) }}" class="btn btn-primary">
                                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Ringkasan
                                    </a>
                                </div>
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
        /* Enhanced Stats Cards */
        .stats-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .stats-card-body {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            gap: 1rem;
        }

        .stats-card-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            flex-shrink: 0;
        }

        .stats-card-primary .stats-card-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stats-card-success .stats-card-icon {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stats-card-info .stats-card-icon {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stats-card-warning .stats-card-icon {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .stats-card-content {
            flex: 1;
        }

        .stats-card-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: #2d3748;
        }

        .stats-card-number small {
            font-size: 1rem;
            font-weight: 400;
            opacity: 0.7;
        }

        .stats-card-label {
            color: #718096;
            font-size: 0.875rem;
            font-weight: 500;
            margin: 0;
        }

        .stats-card-progress {
            margin-top: 0.75rem;
        }

        /* Info Cards */
        .info-card {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .info-card-body {
            display: flex;
            align-items: center;
            padding: 1.25rem;
            gap: 1rem;
        }

        .info-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            flex-shrink: 0;
        }

        .info-card-primary .info-card-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .info-card-success .info-card-icon {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .info-card-warning .info-card-icon {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .info-card-content h4 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: #2d3748;
        }

        .info-card-content p {
            color: #718096;
            font-size: 0.875rem;
            margin: 0;
        }

        /* Enhanced Accordion */
        .enhanced-accordion {
            border-radius: 12px;
            overflow: hidden;
        }

        .enhanced-accordion-item {
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            margin-bottom: 0.75rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .enhanced-accordion-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .enhanced-accordion-button {
            width: 100%;
            padding: 0;
            border: none;
            background: transparent;
            text-align: left;
            transition: all 0.3s ease;
        }

        .enhanced-accordion-button:focus {
            box-shadow: none;
            outline: none;
        }

        .enhanced-accordion-button[aria-expanded="true"] .accordion-toggle i {
            transform: rotate(180deg);
        }

        .enhanced-accordion-content {
            display: flex;
            align-items: center;
            padding: 1.25rem;
            gap: 1rem;
        }

        .meter-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
        }

        .meter-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .meter-details {
            flex: 1;
        }

        .meter-number,
        .customer-name {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .meter-label,
        .customer-label {
            font-size: 0.75rem;
            color: #718096;
            font-weight: 500;
        }

        .meter-value,
        .customer-value {
            font-size: 1rem;
            font-weight: 600;
            color: #2d3748;
        }

        .reading-stats {
            display: flex;
            gap: 1.5rem;
            margin-right: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.08);
            min-width: 60px;
        }

        .stat-item-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.2);
        }

        .stat-value {
            display: block;
            font-size: 1.125rem;
            font-weight: 700;
            color: #2d3748;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #718096;
            font-weight: 500;
        }

        .accordion-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }

        .accordion-toggle i {
            font-size: 1rem;
            color: #718096;
            transition: transform 0.3s ease;
        }

        .enhanced-accordion-body {
            padding: 0;
        }

        /* Enhanced Table */
        .enhanced-table-container {
            border-radius: 8px;
            overflow: hidden;
            margin: 1rem;
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .enhanced-table {
            width: 100%;
            margin: 0;
            border-collapse: collapse;
        }

        .enhanced-table thead {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .enhanced-table th {
            padding: 1rem;
            font-weight: 600;
            color: #495057;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid rgba(0, 0, 0, 0.08);
        }

        .reading-row {
            transition: all 0.2s ease;
            border-bottom: 1px solid rgba(0, 0, 0, 0.04);
        }

        .reading-row:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .reading-verified {
            border-left: 3px solid #10b981;
        }

        .reading-pending {
            border-left: 3px solid #f59e0b;
        }

        .enhanced-table td {
            padding: 0.875rem 1rem;
            vertical-align: middle;
        }

        .date-cell,
        .officer-cell,
        .notes-cell {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .date-cell i,
        .officer-cell i,
        .notes-cell i {
            color: #718096;
            font-size: 1rem;
        }

        .reading-value {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.875rem;
            background: rgba(0, 0, 0, 0.04);
            color: #2d3748;
        }

        .reading-start {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }

        .reading-end {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        .usage-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .usage-high {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }

        .usage-zero {
            background: rgba(0, 0, 0, 0.1);
            color: #6b7280;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .status-verified {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .status-pending {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        /* Empty State */
        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
        }

        .empty-state-icon {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 3rem;
            color: #667eea;
        }

        .empty-state-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1rem;
        }

        .empty-state-text {
            color: #718096;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .empty-state-actions .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .enhanced-accordion-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .reading-stats {
                width: 100%;
                justify-content: space-between;
                margin-right: 0;
            }

            .stats-card-body {
                flex-direction: column;
                text-align: center;
            }

            .meter-info {
                width: 100%;
            }

            .enhanced-table-container {
                overflow-x: auto;
            }

            .enhanced-table {
                min-width: 600px;
            }
        }

        /* Animation Classes */
        .enhanced-accordion-item {
            animation: slideInUp 0.3s ease;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .reading-row {
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Enhanced Accordion Functions
        function expandAll() {
            document.querySelectorAll('.enhanced-accordion-collapse').forEach(collapse => {
                collapse.classList.add('show');
            });
            document.querySelectorAll('.enhanced-accordion-button').forEach(button => {
                button.classList.remove('collapsed');
                button.setAttribute('aria-expanded', 'true');
            });

            // Animate expand
            document.querySelectorAll('.enhanced-accordion-item').forEach((item, index) => {
                setTimeout(() => {
                    item.style.animation = 'slideInUp 0.3s ease';
                }, index * 50);
            });
        }

        function collapseAll() {
            document.querySelectorAll('.enhanced-accordion-collapse').forEach(collapse => {
                collapse.classList.remove('show');
            });
            document.querySelectorAll('.enhanced-accordion-button').forEach(button => {
                button.classList.add('collapsed');
                button.setAttribute('aria-expanded', 'false');
            });
        }

        // Initialize enhanced interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth scroll behavior
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Add hover effects to cards
            document.querySelectorAll('.stats-card, .info-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px) scale(1.02)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Add click animations to accordion items
            document.querySelectorAll('.enhanced-accordion-button').forEach(button => {
                button.addEventListener('click', function() {
                    const item = this.closest('.enhanced-accordion-item');

                    // Add pulse animation
                    item.style.animation = 'none';
                    setTimeout(() => {
                        item.style.animation = 'pulse 0.3s ease';
                    }, 10);
                });
            });

            // Add search/highlight functionality (optional enhancement)
            const searchParams = new URLSearchParams(window.location.search);
            const highlightMeter = searchParams.get('meter');

            if (highlightMeter) {
                setTimeout(() => {
                    const targetItem = document.querySelector(`[data-meter-id="${highlightMeter}"]`);
                    if (targetItem) {
                        targetItem.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        targetItem.classList.add('highlighted');

                        // Expand if collapsed
                        const button = targetItem.querySelector('.enhanced-accordion-button');
                        if (button.classList.contains('collapsed')) {
                            button.click();
                        }

                        // Remove highlight after 3 seconds
                        setTimeout(() => {
                            targetItem.classList.remove('highlighted');
                        }, 3000);
                    }
                }, 500);
            }

            // Add keyboard navigation
            document.addEventListener('keydown', function(e) {
                // Press 'E' to expand all
                if (e.key === 'e' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                    const activeElement = document.activeElement;
                    if (activeElement.tagName !== 'INPUT' && activeElement.tagName !== 'TEXTAREA') {
                        expandAll();
                    }
                }

                // Press 'C' to collapse all
                if (e.key === 'c' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                    const activeElement = document.activeElement;
                    if (activeElement.tagName !== 'INPUT' && activeElement.tagName !== 'TEXTAREA') {
                        collapseAll();
                    }
                }
            });

            // Add loading states for better UX
            document.querySelectorAll('.btn').forEach(button => {
                button.addEventListener('click', function() {
                    if (this.href && !this.href.includes('#')) {
                        this.style.opacity = '0.7';
                        this.style.pointerEvents = 'none';

                        setTimeout(() => {
                            this.style.opacity = '1';
                            this.style.pointerEvents = 'auto';
                        }, 1000);
                    }
                });
            });

            // Auto-refresh notification (optional)
            let lastActivity = Date.now();
            document.addEventListener('click', () => lastActivity = Date.now());
            document.addEventListener('keypress', () => lastActivity = Date.now());

            // Check for inactivity every 30 seconds
            setInterval(() => {
                if (Date.now() - lastActivity > 30000) {
                    showRefreshNotification();
                }
            }, 30000);
        });

        // Refresh notification function
        function showRefreshNotification() {
            // Create notification element if it doesn't exist
            let notification = document.getElementById('refreshNotification');
            if (!notification) {
                notification = document.createElement('div');
                notification.id = 'refreshNotification';
                notification.className = 'refresh-notification';
                notification.innerHTML = `
                    <div class="notification-content">
                        <i class="bi bi-arrow-clockwise"></i>
                        <span>Data mungkin sudah diperbarui. Refresh untuk melihat perubahan terbaru.</span>
                        <button onclick="location.reload()" class="btn-refresh">Refresh</button>
                        <button onclick="this.parentElement.parentElement.remove()" class="btn-close">×</button>
                    </div>
                `;
                document.body.appendChild(notification);
            }

            notification.style.animation = 'slideInRight 0.3s ease';
        }

        // Export functionality enhancement
        function enhanceExportButton() {
            const exportBtn = document.querySelector('a[href*="export"]');
            if (exportBtn) {
                exportBtn.addEventListener('click', function(e) {
                    if (this.dataset.clicked) {
                        e.preventDefault();
                        return;
                    }

                    this.dataset.clicked = 'true';
                    const originalText = this.innerHTML;

                    // Show loading state
                    this.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Menyiapkan Export...';
                    this.classList.add('loading');

                    // Reset after 2 seconds (in case export takes time)
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.classList.remove('loading');
                        delete this.dataset.clicked;
                    }, 2000);
                });
            }
        }

        // Initialize export enhancement
        enhanceExportButton();
    </script>

    <style>
        /* Additional interactive styles */
        .highlighted {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
            border-color: #667eea !important;
        }

        .highlighted .enhanced-accordion-button {
            background: rgba(102, 126, 234, 0.05);
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.02);
            }

            100% {
                transform: scale(1);
            }
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .btn.loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .btn.loading .spin {
            animation: spin 1s linear infinite;
        }

        /* Refresh Notification */
        .refresh-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 1rem;
            max-width: 350px;
            z-index: 9999;
            transform: translateX(400px);
        }

        .notification-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .notification-content i {
            color: #667eea;
            font-size: 1.25rem;
        }

        .notification-content span {
            flex: 1;
            font-size: 0.875rem;
            color: #2d3748;
        }

        .btn-refresh {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-refresh:hover {
            background: #5a67d8;
        }

        .btn-close {
            background: none;
            border: none;
            color: #718096;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .btn-close:hover {
            background: rgba(0, 0, 0, 0.1);
            color: #2d3748;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Keyboard shortcut hint */
        .keyboard-hint {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .keyboard-hint.show {
            opacity: 1;
        }
    </style>
@endpush
