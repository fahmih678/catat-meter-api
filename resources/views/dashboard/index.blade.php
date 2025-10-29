@extends('layouts.main')

@section('title', 'Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<div class="container-fluid p-0">
    <!-- Welcome Section -->
    <div class="row mb-4 fade-in-up">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-2">Selamat Datang, {{ auth()->user()->name }}! 👋</h2>
                        <p class="text-muted mb-0">Dashboard Sistem Manajemen Meter Air - Catat Meter API</p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <span class="badge bg-success fs-6">
                            <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                            Sistem Online
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4 fade-in-up" style="animation-delay: 0.1s;">
            <div class="dashboard-card stat-card">
                <div class="stat-icon bg-primary bg-gradient">
                    <i class="bi bi-people text-white"></i>
                </div>
                <div class="stat-number text-primary">1,250</div>
                <div class="stat-label">Total Pelanggan</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4 fade-in-up" style="animation-delay: 0.2s;">
            <div class="dashboard-card stat-card">
                <div class="stat-icon bg-success bg-gradient">
                    <i class="bi bi-speedometer text-white"></i>
                </div>
                <div class="stat-number text-success">856</div>
                <div class="stat-label">Meter Aktif</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4 fade-in-up" style="animation-delay: 0.3s;">
            <div class="dashboard-card stat-card">
                <div class="stat-icon bg-warning bg-gradient">
                    <i class="bi bi-credit-card text-white"></i>
                </div>
                <div class="stat-number text-warning">92%</div>
                <div class="stat-label">Tingkat Pembayaran</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4 fade-in-up" style="animation-delay: 0.4s;">
            <div class="dashboard-card stat-card">
                <div class="stat-icon bg-info bg-gradient">
                    <i class="bi bi-graph-up text-white"></i>
                </div>
                <div class="stat-number text-info">+23%</div>
                <div class="stat-label">Pertumbuhan Bulanan</div>
            </div>
        </div>
    </div>

    <!-- Application Info and Recent Activity -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4 fade-in-up" style="animation-delay: 0.5s;">
            <div class="dashboard-card">
                <h5 class="card-title mb-4">
                    <i class="bi bi-info-circle me-2 text-primary"></i>
                    Informasi Aplikasi
                </h5>

                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td width="30%"><strong>Nama Aplikasi:</strong></td>
                                <td>Catat Meter API</td>
                            </tr>
                            <tr>
                                <td><strong>Versi:</strong></td>
                                <td>v1.0.0</td>
                            </tr>
                            <tr>
                                <td><strong>Framework:</strong></td>
                                <td>Laravel 10.0</td>
                            </tr>
                            <tr>
                                <td><strong>Database:</strong></td>
                                <td>MySQL</td>
                            </tr>
                            <tr>
                                <td><strong>Environment:</strong></td>
                                <td><span class="badge bg-warning">Development</span></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td width="30%"><strong>PHP Versi:</strong></td>
                                <td>8.2+</td>
                            </tr>
                            <tr>
                                <td><strong>API Status:</strong></td>
                                <td><span class="badge bg-success">Online</span></td>
                            </tr>
                            <tr>
                                <td><strong>Terakhir Update:</strong></td>
                                <td>{{ now()->format('d M Y, H:i') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Server Time:</strong></td>
                                <td>{{ now()->format('H:i:s') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Uptime:</strong></td>
                                <td>99.9%</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4 fade-in-up" style="animation-delay: 0.6s;">
            <div class="dashboard-card">
                <h5 class="card-title mb-4">
                    <i class="bi bi-lightning-charge me-2 text-warning"></i>
                    Quick Actions
                </h5>

                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary" data-bs-toggle="tooltip" title="Tambah pelanggan baru">
                        <i class="bi bi-person-plus me-2"></i>Tambah Pelanggan
                    </button>
                    <button class="btn btn-outline-success" data-bs-toggle="tooltip" title="Catat meter pelanggan">
                        <i class="bi bi-pencil-square me-2"></i>Catat Meter
                    </button>
                    <button class="btn btn-outline-info" data-bs-toggle="tooltip" title="Generate laporan">
                        <i class="bi bi-file-earmark-text me-2"></i>Generate Laporan
                    </button>
                    <button class="btn btn-outline-warning" data-bs-toggle="tooltip" title="Pantau pembayaran">
                        <i class="bi bi-cash-stack me-2"></i>Monitoring
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- System Status -->
    <div class="row mb-4">
        <div class="col-12 fade-in-up" style="animation-delay: 0.7s;">
            <div class="dashboard-card">
                <h5 class="card-title mb-4">
                    <i class="bi bi-heart-pulse me-2 text-danger"></i>
                    System Status
                </h5>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                            <span><i class="bi bi-database me-2"></i>Database</span>
                            <span class="badge bg-success">Healthy</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                            <span><i class="bi bi-server me-2"></i>API Server</span>
                            <span class="badge bg-success">Running</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                            <span><i class="bi bi-hdd me-2"></i>Storage</span>
                            <span class="badge bg-success">62%</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                            <span><i class="bi bi-memory me-2"></i>Memory</span>
                            <span class="badge bg-warning">78%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row">
        <div class="col-lg-6 mb-4 fade-in-up" style="animation-delay: 0.8s;">
            <div class="dashboard-card">
                <h5 class="card-title mb-4">
                    <i class="bi bi-clock-history me-2 text-info"></i>
                    Aktivitas Terkini
                </h5>

                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <div class="bg-primary bg-gradient rounded-circle p-2 text-white">
                                    <i class="bi bi-person-plus" style="font-size: 0.8rem;"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Pelanggan baru ditambahkan</h6>
                                <small class="text-muted">John Doe - 10 menit yang lalu</small>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item px-0">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <div class="bg-success bg-gradient rounded-circle p-2 text-white">
                                    <i class="bi bi-pencil-square" style="font-size: 0.8rem;"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Pencatatan meter berhasil</h6>
                                <small class="text-muted">Unit #1234 - 25 menit yang lalu</small>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item px-0">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <div class="bg-warning bg-gradient rounded-circle p-2 text-white">
                                    <i class="bi bi-credit-card" style="font-size: 0.8rem;"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Pembayaran tercatat</h6>
                                <small class="text-muted">Jane Smith - 1 jam yang lalu</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4 fade-in-up" style="animation-delay: 0.9s;">
            <div class="dashboard-card">
                <h5 class="card-title mb-4">
                    <i class="bi bi-shield-check me-2 text-success"></i>
                    Security Information
                </h5>

                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>Security Status:</strong> All systems secure
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted">Last Security Scan</small>
                        <div class="fw-semibold">2 hours ago</div>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">SSL Certificate</small>
                        <div class="fw-semibold text-success">Valid</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">Active Sessions</small>
                        <div class="fw-semibold">3 users</div>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Failed Attempts</small>
                        <div class="fw-semibold">0 (24h)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Update server time every second
    function updateServerTime() {
        const serverTimeElement = document.querySelector('td:contains("Server Time") + td');
        if (serverTimeElement) {
            serverTimeElement.textContent = new Date().toLocaleTimeString();
        }
    }

    setInterval(updateServerTime, 1000);

    // Quick action buttons
    document.querySelectorAll('.btn-outline-primary, .btn-outline-success, .btn-outline-info, .btn-outline-warning').forEach(button => {
        button.addEventListener('click', function() {
            // Placeholder for future functionality
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 p-3';
            toast.style.zIndex = '1050';
            toast.innerHTML = `
                <div class="toast show" role="alert">
                    <div class="toast-header">
                        <strong class="me-auto">Info</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">
                        Fitur ini akan segera tersedia!
                    </div>
                </div>
            `;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        });
    });
</script>
@endsection