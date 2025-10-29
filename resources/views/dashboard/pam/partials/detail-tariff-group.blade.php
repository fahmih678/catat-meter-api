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
                                            <th>Group Code</th>
                                            <th>Group Name</th>
                                            <th>Tiers</th>
                                            <th>Customers</th>
                                            <th>Avg Price/m³</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($tariffGroups as $group)
                                            <tr>
                                                <td><span class="badge bg-primary">{{ $group->code }}</span></td>
                                                <td>{{ $group->name }}</td>
                                                <td>{{ $group->tiers_count ?? count($group->tiers ?? []) }} tiers</td>
                                                <td>{{ number_format($group->customers_count ?? 0) }}</td>
                                                <td>Rp {{ number_format($group->average_price ?? 0, 0, ',', '.') }}</td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary"
                                                            onclick="editTariffGroup({{ $group->id }})">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-outline-success"
                                                            onclick="manageTiers({{ $group->id }})">
                                                            <i class="bi bi-layers"></i>
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
