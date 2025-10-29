@extends('layouts.main')

@section('title', 'User Management')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">User Management</li>
@endsection

@section('content')
<div class="container-fluid p-0">
    <!-- Page Header -->
    <div class="row mb-4 fade-in-up">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="row align-items-center">
                    <div class="col">
                        <h2 class="mb-2">
                            <i class="bi bi-people-fill me-2 text-primary"></i>
                            User Management
                        </h2>
                        <p class="text-muted mb-0">Manage user accounts and role assignments</p>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bi bi-plus-circle me-2"></i>Add User
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="row mb-4">
        <div class="col-12 fade-in-up" style="animation-delay: 0.1s;">
            <div class="dashboard-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-ul me-2"></i>
                        All Users
                    </h5>
                    <div class="d-flex gap-2">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <input type="text" class="form-control" id="searchUser" placeholder="Search users...">
                            <button class="btn btn-outline-secondary">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Roles</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td>
                                    <span class="badge bg-secondary">#{{ $user->id }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-medium">{{ $user->name }}</div>
                                            @if($user->id === auth()->id())
                                                <small class="text-muted">(You)</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="mailto:{{ $user->email }}" class="text-decoration-none">
                                        {{ $user->email }}
                                    </a>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $user->phone ?? '-' }}</span>
                                </td>
                                <td>
                                    @foreach($user->roles as $role)
                                        <span class="badge bg-info bg-opacity-10 text-info mb-1">{{ $role->name }}</span>
                                    @endforeach
                                    @if($user->roles->isEmpty())
                                        <span class="text-muted">No roles</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->email_verified_at)
                                        <span class="badge bg-success bg-opacity-10 text-success">
                                            <i class="bi bi-check-circle me-1"></i>Verified
                                        </span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-10 text-warning">
                                            <i class="bi bi-clock me-1"></i>Pending
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $user->created_at->format('M d, Y') }}</small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('users.detail', $user->id) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button class="btn btn-outline-danger btn-sm" onclick="confirmDelete({{ $user->id }}, '{{ $user->name }}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted">
                        Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} users
                    </div>
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Roles</label>
                            <select class="form-select" name="roles[]" multiple>
                                <option value="super_admin">Super Admin</option>
                                <option value="admin">Admin</option>
                                <option value="operator">Operator</option>
                                <option value="viewer">Viewer</option>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple roles</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addUser()">Create User</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="deleteUserName"></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete User</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.avatar {
    width: 32px;
    height: 32px;
    font-size: 14px;
    font-weight: 600;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: var(--bs-gray-700);
}

.table td {
    vertical-align: middle;
}

.badge {
    font-size: 0.75rem;
}

.btn-group-sm > .btn {
    padding: 0.25rem 0.5rem;
}

.modal-content {
    border-radius: 15px;
    border: none;
}

.modal-header {
    border-bottom: 1px solid #f8f9fa;
    border-radius: 15px 15px 0 0;
}

.modal-footer {
    border-top: 1px solid #f8f9fa;
    border-radius: 0 0 15px 15px;
}
</style>
@endpush

@push('scripts')
<script>
let deleteUserId = null;

function confirmDelete(userId, userName) {
    deleteUserId = userId;
    document.getElementById('deleteUserName').textContent = userName;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (deleteUserId) {
        // Here you would implement the delete functionality
        console.log('Delete user:', deleteUserId);
        showNotification('User deletion functionality coming soon', 'warning');

        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
        modal.hide();

        deleteUserId = null;
    }
});

function addUser() {
    const form = document.getElementById('addUserForm');
    const formData = new FormData(form);

    // Here you would implement the add user functionality
    console.log('Add user:', Object.fromEntries(formData));
    showNotification('Add user functionality coming soon', 'info');

    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
    modal.hide();

    // Reset form
    form.reset();
}

// Search functionality
document.getElementById('searchUser').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

function showNotification(message, type = 'info') {
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    const container = document.createElement('div');
    container.innerHTML = toastHtml;
    container.style.position = 'fixed';
    container.style.top = '20px';
    container.style.right = '20px';
    container.style.zIndex = '9999';

    document.body.appendChild(container);

    const toast = new bootstrap.Toast(container.querySelector('.toast'));
    toast.show();

    setTimeout(() => {
        container.remove();
    }, 3000);
}
</script>
@endpush