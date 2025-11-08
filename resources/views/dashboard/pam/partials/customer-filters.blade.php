<div class="dashboard-card">
    <form method="GET" action="{{ route('pam.customers', $pam->id) }}" id="filterForm" autocomplete="off">
        <div class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label fw-semibold">
                    <i class="bi bi-search me-1"></i>Cari Pelanggan
                </label>
                <input type="text" class="form-control" id="search" name="search"
                    value="{{ e($search) }}"
                    placeholder="Nama, Nomor Pelanggan, Telepon, atau Alamat..."
                    maxlength="255">
            </div>
            <div class="col-md-3">
                <label for="filter_area_id" class="form-label fw-semibold">
                    <i class="bi bi-geo-alt me-1"></i>Filter Area
                </label>
                <select class="form-select" id="filter_area_id" name="area_id">
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