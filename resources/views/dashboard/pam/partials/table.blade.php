<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>No</th>
                <th>Name</th>
                <th>Email & Phone</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pams as $pam)
                <tr>
                    <td>{{ $loop->iteration + ($pams->currentPage() - 1) * $pams->perPage() }}</td>
                    <td>{{ $pam->name }} ({{ $pam->code }})</td>
                    <td>
                        <small>
                            @if ($pam->email)
                                {{ $pam->email }}<br>
                            @endif
                            @if ($pam->phone)
                                {{ $pam->phone }}<br>
                            @endif
                        </small>
                    </td>
                    <td>
                        @if ($pam->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-warning">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <small class="text-muted">{{ $pam->created_at->format('M d, Y') }}</small>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="viewPam({{ $pam->id }})" title="View Details">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning me-1" onclick="editPam({{ $pam->id }})" title="Edit PAM">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-{{ $pam->is_active ? 'outline-secondary' : 'outline-success' }} me-1"
                                onclick="togglePamStatus({{ $pam->id }}, {{ $pam->is_active ? 'true' : 'false' }})"
                                title="{{ $pam->is_active ? 'Deactivate' : 'Activate' }} PAM">
                            <i class="bi bi-{{ $pam->is_active ? 'pause' : 'play' }}"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger"
                                onclick="deletePam({{ $pam->id }}, '{{ $pam->name }}')"
                                title="Delete PAM">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            @if($search ?? '')
                                No PAMs found matching "{{ $search }}"
                            @else
                                No PAMs found
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>