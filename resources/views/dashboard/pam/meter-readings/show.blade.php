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
                            <table class="table table-hover">
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
                                        <th><i class="bi bi-gear me-1"></i>Aksi</th>
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
                                            <td>
                                                <div class="" role="group">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-primary edit-meter-reading"
                                                        data-id="{{ $reading->id }}"
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
                                                            "photo_url": "{{ $reading->photo_url ?? '' }}"
                                                        }'
                                                        title="Edit Pembacaan">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-warning change-status"
                                                        data-id="{{ $reading->id }}"
                                                        data-reading='{
                                                            "customer_name": "{{ $reading->meter->customer->name ?? '-' }}",
                                                            "meter_number": "{{ $reading->meter->meter_number ?? '-' }}",
                                                            "volume_usage": {{ $reading->volume_usage ?? 0 }},
                                                            "status": "{{ $reading->status }}"
                                                        }'
                                                        title="Ubah Status">
                                                        <i class="bi bi-patch-check"></i>
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
    @include('dashboard.pam.meter-readings.modals.change-status')

@endsection
