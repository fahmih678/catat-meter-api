<div class="tab-pane fade" id="tiers" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0"><i class="bi bi-layers me-2"></i>Tariff Tiers Management</h5>
        <button class="btn btn-info btn-sm" onclick="showCreateTariffTierModal()">
            <i class="bi bi-plus-circle me-2"></i>Add Tariff Tier
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Group Name</th>
                    <th>Description</th>
                    <th>Range (m³)</th>
                    <th>Amount</th>
                    <th>Effectivity Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tariffTiers as $tier)
                    <tr>
                        <td><span class="badge bg-warning">{{ $tier->tariffGroup->name ?? 'N/A' }}</span></td>
                        <td>{{ $tier->description ?? '-' }}</td>
                        <td>{{ number_format($tier->meter_min, 2) }} - {{ number_format($tier->meter_max, 2) }}</td>
                        <td>Rp {{ number_format($tier->amount, 0, ',', '.') }}</td>
                        <td>{{ $tier->effective_from?->format('d M y') }}
                            {{ $tier->effective_to ? ' to ' . $tier->effective_to->format('d M Y') : '' }}</td>
                        <td>
                            @if ($tier->is_active == true)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-warning">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="editTariffTier({{ $tier->id }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="deleteTariffTier({{ $tier->id }}, '{{ $tier->tariffGroup->name ?? 'Tariff Tier' }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-layers fs-1 d-block mb-2"></i>
                                No tariff tiers found for this PAM
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
