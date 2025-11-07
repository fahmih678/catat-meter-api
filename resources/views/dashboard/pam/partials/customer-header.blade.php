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