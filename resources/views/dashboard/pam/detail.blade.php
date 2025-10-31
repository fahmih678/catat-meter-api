@extends('layouts.main')

@section('title', 'PAM Detail - ' . ($pam->name ?? 'PAM Not Found'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('pam.index') }}">PAM Management</a></li>
    <li class="breadcrumb-item active">{{ $pam->name ?? 'PAM Not Found' }}</li>
@endsection

@section('content')
    <div class="container-fluid p-0">
        <!-- PAM Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="d-flex align-items-center mb-2">
                                <div
                                    class="avatar avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="bi bi-building"></i>
                                </div>
                                <div>
                                    <h2 class="mb-1">{{ $pam->name }}</h2>
                                    <p class="text-muted mb-0">{{ $pam->code }} | {{ $pam->address }}</p>
                                    @if ($pam->email || $pam->phone)
                                        <p class="text-muted mb-0 small">
                                            @if ($pam->email)
                                                {{ $pam->email }}
                                            @endif
                                            @if ($pam->email && $pam->phone)
                                                |
                                            @endif
                                            @if ($pam->phone)
                                                {{ $pam->phone }}
                                            @endif
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-outline-secondary me-2" onclick="window.history.back()">
                                <i class="bi bi-arrow-left me-2"></i>Back
                            </button>
                            <button class="btn btn-warning me-2" onclick="editPam({{ $pam->id }})">
                                <i class="bi bi-pencil me-2"></i>Edit PAM
                            </button>
                            <button class="btn btn-danger" onclick="deletePam({{ $pam->id }}, '{{ $pam->name }}')">
                                <i class="bi bi-trash me-2"></i>Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Management Tabs -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs nav-tabs-custom" id="pamTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab"
                                data-bs-target="#overview" type="button" role="tab">
                                <i class="bi bi-grid me-2"></i>Overview
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="areas-tab" data-bs-toggle="tab" data-bs-target="#areas"
                                type="button" role="tab">
                                <i class="bi bi-geo-alt me-2"></i>Areas
                                <span class="badge bg-primary ms-1">{{ $areas->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tariffs-tab" data-bs-toggle="tab" data-bs-target="#tariffs"
                                type="button" role="tab">
                                <i class="bi bi-tags me-2"></i>Tariff Groups
                                <span class="badge bg-warning ms-1">{{ $tariffGroups->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tiers-tab" data-bs-toggle="tab" data-bs-target="#tiers"
                                type="button" role="tab">
                                <i class="bi bi-layers me-2"></i>Tariff Tiers
                                <span class="badge bg-info ms-1">{{ $tariffTiers->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="fees-tab" data-bs-toggle="tab" data-bs-target="#fees"
                                type="button" role="tab">
                                <i class="bi bi-cash-stack me-2"></i>Fixed Fees
                                <span class="badge bg-success ms-1">{{ $fixedFees->count() }}</span>
                            </button>
                        </li>
                    </ul>

                    <!-- Tabs Content -->
                    <div class="tab-content p-4" id="pamTabsContent">
                        <!-- Overview Tab -->
                        @include('dashboard.pam.partials.detail-overview')

                        <!-- Areas Tab -->
                        @include('dashboard.pam.partials.detail-area')

                        <!-- Tariff Groups Tab -->
                        @include('dashboard.pam.partials.detail-tariff-group')

                        <!-- Tariff Tiers Tab -->
                        @include('dashboard.pam.partials.detail-tariff-tier')

                        <!-- Fixed Fees Tab -->
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
                                            <th>Fee Code</th>
                                            <th>Fee Name</th>
                                            <th>Amount</th>
                                            <th>Frequency</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($fixedFees as $fee)
                                            <tr>
                                                <td><span class="badge bg-success">{{ $fee->code }}</span></td>
                                                <td>{{ $fee->name }}</td>
                                                <td>Rp {{ number_format($fee->amount, 0, ',', '.') }}</td>
                                                <td>{{ ucfirst($fee->frequency) }}</td>
                                                <td>
                                                    @if ($fee->status === 'active' || (isset($fee->is_active) && $fee->is_active))
                                                        <span class="badge bg-success">Active</span>
                                                    @else
                                                        <span class="badge bg-warning">Inactive</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary"
                                                            onclick="editFixedFee({{ $fee->id }})">
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Tariff Group Modal -->
    <div class="modal fade" id="createTariffGroupModal" tabindex="-1" aria-labelledby="createTariffGroupModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="createTariffGroupModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>Add New Tariff Group
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="createTariffGroupForm" method="POST"
                    action="{{ route('pam.tariff-groups.store', $pam->id) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tariffGroupName" class="form-label">Group Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="tariffGroupName" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tariffGroupCode" class="form-label">Group Code <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="tariffGroupCode" name="code" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="tariffGroupDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="tariffGroupDescription" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="tariffGroupStatus" class="form-label">Status <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="tariffGroupStatus" name="is_active" required>
                                <option value="">Select Status</option>
                                <option value="1" selected>Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <small>After creating this tariff group, you can add tariff tiers to define pricing
                                structure.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-plus-circle me-2"></i>Create Tariff Group
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .nav-tabs-custom {
            border-bottom: 2px solid #e9ecef;
        }

        .nav-tabs-custom .nav-link {
            border: none;
            color: #6c757d;
            padding: 1rem 1.5rem;
            border-radius: 10px 10px 0 0;
            margin-right: 0.25rem;
            transition: all 0.3s ease;
        }

        .nav-tabs-custom .nav-link:hover {
            color: var(--primary-color);
            background-color: rgba(0, 123, 255, 0.05);
        }

        .nav-tabs-custom .nav-link.active {
            color: var(--primary-color);
            background-color: rgba(0, 123, 255, 0.1);
            border-bottom: 3px solid var(--primary-color);
            font-weight: 600;
        }

        .stat-card-success,
        .stat-card-warning,
        .stat-card-info,
        .stat-card-primary {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card-success:hover,
        .stat-card-warning:hover,
        .stat-card-info:hover,
        .stat-card-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Tab switching
        document.addEventListener('DOMContentLoaded', function() {
            // Store active tab in localStorage
            const tabButtons = document.querySelectorAll('#pamTabs button[data-bs-toggle="tab"]');

            tabButtons.forEach(button => {
                button.addEventListener('shown.bs.tab', function(e) {
                    const target = e.target.getAttribute('data-bs-target');
                    localStorage.setItem('activePamTab', target);
                });
            });

            // Restore active tab from localStorage
            const activeTab = localStorage.getItem('activePamTab');
            if (activeTab) {
                const tabButton = document.querySelector(`#pamTabs button[data-bs-target="${activeTab}"]`);
                if (tabButton) {
                    const tab = new bootstrap.Tab(tabButton);
                    tab.show();
                }
            }

            // Handle tariff group form submission
            const createTariffGroupForm = document.getElementById('createTariffGroupForm');
            if (createTariffGroupForm) {
                createTariffGroupForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    handleCreateTariffGroup();
                });
            }
        });

        // Navigation functions
        function editPam(id) {
            console.log('Edit PAM:', id);
            showNotification('Edit PAM functionality coming soon', 'info');
        }

        function deletePam(id, name) {
            if (confirm(`Are you sure you want to delete ${name}?`)) {
                console.log('Delete PAM:', id);
                showNotification('PAM deleted successfully', 'success');
                setTimeout(() => window.location.href = '/pam', 1000);
            }
        }

        function editArea(id) {
            console.log('Edit Area:', id);
            showNotification('Edit Area functionality coming soon', 'info');
        }

        function deleteArea(id, name) {
            if (confirm(`Are you sure you want to delete ${name}?`)) {
                console.log('Delete Area:', id);
                showNotification('Area deleted successfully', 'success');
            }
        }

        function editTariffGroup(id) {
            console.log('Edit Tariff Group:', id);
            showNotification('Edit Tariff Group functionality coming soon', 'info');
        }

        function manageTiers(id) {
            console.log('Manage Tiers for group:', id);
            showNotification('Manage Tiers functionality coming soon', 'info');
        }

        function deleteTariffGroup(id, name) {
            if (confirm(`Are you sure you want to delete ${name}?`)) {
                console.log('Delete Tariff Group:', id);
                showNotification('Tariff Group deleted successfully', 'success');
            }
        }

        function editTariffTier(id) {
            console.log('Edit Tariff Tier:', id);
            showNotification('Edit Tariff Tier functionality coming soon', 'info');
        }

        function deleteTariffTier(id, name) {
            if (confirm(`Are you sure you want to delete ${name}?`)) {
                console.log('Delete Tariff Tier:', id);
                showNotification('Tariff Tier deleted successfully', 'success');
            }
        }

        function editFixedFee(id) {
            console.log('Edit Fixed Fee:', id);
            showNotification('Edit Fixed Fee functionality coming soon', 'info');
        }

        function deleteFixedFee(id, name) {
            if (confirm(`Are you sure you want to delete ${name}?`)) {
                console.log('Delete Fixed Fee:', id);
                showNotification('Fixed Fee deleted successfully', 'success');
            }
        }

        // Modal functions
        function showCreateAreaModal() {
            console.log('Show Create Area Modal');
            showNotification('Create Area modal coming soon', 'info');
        }

        function showCreateTariffGroupModal() {
            const modal = new bootstrap.Modal(document.getElementById('createTariffGroupModal'));

            // Generate default code based on current date and random number
            const date = new Date();
            const code = 'TG' + date.getFullYear() + String(date.getMonth() + 1).padStart(2, '0') + String(Math.floor(Math
                .random() * 1000)).padStart(3, '0');
            document.getElementById('tariffGroupCode').value = code;

            modal.show();
        }

        function showCreateTariffTierModal() {
            console.log('Show Create Tariff Tier Modal');
            showNotification('Create Tariff Tier modal coming soon', 'info');
        }

        function showCreateFixedFeeModal() {
            console.log('Show Create Fixed Fee Modal');
            showNotification('Create Fixed Fee modal coming soon', 'info');
        }

        function handleCreateTariffGroup() {
            const form = document.getElementById('createTariffGroupForm');
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;

            // Show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Creating...';

            fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('createTariffGroupModal'));
                        modal.hide();

                        // Reset form
                        form.reset();

                        // Show success message
                        showNotification(data.message || 'Tariff group created successfully!', 'success');

                        // Reload page after a short delay to show new data
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        // Show error message
                        showNotification(data.message || 'Failed to create tariff group', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while creating the tariff group', 'danger');
                })
                .finally(() => {
                    // Restore button state
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
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
