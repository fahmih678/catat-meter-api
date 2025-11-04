<div class="tab-pane fade" id="areas" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Service Areas Management</h5>
        <button class="btn btn-primary btn-sm" onclick="showCreateAreaModal()">
            <i class="bi bi-plus-circle me-2"></i>Add Area
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Area Code</th>
                    <th>Area Name</th>
                    <th>Customers</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($areas as $area)
                    <tr>
                        <td><span class="badge bg-primary">{{ $area->code }}</span></td>
                        <td>{{ $area->name }}</td>
                        <td>{{ number_format($area->customers_count ?? 0) }}</td>
                        <td>
                            <div class="btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="editArea({{ $area->id }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="deleteArea({{ $area->id }}, '{{ $area->name }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-geo-alt fs-1 d-block mb-2"></i>
                                No service areas found for this PAM
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
