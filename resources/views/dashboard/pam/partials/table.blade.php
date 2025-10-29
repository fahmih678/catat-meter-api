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
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="viewPam({{ $pam->id }})">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning" onclick="editPam({{ $pam->id }})">
                            <i class="bi bi-pencil"></i>
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