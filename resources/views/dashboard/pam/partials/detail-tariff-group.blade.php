<div class="tab-pane fade" id="tariffs" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0"><i class="bi bi-tags me-2"></i>Tariff Groups Management</h5>
        <button class="btn btn-warning btn-sm" onclick="showCreateTariffGroupModal()">
            <i class="bi bi-plus-circle me-2"></i>Add Tariff Group
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Group Name</th>
                    <th>Description</th>
                    <th>Tiers</th>
                    <th>Customers</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tariffGroups as $group)
                    {{-- {{ dd($group) }} --}}
                    <tr data-tariff-group-id="{{ $group->id }}">
                        <td><span class="badge bg-warning">{{ $group->name }}</span></td>
                        <td>{{ $group->description }}</td>
                        <td>{{ $group->tariff_tiers_count }} tiers</td>
                        <td>{{ number_format($group->customers_count ?? 0) }}</td>
                        <td>
                            @if ($group->is_active == true)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-warning">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="editTariffGroup({{ $group->id }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="deleteTariffGroup({{ $group->id }}, '{{ $group->name }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-tags fs-1 d-block mb-2"></i>
                                No tariff groups found for this PAM
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
