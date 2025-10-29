@extends('layouts.main')

@section('title', 'User Detail - ' . $user->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('users') }}">User Management</a></li>
    <li class="breadcrumb-item active">{{ $user->name }}</li>
@endsection

@section('content')
<div class="container-fluid p-0">
    <!-- Page Header -->
    <div class="row mb-4 fade-in-up">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <h2 class="mb-1">{{ $user->name }}</h2>
                                <p class="text-muted mb-0">{{ $user->email }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-secondary me-2" onclick="window.history.back()">
                            <i class="bi bi-arrow-left me-2"></i>Back
                        </button>
                        @if($user->id !== auth()->id())
                        <button class="btn btn-danger" onclick="confirmDelete({{ $user->id }}, '{{ $user->name }}')">
                            <i class="bi bi-trash me-2"></i>Delete User
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- User Information -->
        <div class="col-lg-6 mb-4 fade-in-up" style="animation-delay: 0.1s;">
            <div class="dashboard-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person-badge me-2"></i>
                        User Information
                    </h5>
                </div>
                <div class="card-body">
                    <form id="userInfoForm">
                        @csrf
                        @method('PUT')
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" value="{{ $user->name }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="{{ $user->email }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" value="{{ $user->phone ?? '' }}">
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-primary" onclick="updateUserInfo()">
                                <i class="bi bi-check-circle me-2"></i>Update Information
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Role Management -->
        <div class="col-lg-6 mb-4 fade-in-up" style="animation-delay: 0.2s;">
            <div class="dashboard-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-shield-check me-2"></i>
                        Role Management
                    </h5>
                </div>
                <div class="card-body">
                    <form id="roleForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Assigned Roles</label>
                            <div class="mb-3">
                                @foreach($user->roles as $role)
                                    <span class="badge bg-info bg-opacity-10 text-info me-2">
                                        {{ $role->name }}
                                    </span>
                                @endforeach
                                @if($user->roles->isEmpty())
                                    <span class="text-muted">No roles assigned</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Available Roles</label>
                            <select class="form-select" name="roles[]" multiple size="4">
                                <option value="super_admin" {{ $user->hasRole('super_admin') ? 'selected' : '' }}>Super Admin</option>
                                <option value="admin" {{ $user->hasRole('admin') ? 'selected' : '' }}>Admin</option>
                                <option value="operator" {{ $user->hasRole('operator') ? 'selected' : '' }}>Operator</option>
                                <option value="viewer" {{ $user->hasRole('viewer') ? 'selected' : '' }}>Viewer</option>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple roles</small>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-primary" onclick="updateUserRoles()">
                                <i class="bi bi-shield-check me-2"></i>Update Roles
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Password Management -->
    <div class="row">
        <div class="col-12 mb-4 fade-in-up" style="animation-delay: 0.3s;">
            <div class="dashboard-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-key me-2"></i>
                        Password Management
                    </h5>
                </div>
                <div class="card-body">
                    <form id="passwordForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="password" required minlength="8">
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" name="password_confirmation" required minlength="8">
                                <small class="text-muted">Re-enter new password for confirmation</small>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showPassword" onchange="togglePasswordVisibility()">
                                <label class="form-check-label" for="showPassword">
                                    Show passwords
                                </label>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                <i class="bi bi-shield-check me-1"></i>
                                Password must contain at least 8 characters
                            </div>
                            <button type="button" class="btn btn-warning" onclick="updateUserPassword()">
                                <i class="bi bi-key me-2"></i>Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- User Activity -->
    <div class="row">
        <div class="col-12 mb-4 fade-in-up" style="animation-delay: 0.3s;">
            <div class="dashboard-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        User Activity
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="stat-card">
                                <div class="stat-value">{{ $user->created_at->format('M d, Y') }}</div>
                                <div class="stat-label">Created Date</div>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="stat-card">
                                <div class="stat-value">
                                    {{ $user->email_verified_at ? 'Verified' : 'Not Verified' }}
                                </div>
                                <div class="stat-label">Email Status</div>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="stat-card">
                                <div class="stat-value">{{ $user->roles->count() }}</div>
                                <div class="stat-label">Roles</div>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="stat-card">
                                <div class="stat-value">{{ $user->permissions->count() }}</div>
                                <div class="stat-label">Permissions</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Permissions List -->
    <div class="row">
        <div class="col-12 mb-4 fade-in-up" style="animation-delay: 0.4s;">
            <div class="dashboard-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-key me-2"></i>
                        Permissions
                    </h5>
                </div>
                <div class="card-body">
                    @if($user->permissions->count() > 0)
                        <div class="row">
                            @foreach($user->permissions->chunk(4) as $chunk)
                                <div class="col-md-3">
                                    @foreach($chunk as $permission)
                                        <div class="permission-item">
                                            <i class="bi bi-check-circle text-success me-2"></i>
                                            {{ $permission->name }}
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-info-circle me-2"></i>
                            No direct permissions assigned (user inherits permissions from roles)
                        </div>
                    @endif
                </div>
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
@endsection

@push('styles')
<style>
.avatar {
    width: 64px;
    height: 64px;
    font-size: 24px;
    font-weight: 600;
}

.stat-card {
    padding: 1.5rem;
    border-radius: 10px;
    background: #f8f9fa;
    transition: all 0.3s ease;
}

.stat-card:hover {
    background: #e9ecef;
    transform: translateY(-2px);
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    font-weight: 500;
}

.permission-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.permission-item:last-child {
    border-bottom: none;
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

function updateUserInfo() {
    const form = document.getElementById('userInfoForm');
    const formData = new FormData(form);
    formData.append('_method', 'PUT');

    fetch(`/users/{{ $user->id }}/update`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('User information updated successfully', 'success');
            // Refresh page after successful update
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message || 'Failed to update user', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while updating user', 'danger');
    });
}

function updateUserRoles() {
    const form = document.getElementById('roleForm');
    const formData = new FormData(form);

    fetch(`/users/{{ $user->id }}/role`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('User roles updated successfully', 'success');
            // Refresh page after successful update
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message || 'Failed to update roles', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while updating roles', 'danger');
    });
}

function updateUserPassword() {
    const form = document.getElementById('passwordForm');
    const formData = new FormData(form);

    // Client-side validation
    const password = formData.get('password');
    const passwordConfirmation = formData.get('password_confirmation');

    if (!password || !passwordConfirmation) {
        showNotification('Both password fields are required', 'danger');
        return;
    }

    if (password !== passwordConfirmation) {
        showNotification('Password and confirmation do not match', 'danger');
        return;
    }

    if (password.length < 8) {
        showNotification('Password must be at least 8 characters', 'danger');
        return;
    }

    formData.append('_method', 'PUT');

    fetch(`/users/{{ $user->id }}/password`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Password updated successfully', 'success');
            // Clear form after successful update
            form.reset();
        } else {
            showNotification(data.message || 'Failed to update password', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while updating password', 'danger');
    });
}

function togglePasswordVisibility() {
    const showPassword = document.getElementById('showPassword').checked;
    const passwordFields = ['password', 'password_confirmation'];

    passwordFields.forEach(fieldName => {
        const field = document.querySelector(`input[name="${fieldName}"]`);
        if (field) {
            field.type = showPassword ? 'text' : 'password';
        }
    });
}

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