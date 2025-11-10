<div class="dashboard-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            <i class="bi bi-people me-2"></i>Daftar Pelanggan
            @if ($search || $areaId || $status)
                <small class="text-muted">({{ $customers->count() }} dari {{ $customers->total() }}
                    hasil)</small>
            @else
                <small class="text-muted">({{ $customers->total() }} pelanggan)</small>
            @endif
        </h5>
        <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-success" onclick="exportCustomers()">
                <i class="bi bi-download me-1"></i>Export
            </button>
            <button class="btn btn-outline-primary" onclick="refreshTable()">
                <i class="bi bi-arrow-clockwise me-1"></i>Refresh
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover" id="customersTable">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>No. Pelanggan</th>
                    <th>Nama</th>
                    <th>Area</th>
                    <th>Grup Tarif</th>
                    <th> Meter</th>
                    <th>Pengguna</th>
                    <th>Kontak</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $index => $customer)
                    <tr>
                        <td>
                            <span class="badge text-dark">{{ $customers->firstItem() + $index }}</span>
                        </td>
                        <td>
                            <span class="badge bg-info text-dark">{{ $customer->customer_number }}</span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar bg-light text-dark rounded-circle me-2"
                                    style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-person fs-6"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $customer->name }}</div>
                                    @if ($customer->address)
                                        <small
                                            class="text-muted d-block">{{ Str::limit($customer->address, 50) }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($customer->area)
                                <span class="badge bg-secondary">{{ $customer->area->name }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if ($customer->tariffGroup)
                                <span class="badge bg-warning text-dark">{{ $customer->tariffGroup->name }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if ($customer->meters && $customer->meters->count() > 0)
                                <span class="badge bg-info" title="{{ $customer->meters->count() }} meter terpasang">
                                    <i class="bi bi-speedometer2 me-1"></i>{{ $customer->meters->count() }}
                                </span>
                            @else
                                <span class="text-muted">
                                    <i class="bi bi-speedometer2"></i>
                                </span>
                            @endif
                        </td>
                        <td>
                            @if ($customer->user)
                                <span class="badge bg-success">{{ $customer->user->name }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <div>
                                @if ($customer->phone)
                                    <small class="d-block">{{ $customer->phone }}</small>
                                @else
                                    <small class="text-muted">-</small>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if ($customer->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-warning">Tidak Aktif</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="viewCustomer({{ $customer->id }})"
                                    title="Lihat Detail">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-outline-warning" onclick="editCustomer({{ $customer->id }})"
                                    title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-info" onclick="viewCustomerMeters({{ $customer->id }})"
                                    title="Kelola Meter">
                                    <i class="bi bi-speedometer2"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="deleteCustomer({{ $customer->id }}, '{{ e($customer->name) }}')"
                                    title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-people fs-1 d-block mb-2"></i>
                                @if ($search || $areaId || $status)
                                    Tidak ada pelanggan yang cocok dengan filter yang dipilih
                                @else
                                    Belum ada pelanggan untuk PAM ini
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if ($customers->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted">
                Menampilkan {{ $customers->firstItem() }} hingga {{ $customers->lastItem() }} dari
                {{ $customers->total() }} pelanggan
            </div>
            {{ $customers->links('pagination::bootstrap-4') }}
        </div>
    @endif
</div>

{{-- JavaScript for customer table functionality --}}
<script>
    // Set PAM ID for JavaScript
    @php
        $pamId = request()->route('pamId') ?? (request()->segment(3) ?? (isset($pam) ? $pam->id : 0));
    @endphp
    window.currentPamId = {{ $pamId }};

    // Debug: Log the PAM ID
    console.log('PAM ID set to:', window.currentPamId);
    console.log('Current URL:', window.location.pathname);
</script>

<script src="{{ asset('js/customer-table.js') }}"></script>
<script src="{{ asset('js/customer-modals.js') }}"></script>
