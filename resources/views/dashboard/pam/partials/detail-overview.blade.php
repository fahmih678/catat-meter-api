<div class="tab-pane fade show active" id="overview" role="tabpanel">
    <div class="row">
        <div class="col-md-6">
            <h5 class="mb-3"><i class="bi bi-info-circle me-2"></i>PAM Information</h5>
            <table class="table table-borderless">
                <tr>
                    <td><strong>Code:</strong></td>
                    <td>{{ $pam->code }}</td>
                </tr>
                <tr>
                    <td><strong>Phone:</strong></td>
                    <td>{{ $pam->phone ?: 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td>{{ $pam->email ?: 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td>
                        @if ($pam->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-warning">Inactive</span>
                        @endif
                    </td>
                </tr>
                @if ($pam->created_at)
                    <tr>
                        <td><strong>Created:</strong></td>
                        <td>{{ $pam->created_at->format('M d, Y H:i') }}</td>
                    </tr>
                @endif
                @if ($pam->updated_at)
                    <tr>
                        <td><strong>Last Updated:</strong></td>
                        <td>{{ $pam->updated_at->format('M d, Y H:i') }}</td>
                    </tr>
                @endif
            </table>
        </div>
        <div class="col-md-6">
            <h5 class="mb-3"><i class="bi bi-graph-up me-2"></i>Performance Metrics</h5>
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span>Total Customers</span>
                    <strong>{{ number_format($statistics['total_customers'] ?? 0) }}</strong>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-success"
                        style="width: {{ min(100, $statistics['total_customers'] / 100) }}%">
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span>Active Customers</span>
                    <strong>{{ number_format($statistics['active_customers'] ?? 0) }}</strong>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-info"
                        style="width: {{ ($statistics['total_customers'] ?? 1) > 0 ? (($statistics['active_customers'] ?? 0) / ($statistics['total_customers'] ?? 1)) * 100 : 0 }}%">
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span>Total Meters</span>
                    <strong>{{ number_format($statistics['total_meters'] ?? 0) }}</strong>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-warning"
                        style="width: {{ min(100, ($statistics['total_meters'] ?? 0) / 50) }}%">
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span>Active Meters</span>
                    <strong>{{ number_format($statistics['active_meters'] ?? 0) }}</strong>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-primary"
                        style="width: {{ ($statistics['total_meters'] ?? 1) > 0 ? (($statistics['active_meters'] ?? 0) / ($statistics['total_meters'] ?? 1)) * 100 : 0 }}%">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
