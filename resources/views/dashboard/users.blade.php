@extends('layouts.main')

@section('title', 'User Management')

@section('breadcrumb')
    <li class="breadcrumb-item active">User Management</li>
@endsection

@push('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="current-user-id" content="{{ auth()->id() }}">
@endpush

@section('content')
    <div class="container-fluid p-0">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="row align-items-center">
                        <div class="col">
                            <h2 class="mb-2">
                                <i class="bi bi-people-fill me-2"></i>
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
            <div class="col-12" style="animation-delay: 0.1s;">
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
                                @foreach ($users as $user)
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">#{{ $user->id }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div
                                                    class="avatar avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <div class="fw-medium">{{ $user->name }}</div>
                                                    @if ($user->id === auth()->id())
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
                                            @foreach ($user->roles as $role)
                                                <span
                                                    class="badge bg-info bg-opacity-10 text-info mb-1">{{ $role->name }}</span>
                                            @endforeach
                                            @if ($user->roles->isEmpty())
                                                <span class="text-muted">No roles</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input status-toggle" type="checkbox"
                                                    id="statusToggle{{ $user->id }}" data-user-id="{{ $user->id }}"
                                                    {{ $user->is_active ? 'checked' : '' }}
                                                    {{ $user->id == auth()->id() ? 'disabled' : '' }}>
                                                <label class="form-check-label" for="statusToggle{{ $user->id }}">
                                                    @if ($user->is_active)
                                                        <span class="badge bg-success bg-opacity-10 text-success">
                                                            <i class="bi bi-check-circle me-1"></i>Active
                                                        </span>
                                                    @else
                                                        <span class="badge bg-danger bg-opacity-10 text-danger">
                                                            <i class="bi bi-x-circle me-1"></i>Inactive
                                                        </span>
                                                    @endif
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $user->created_at->format('M d, Y') }}</small>
                                        </td>
                                        <td>
                                            <div>
                                                <button class="btn btn-outline-warning btn-sm"
                                                    onclick="editUser({{ $user->id }}, '{{ $user->name }}', '{{ $user->email }}', '{{ $user->phone ?? '' }}', '{{ $user->roles->pluck('name')->implode(',') }}')">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm ms-1"
                                                    onclick="confirmDelete({{ $user->id }}, '{{ $user->name }}')">
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
                                    <option value="superadmin">Super Admin</option>
                                    <option value="admin">Admin</option>
                                    <option value="catat_meter">Catat Meter</option>
                                    <option value="loket">Loket</option>
                                    <option value="customer">Customer</option>
                                </select>
                                <small class="text-muted">Hold Ctrl/Cmd to select multiple roles</small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="submitUserBtn" onclick="addUser()">Create
                            User</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editUserForm">
                            @csrf
                            <input type="hidden" name="user_id" id="editUserId">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" id="editUserName" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="editUserEmail" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" id="editUserPhone">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Roles</label>
                                <select class="form-select" name="roles[]" id="editUserRoles" multiple>
                                    <option value="super_admin">Super Admin</option>
                                    <option value="admin">Admin</option>
                                    <option value="catat_meter">Catat Meter</option>
                                    <option value="loket">Loket</option>
                                    <option value="customer">Customer</option>

                                </select>
                                <small class="text-muted">Hold Ctrl/Cmd to select multiple roles</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">
                                    <input type="checkbox" id="editUserPasswordToggle"> Change Password
                                </label>
                                <div id="passwordFields" style="display: none; margin-top: 10px;">
                                    <div class="mb-2">
                                        <label class="form-label">New Password</label>
                                        <input type="password" class="form-control" name="password"
                                            id="editUserPassword">
                                        <small class="text-muted">Minimum 8 characters</small>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Confirm Password</label>
                                        <input type="password" class="form-control" name="password_confirmation"
                                            id="editUserPasswordConfirmation">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="updateUserBtn" onclick="updateUser()">Update
                            User</button>
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

        .btn-group-sm>.btn {
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

        /* Custom styles for status toggle */
        .form-check {
            min-height: auto;
            margin-bottom: 0;
        }

        .form-check-input.status-toggle {
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        .form-check-input.status-toggle:checked {
            background-color: #198754;
            border-color: #198754;
        }

        .form-check-input.status-toggle:not(:checked) {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .form-check-input.status-toggle:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        .form-check-label {
            cursor: pointer;
            margin-left: 0.5rem;
        }

        .form-check-label .badge {
            margin-left: 0.5rem;
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
            try {
                if (deleteUserId) {
                    const deleteBtn = this;
                    const originalText = deleteBtn.innerHTML;

                    // Get CSRF token
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    if (!csrfToken) {
                        showNotification('Security token not found', 'danger');
                        return;
                    }

                    // Show loading state
                    deleteBtn.disabled = true;
                    deleteBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Deleting...';

                    fetch(`/users/${deleteUserId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success || data.message.includes('successfully')) {
                                showNotification(data.message || 'User deleted successfully', 'success');

                                // Remove user row from table
                                const userRow = document.querySelector(
                                    `tr:has(button[onclick="confirmDelete(${deleteUserId}"])`);
                                if (userRow) {
                                    userRow.style.transition = 'opacity 0.3s';
                                    userRow.style.opacity = '0';
                                    setTimeout(() => userRow.remove(), 300);
                                }
                                // Reload page to show new user
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                showNotification(data.message || 'Failed to delete user', 'danger');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('An error occurred while deleting the user', 'danger');
                        })
                        .finally(() => {
                            // Close modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                            if (modal) {
                                modal.hide();
                            }

                            // Restore button state
                            deleteBtn.disabled = false;
                            deleteBtn.innerHTML = originalText;
                            deleteUserId = null;
                        });
                }
            } catch (error) {
                console.error('Delete function error:', error);
                showNotification('An unexpected error occurred', 'danger');
            }
        });

        function addUser() {
            try {
                const form = document.getElementById('addUserForm');
                const submitBtn = document.getElementById('submitUserBtn');

                // Validate form and button exist
                if (!form) {
                    console.error('Add User form not found');
                    showNotification('Form not found', 'danger');
                    return;
                }

                if (!submitBtn) {
                    console.error('Submit button not found');
                    showNotification('Submit button not found', 'danger');
                    return;
                }

                const originalText = submitBtn.innerHTML;

                // Validate form
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Creating...';

                const formData = new FormData(form);

                // Get CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (!csrfToken) {
                    throw new Error('CSRF token not found');
                }

                fetch('/users', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message || 'User created successfully', 'success');

                            // Close modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
                            if (modal) {
                                modal.hide();
                            }

                            // Reset form
                            form.reset();

                            // Reload page to show new user
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            // Handle validation errors
                            if (data.errors) {
                                let errorMessage = 'Validation failed:\n';
                                Object.keys(data.errors).forEach(key => {
                                    errorMessage += `- ${data.errors[key].join(', ')}\n`;
                                });
                                showNotification(errorMessage, 'danger');
                            } else {
                                showNotification(data.message || 'Failed to create user', 'danger');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred while creating the user', 'danger');
                    })
                    .finally(() => {
                        // Restore button state
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    });

            } catch (error) {
                console.error('AddUser function error:', error);
                showNotification('An unexpected error occurred', 'danger');
            }
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

        // Edit User functionality
        let currentEditingUserId = null;

        function editUser(userId, userName, userEmail, userPhone, userRoles) {
            currentEditingUserId = userId;

            // Populate form fields
            document.getElementById('editUserId').value = userId;
            document.getElementById('editUserName').value = userName;
            document.getElementById('editUserEmail').value = userEmail;
            document.getElementById('editUserPhone').value = userPhone;

            // Set roles
            const rolesSelect = document.getElementById('editUserRoles');
            const rolesArray = userRoles ? userRoles.split(',') : [];

            // Clear all selections first
            Array.from(rolesSelect.options).forEach(option => {
                option.selected = false;
            });

            // Select the user's roles
            rolesArray.forEach(role => {
                const option = Array.from(rolesSelect.options).find(opt => opt.value === role.trim());
                if (option) {
                    option.selected = true;
                }
            });

            // Reset password fields
            document.getElementById('editUserPasswordToggle').checked = false;
            document.getElementById('passwordFields').style.display = 'none';
            document.getElementById('editUserPassword').value = '';
            document.getElementById('editUserPasswordConfirmation').value = '';

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            modal.show();
        }

        // Toggle password fields visibility
        document.getElementById('editUserPasswordToggle')?.addEventListener('change', function() {
            const passwordFields = document.getElementById('passwordFields');
            if (this.checked) {
                passwordFields.style.display = 'block';
            } else {
                passwordFields.style.display = 'none';
            }
        });

        function updateUser() {
            try {
                const form = document.getElementById('editUserForm');
                const submitBtn = document.getElementById('updateUserBtn');
                const userId = document.getElementById('editUserId').value;

                if (!form || !submitBtn) {
                    console.error('Edit form or button not found');
                    showNotification('Form not found', 'danger');
                    return;
                }

                // Validate form
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                // Check password fields if password change is enabled
                const passwordToggle = document.getElementById('editUserPasswordToggle');
                if (passwordToggle.checked) {
                    const password = document.getElementById('editUserPassword').value;
                    const passwordConfirmation = document.getElementById('editUserPasswordConfirmation').value;

                    if (password && password !== passwordConfirmation) {
                        showNotification('Password and confirmation do not match', 'danger');
                        return;
                    }

                    if (password && password.length < 8) {
                        showNotification('Password must be at least 8 characters', 'danger');
                        return;
                    }
                }

                const originalText = submitBtn.innerHTML;

                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Updating...';

                const formData = new FormData(form);
                formData.append('_method', 'PUT');

                // Get CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (!csrfToken) {
                    throw new Error('CSRF token not found');
                }

                fetch(`/users/${userId}/update`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            if (response.status === 401) {
                                showNotification('Session expired. Please refresh the page and try again.', 'warning');
                                setTimeout(() => location.reload(), 2000);
                                throw new Error('Unauthenticated');
                            }
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message || 'User updated successfully', 'success');

                            // Close modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
                            if (modal) {
                                modal.hide();
                            }

                            // Reload page to show updated user
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            // Handle validation errors
                            if (data.errors) {
                                let errorMessage = 'Validation failed:\n';
                                Object.keys(data.errors).forEach(key => {
                                    errorMessage += `- ${data.errors[key].join(', ')}\n`;
                                });
                                showNotification(errorMessage, 'danger');
                            } else {
                                showNotification(data.message || 'Failed to update user', 'danger');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        if (error.message !== 'Unauthenticated') {
                            showNotification('An error occurred while updating the user', 'danger');
                        }
                    })
                    .finally(() => {
                        // Restore button state
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    });

            } catch (error) {
                console.error('UpdateUser function error:', error);
                showNotification('An unexpected error occurred', 'danger');
            }
        }

        // Debug function to check if all required elements are loaded
        function debugUserManagement() {
            console.log('=== User Management Debug ===');

            // Check if forms exist
            const addForm = document.getElementById('addUserForm');
            console.log('Add User Form:', addForm ? 'Found' : 'NOT FOUND');

            const submitBtn = document.getElementById('submitUserBtn');
            console.log('Submit Button:', submitBtn ? 'Found' : 'NOT FOUND');

            const deleteBtn = document.getElementById('confirmDeleteBtn');
            console.log('Delete Button:', deleteBtn ? 'Found' : 'NOT FOUND');

            // Check CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            console.log('CSRF Token:', csrfToken ? 'Found' : 'NOT FOUND');

            // Check modals
            const addModal = document.getElementById('addUserModal');
            console.log('Add Modal:', addModal ? 'Found' : 'NOT FOUND');

            const editModal = document.getElementById('editUserModal');
            console.log('Edit Modal:', editModal ? 'Found' : 'NOT FOUND');

            const deleteModal = document.getElementById('deleteModal');
            console.log('Delete Modal:', deleteModal ? 'Found' : 'NOT FOUND');

            // Check Bootstrap
            if (typeof bootstrap !== 'undefined') {
                console.log('Bootstrap: Loaded');
            } else {
                console.error('Bootstrap: NOT FOUND');
            }

            console.log('=== End Debug ===');
        }

        // Status toggle functionality
        function toggleUserStatus(userId, currentStatus) {
            try {
                const toggle = document.querySelector(`#statusToggle${userId}`);
                const label = toggle.nextElementSibling;
                const csrfToken = document.querySelector('meta[name="csrf-token"]');

                if (!csrfToken) {
                    showNotification('Security token not found', 'danger');
                    return;
                }

                if (!toggle || !label) {
                    showNotification('Toggle elements not found', 'danger');
                    return;
                }

                // Show loading state
                toggle.disabled = true;
                const originalChecked = toggle.checked;

                fetch(`/users/${userId}/toggle-status`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Update UI based on new status from server
                            const isActive = data.data.is_active;

                            console.log('Toggle response:', {
                                userId: userId,
                                isActive: isActive,
                                originalChecked: originalChecked
                            });

                            // Force update toggle state
                            toggle.checked = isActive;

                            // Update badge with proper HTML structure
                            const badgeContainer = label;
                            if (badgeContainer) {
                                if (isActive) {
                                    badgeContainer.innerHTML =
                                        '<span class="badge bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle me-1"></i>Active</span>';
                                } else {
                                    badgeContainer.innerHTML =
                                        '<span class="badge bg-danger bg-opacity-10 text-danger"><i class="bi bi-x-circle me-1"></i>Inactive</span>';
                                }
                            }

                            showNotification(data.message, 'success');
                        } else {
                            // Revert toggle state on failure
                            toggle.checked = originalChecked;
                            showNotification(data.message || 'Failed to update user status', 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Toggle error:', error);
                        // Revert toggle state on error
                        toggle.checked = originalChecked;
                        showNotification('An error occurred while updating user status', 'danger');
                    })
                    .finally(() => {
                        // Re-enable toggle (except for own account)
                        // Check if this is the current user's own account
                        const currentUserIdElement = document.querySelector('meta[name="current-user-id"]');
                        const currentUserId = currentUserIdElement ? parseInt(currentUserIdElement.getAttribute(
                            'content')) : null;

                        if (userId !== currentUserId) {
                            toggle.disabled = false;
                        }
                    });

            } catch (error) {
                console.error('Toggle status function error:', error);
                showNotification('An unexpected error occurred', 'danger');
            }
        }

        // Add event listeners to all toggle switches
        document.addEventListener('DOMContentLoaded', function() {
            // Debug user management elements
            debugUserManagement();

            // Test addUser function binding
            if (typeof addUser === 'function') {
                console.log('addUser function: Available');
            } else {
                console.error('addUser function: NOT AVAILABLE');
            }

            // Initialize toggle switches
            const toggleSwitches = document.querySelectorAll('.status-toggle');
            console.log(`Found ${toggleSwitches.length} toggle switches`);

            toggleSwitches.forEach((toggle, index) => {
                const userId = parseInt(toggle.getAttribute('data-user-id'));
                console.log(
                    `Initializing toggle ${index + 1} for user ${userId}, current state: ${toggle.checked}`
                );

                toggle.addEventListener('change', function(e) {
                    e.preventDefault();
                    const userId = parseInt(this.getAttribute('data-user-id'));
                    const newState = this.checked;

                    console.log(`Toggle changed for user ${userId}, new state: ${newState}`);

                    // Call the toggle function
                    toggleUserStatus(userId, newState);
                });
            });
        });
    </script>
@endpush
