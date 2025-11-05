<div class="tab-pane fade" id="fees" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Fixed Fees Management</h5>
        <button class="btn btn-success btn-sm" onclick="showCreateFixedFeeModal()">
            <i class="bi bi-plus-circle me-2"></i>Add Fixed Fee
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Group Name</th>
                    <th>Fee Name</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fixedFees as $fee)
                    <tr>
                        <td><span class="badge bg-warning">{{ $fee->tariffGroup->name }}</span></td>
                        <td>{{ $fee->name }}</td>

                        <td>Rp {{ number_format($fee->amount, 0, ',', '.') }}</td>
                        <td>
                            @if ($fee->status === 'active' || (isset($fee->is_active) && $fee->is_active))
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-warning">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="editFixedFee({{ $fee->id }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="deleteFixedFee({{ $fee->id }}, '{{ $fee->name }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-cash-stack fs-1 d-block mb-2"></i>
                                No fixed fees found for this PAM
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
