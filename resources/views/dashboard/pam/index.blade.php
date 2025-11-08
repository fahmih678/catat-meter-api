@extends('layouts.main')

@section('title', 'PAM Management')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">PAM Management</li>
@endsection

@section('content')
    <div class="container-fluid p-0">
        <!-- Page Header -->
        <div class="row mb-4 ">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="row align-items-center">
                        <div class="col">
                            <h2 class="mb-1">PAM Management</h2>
                            <p class="text-muted mb-0">Manage water utility companies, areas, and tariff settings</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Management Sections -->
        <div class="row">
            <!-- PAM List -->
            <div class="col-lg-12 mb-4">
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-building me-2"></i>
                                    {{ $pamTotal }} Water Utility Companies
                                </h5>
                            </div>
                            <div class="col-md-4">
                                <form method="GET" action="{{ route('pam.index') }}" id="searchForm"
                                    class="search-container">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="search"
                                            value="{{ $search ?? '' }}" placeholder="Search by name, code, email, phone..."
                                            id="searchInput" autocomplete="off">
                                        <button class="btn btn-outline-secondary search-button" type="submit"
                                            title="Search">
                                            <i class="bi bi-search"></i>
                                        </button>
                                        <a href="javascript:void(0)" class="btn btn-outline-danger clear-button"
                                            onclick="clearSearch()" title="Clear search"
                                            style="display: {{ $search ?? '' ? 'inline-block' : 'none' }};">
                                            <i class="bi bi-x-lg"></i>
                                        </a>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-primary" onclick="showCreatePamModal()">
                                    <i class="bi bi-plus-lg me-1"></i> Add New PAM
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($search ?? '')
                            <div class="alert alert-info search-result-alert d-flex align-items-center" role="alert">
                                <i class="bi bi-search me-2"></i>
                                <div>
                                    <strong>Search Results:</strong> Found {{ $pamTotal }} PAM(s) matching "<span
                                        class="search-highlight">{{ $search }}</span>"
                                    <a href="{{ route('pam.index') }}" class="ms-3 text-decoration-none">
                                        <i class="bi bi-x-circle me-1"></i>Clear search
                                    </a>
                                </div>
                            </div>
                        @endif

                        <div id="pamTableContainer">
                            @include('dashboard.pam.partials.table', [
                                'pams' => $pams,
                                'search' => $search,
                            ])
                        </div>

                        <div id="pamPaginationContainer">
                            @include('dashboard.pam.partials.pagination', [
                                'pams' => $pams,
                                'search' => $search,
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit PAM Modal -->
    <div class="modal fade" id="editPamModal" tabindex="-1" aria-labelledby="editPamModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPamModalLabel">
                        <i class="bi bi-building-gear me-2"></i>Edit Water Utility Company
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editPamForm">
                    @csrf
                    <input type="hidden" id="edit_pam_id" name="id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="edit_pam_name" name="name" required>
                                    <label for="edit_pam_name">Name *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="edit_pam_code" name="code" required>
                                    <label for="edit_pam_code">Code *</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-floating mb-3">
                                    <textarea class="form-control" id="edit_pam_address" name="address" style="height: 80px" required></textarea>
                                    <label for="edit_pam_address">Address *</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control" id="edit_pam_email" name="email">
                                    <label for="edit_pam_email">Email Address</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="tel" class="form-control" id="edit_pam_phone" name="phone">
                                    <label for="edit_pam_phone">Phone Number</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="edit_pam_coordinate"
                                        name="coordinate">
                                    <label for="edit_pam_coordinate">Coordinate (latitude,longitude)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="url" class="form-control" id="edit_pam_logo_url" name="logo_url">
                                    <label for="edit_pam_logo_url">Logo URL</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="edit_pam_is_active"
                                        name="is_active" value="1">
                                    <label class="form-check-label" for="edit_pam_is_active">
                                        Active Status
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Error display area -->
                        <div id="editPamErrors" class="alert alert-danger d-none"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitEditPamBtn">
                            <i class="bi bi-check-lg me-1"></i>Update PAM
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create PAM Modal -->
    <div class="modal fade" id="createPamModal" tabindex="-1" aria-labelledby="createPamModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createPamModalLabel">
                        <i class="bi bi-building-add me-2"></i>Add New Water Utility Company
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="createPamForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="pam_name" name="name" required>
                                    <label for="pam_name">Name *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="pam_code" name="code" required>
                                    <label for="pam_code">Code *</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-floating mb-3">
                                    <textarea class="form-control" id="pam_address" name="address" style="height: 80px" required></textarea>
                                    <label for="pam_address">Address *</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control" id="pam_email" name="email">
                                    <label for="pam_email">Email Address</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="tel" class="form-control" id="pam_phone" name="phone">
                                    <label for="pam_phone">Phone Number</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="pam_coordinate" name="coordinate">
                                    <label for="pam_coordinate">Coordinate (latitude,longitude)</label>
                                </div>
                            </div>
                        </div>

                        <!-- Error display area -->
                        <div id="createPamErrors" class="alert alert-danger d-none"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitPamBtn">
                            <i class="bi bi-check-lg me-1"></i>Create PAM
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .stat-card-primary,
        .stat-card-success,
        .stat-card-warning,
        .stat-card-info {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card-primary:hover,
        .stat-card-success:hover,
        .stat-card-warning:hover,
        .stat-card-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        /* Search Styles */
        .search-container {
            position: relative;
        }

        #searchInput {
            border-radius: 20px 0 0 20px;
            border-right: none;
            transition: all 0.3s ease;
        }

        #searchInput:focus {
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            border-color: var(--primary-color);
        }

        .search-button {
            border-radius: 0 20px 20px 0;
            border-left: none;
            transition: all 0.3s ease;
        }

        .search-button:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .clear-button {
            border-radius: 20px;
            margin-left: 0.5rem;
            transition: all 0.3s ease;
        }

        .clear-button:hover {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
            transform: scale(1.05);
        }

        .search-highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
            animation: highlight 0.5s ease-in-out;
        }

        @keyframes highlight {
            from {
                background-color: #ffc107;
            }

            to {
                background-color: #fff3cd;
            }
        }

        .search-result-alert {
            border-left: 4px solid var(--primary-color);
            background: linear-gradient(135deg, #e3f2fd 0%, #f8f9fa 100%);
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Mobile responsive search */
        @media (max-width: 768px) {
            .card-header .row {
                flex-direction: column;
                gap: 1rem;
            }

            .card-header .col-md-4 {
                width: 100%;
            }

            .card-header .text-end {
                text-align: left !important;
            }

            #searchInput {
                border-radius: 10px;
                border-right: 1px solid #dee2e6;
            }

            .search-button {
                border-radius: 10px;
                border-left: 1px solid #dee2e6;
                margin-top: 0.5rem;
                width: 100%;
            }
        }

        /* Table highlight for search results */
        .search-match td {
            background-color: rgba(102, 126, 234, 0.05);
            animation: fadeHighlight 1s ease-out;
        }

        @keyframes fadeHighlight {
            from {
                background-color: rgba(102, 126, 234, 0.2);
            }

            to {
                background-color: rgba(102, 126, 234, 0.05);
            }
        }

        /* Loading states */
        .loading-overlay {
            position: relative;
        }

        .loading-overlay::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        /* Smooth transitions for AJAX updates */
        #pamTableContainer,
        #pamPaginationContainer {
            transition: opacity 0.3s ease-in-out;
        }

        /* Search input loading state */
        .search-input-loading {
            position: relative;
        }

        .search-input-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: translateY(-50%) rotate(0deg);
            }

            100% {
                transform: translateY(-50%) rotate(360deg);
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Navigation functions
        function viewPam(id) {
            window.location.href = `/pam/${id}`;
        }

        function editPam(id) {
            // Show loading state
            const submitBtn = document.getElementById('submitEditPamBtn');
            if (submitBtn) {
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Loading...';
            }

            // Fetch PAM data
            fetch(`/pam/${id}/edit`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const pam = data.data;

                        // Check if all elements exist before accessing them
                        const elements = {
                            id: document.getElementById('edit_pam_id'),
                            name: document.getElementById('edit_pam_name'),
                            code: document.getElementById('edit_pam_code'),
                            address: document.getElementById('edit_pam_address'),
                            email: document.getElementById('edit_pam_email'),
                            phone: document.getElementById('edit_pam_phone'),
                            coordinate: document.getElementById('edit_pam_coordinate'),
                            logoUrl: document.getElementById('edit_pam_logo_url'),
                            activeCheckbox: document.getElementById('edit_pam_is_active'),
                            errors: document.getElementById('editPamErrors')
                        };

                        // Populate form fields safely
                        if (elements.id) elements.id.value = pam.id || '';
                        if (elements.name) elements.name.value = pam.name || '';
                        if (elements.code) elements.code.value = pam.code || '';
                        if (elements.address) elements.address.value = pam.address || '';
                        if (elements.email) elements.email.value = pam.email || '';
                        if (elements.phone) elements.phone.value = pam.phone || '';
                        if (elements.coordinate) {
                            elements.coordinate.value = pam.coordinate ? (typeof pam
                                .coordinate === 'string' ? pam.coordinate :
                                `${pam.coordinate.lat},${pam.coordinate.lng}`) : '';
                        }
                        if (elements.logoUrl) elements.logoUrl.value = pam.logo_url || '';

                        // Set active status
                        if (elements.activeCheckbox) {
                            elements.activeCheckbox.checked = pam.is_active === 1 || pam.is_active === true;
                        }

                        // Clear any previous errors
                        if (elements.errors) {
                            elements.errors.classList.add('d-none');
                        }

                        // Show modal with focus management
                        const modalElement = document.getElementById('editPamModal');
                        if (modalElement) {
                            // Remove any existing modal instance
                            const existingModal = bootstrap.Modal.getInstance(modalElement);
                            if (existingModal) {
                                existingModal.dispose();
                            }

                            // Create new modal instance
                            const modal = new bootstrap.Modal(modalElement, {
                                focus: true,
                                keyboard: true
                            });

                            // Show modal
                            modal.show();

                            // Handle modal hidden event to clean up focus
                            modalElement.addEventListener('hidden.bs.modal', function() {
                                if (document.activeElement && modalElement.contains(document.activeElement)) {
                                    document.activeElement.blur();
                                }
                            }, { once: true });
                        }
                    } else {
                        showNotification(data.message || 'Failed to load PAM data', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error fetching PAM data:', error);
                    showNotification('An error occurred while loading PAM data', 'danger');
                })
                .finally(() => {
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Update PAM';
                    }
                });
        }

        function deletePam(id, name) {
            if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
                // Show loading state
                showNotification('Deleting PAM...', 'info');

                fetch(`/pam/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message || 'PAM deleted successfully!', 'success');

                            // Reload page after a short delay to show updated data
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            showNotification(data.message || 'Failed to delete PAM', 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting PAM:', error);

                        if (error.status === 403) {
                            showNotification('Access denied. You do not have permission to delete PAM.', 'danger');
                        } else if (error.status === 401) {
                            showNotification('Your session has expired. Please login again.', 'danger');
                        } else {
                            showNotification('An error occurred while deleting PAM', 'danger');
                        }
                    });
            }
        }

        function togglePamStatus(id, currentStatus) {
            const newStatus = !currentStatus;
            const statusText = newStatus ? 'activate' : 'deactivate';

            if (confirm(`Are you sure you want to ${statusText} this PAM?`)) {
                showNotification(`${statusText.charAt(0).toUpperCase() + statusText.slice(1)}ing PAM...`, 'info');

                const formData = new FormData();
                formData.append('_method', 'PUT');
                formData.append('is_active', newStatus ? '1' : '0');

                fetch(`/pam/${id}/toggle-status`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message || `PAM ${statusText}d successfully!`, 'success');

                            // Reload page after a short delay to show updated data
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            showNotification(data.message || `Failed to ${statusText} PAM`, 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Error toggling PAM status:', error);

                        if (error.status === 403) {
                            showNotification('Access denied. You do not have permission to change PAM status.',
                                'danger');
                        } else if (error.status === 401) {
                            showNotification('Your session has expired. Please login again.', 'danger');
                        } else {
                            showNotification(`An error occurred while ${statusText}ing PAM`, 'danger');
                        }
                    });
            }
        }

        function viewArea(id) {
            window.location.href = `/pam/areas/${id}`;
        }

        function editArea(id) {
            console.log('Edit Area:', id);
            showNotification('Edit Area functionality coming soon', 'info');
        }

        function viewTariffGroup(id) {
            window.location.href = `/pam/tariff-groups/${id}`;
        }

        function editTariffGroup(id) {
            console.log('Edit Tariff Group:', id);
            showNotification('Edit Tariff Group functionality coming soon', 'info');
        }

        function viewFixedFee(id) {
            console.log('View Fixed Fee:', id);
            showNotification('View Fixed Fee functionality coming soon', 'info');
        }

        function editFixedFee(id) {
            console.log('Edit Fixed Fee:', id);
            showNotification('Edit Fixed Fee functionality coming soon', 'info');
        }

        // Modal functions
        function showCreatePamModal() {
            const modalElement = document.getElementById('createPamModal');
            if (!modalElement) return;

            // Remove any existing modal instance
            const existingModal = bootstrap.Modal.getInstance(modalElement);
            if (existingModal) {
                existingModal.dispose();
            }

            // Create new modal instance
            const modal = new bootstrap.Modal(modalElement, {
                focus: true,
                keyboard: true
            });

            const form = document.getElementById('createPamForm');
            const errors = document.getElementById('createPamErrors');

            if (form) {
                form.reset();
            }

            if (errors) {
                errors.classList.add('d-none');
            }

            modal.show();
        }

        function showCreateAreaModal() {
            console.log('Show Create Area Modal');
            showNotification('Create Area modal coming soon', 'info');
        }

        function showCreateTariffGroupModal() {
            console.log('Show Create Tariff Group Modal');
            showNotification('Create Tariff Group modal coming soon', 'info');
        }

        function showCreateFixedFeeModal() {
            console.log('Show Create Fixed Fee Modal');
            showNotification('Create Fixed Fee modal coming soon', 'info');
        }

        // Search functionality with AJAX
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const searchForm = document.getElementById('searchForm');
            const searchButton = searchForm.querySelector('.search-button');
            let currentSearch = '{{ $search ?? '' }}';
            let currentPage = 1;

            // Auto-search functionality with debouncing
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();

                // Show loading state
                if (query.length > 0) {
                    searchButton.innerHTML =
                        '<span class="spinner-border spinner-border-sm" role="status"></span>';
                } else {
                    searchButton.innerHTML = '<i class="bi bi-search"></i>';
                }

                // Update clear button visibility
                updateClearButton(query);

                // Debounce search (wait 1000ms after user stops typing)
                searchTimeout = setTimeout(() => {
                    if (query.length >= 2 || query.length === 0) {
                        performAjaxSearch(query, 1);
                    }
                }, 1000);
            });

            // Handle search form submission
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const query = searchInput.value.trim();

                // Don't search if query is too short (unless empty)
                if (query.length > 0 && query.length < 2) {
                    showNotification('Please enter at least 2 characters to search', 'warning');
                    return;
                }

                performAjaxSearch(query, 1);
            });

            // Clear search on escape key
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    this.value = '';
                    updateClearButton('');
                    performAjaxSearch('', 1);
                }
            });

            // Update clear button visibility
            function updateClearButton(query) {
                const clearButton = document.querySelector('.clear-button');
                if (clearButton) {
                    if (query) {
                        clearButton.style.display = 'inline-block';
                    } else {
                        clearButton.style.display = 'none';
                    }
                }
            }

            // Perform AJAX search
            function performAjaxSearch(query, page) {
                // Show loading states
                searchButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
                searchButton.disabled = true;
                showLoadingState();

                const params = new URLSearchParams();
                if (query) params.append('search', query);
                if (page > 1) params.append('page', page);

                fetch(`/pam/search?${params.toString()}`, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateTableContent(data.tableHtml);
                            updatePaginationContent(data.paginationHtml);
                            updateSearchInfo(data.search, data.total);
                            currentSearch = data.search;
                            currentPage = data.currentPage;
                            highlightSearchResults();
                        } else {
                            showNotification(data.message || 'Search failed', 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        showNotification('An error occurred while searching', 'danger');
                    })
                    .finally(() => {
                        // Reset loading states
                        searchButton.innerHTML = '<i class="bi bi-search"></i>';
                        searchButton.disabled = false;
                        hideLoadingState();
                    });
            }

            // Load page function for pagination
            window.loadPage = function(page) {
                performAjaxSearch(currentSearch, page);
            };

            // Update table content
            function updateTableContent(html) {
                const container = document.getElementById('pamTableContainer');
                container.innerHTML = html;
                container.style.opacity = '0';
                setTimeout(() => {
                    container.style.opacity = '1';
                }, 100);
            }

            // Update pagination content
            function updatePaginationContent(html) {
                const container = document.getElementById('pamPaginationContainer');
                container.innerHTML = html;
                container.style.opacity = '0';
                setTimeout(() => {
                    container.style.opacity = '1';
                }, 100);
            }

            // Update search info
            function updateSearchInfo(search, total) {
                const searchInfo = document.querySelector('.search-result-alert');
                const totalCount = document.querySelector('.card-title h5');

                // Update total count in header
                if (totalCount) {
                    totalCount.innerHTML = `<i class="bi bi-building me-2"></i>${total} Water Utility Companies`;
                }

                // Show/hide search info
                if (search) {
                    if (!searchInfo) {
                        const alertHtml = `
                            <div class="alert alert-info search-result-alert d-flex align-items-center" role="alert">
                                <i class="bi bi-search me-2"></i>
                                <div>
                                    <strong>Search Results:</strong> Found ${total} PAM(s) matching "<span class="search-highlight">${search}</span>"
                                    <a href="javascript:void(0)" onclick="clearSearch()" class="ms-3 text-decoration-none">
                                        <i class="bi bi-x-circle me-1"></i>Clear search
                                    </a>
                                </div>
                            </div>
                        `;
                        const container = document.querySelector('.card-body');
                        container.insertAdjacentHTML('afterbegin', alertHtml);
                    } else {
                        const infoDiv = searchInfo.querySelector('div');
                        infoDiv.innerHTML = `
                            <strong>Search Results:</strong> Found ${total} PAM(s) matching "<span class="search-highlight">${search}</span>"
                            <a href="javascript:void(0)" onclick="clearSearch()" class="ms-3 text-decoration-none">
                                <i class="bi bi-x-circle me-1"></i>Clear search
                            </a>
                        `;
                    }
                } else {
                    if (searchInfo) {
                        searchInfo.remove();
                    }
                }
            }

            // Clear search function
            window.clearSearch = function() {
                searchInput.value = '';
                updateClearButton('');
                performAjaxSearch('', 1);
            };

            // Show loading state
            function showLoadingState() {
                const tableContainer = document.getElementById('pamTableContainer');
                const paginationContainer = document.getElementById('pamPaginationContainer');

                if (tableContainer) {
                    tableContainer.style.opacity = '0.5';
                }
                if (paginationContainer) {
                    paginationContainer.style.opacity = '0.5';
                }
            }

            // Hide loading state
            function hideLoadingState() {
                const tableContainer = document.getElementById('pamTableContainer');
                const paginationContainer = document.getElementById('pamPaginationContainer');

                if (tableContainer) {
                    tableContainer.style.opacity = '1';
                }
                if (paginationContainer) {
                    paginationContainer.style.opacity = '1';
                }
            }

            // Initialize
            updateClearButton(currentSearch);
            highlightSearchResults();
        });

        function highlightSearchResults() {
            const searchTerm = '{{ $search ?? '' }}';
            if (!searchTerm) return;

            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                let hasMatch = false;

                cells.forEach(cell => {
                    const text = cell.textContent;
                    if (text.toLowerCase().includes(searchTerm.toLowerCase())) {
                        hasMatch = true;
                        // Add highlight class to matching rows
                        row.classList.add('search-match');
                    }
                });
            });
        }

        // Form submission for creating PAM
        document.addEventListener('DOMContentLoaded', function() {
            const createPamForm = document.getElementById('createPamForm');
            const submitPamBtn = document.getElementById('submitPamBtn');
            const createPamErrors = document.getElementById('createPamErrors');

            createPamForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Basic client-side validation
                const name = document.getElementById('pam_name').value.trim();
                const code = document.getElementById('pam_code').value.trim();
                const address = document.getElementById('pam_address').value.trim();

                if (!name || !code || !address) {
                    createPamErrors.innerHTML =
                        'Please fill in all required fields (Name, Code, and Address).';
                    createPamErrors.classList.remove('d-none');
                    return;
                }

                // Disable submit button
                submitPamBtn.disabled = true;
                submitPamBtn.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1"></span>Creating...';
                createPamErrors.classList.add('d-none');

                const formData = new FormData(createPamForm);
                const submitData = Object.fromEntries(formData.entries());

                // Convert is_active string to boolean
                if (submitData.is_active) {
                    submitData.is_active = submitData.is_active === '1';
                }

                fetch('/pam', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(submitData)
                    })
                    .then(response => {
                        if (!response.ok) {
                            // Try to parse error response
                            return response.json().then(data => {
                                throw {
                                    status: response.status,
                                    response: data
                                };
                            }).catch(() => {
                                // If parsing fails, just throw status
                                throw {
                                    status: response.status
                                };
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Close modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById(
                                'createPamModal'));
                            modal.hide();

                            // Show success message
                            showNotification(data.message || 'PAM created successfully!', 'success');

                            // Reload page after a short delay to show new data
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            // Show error messages
                            let errorMessage = '';
                            if (data.errors) {
                                Object.values(data.errors).forEach(errors => {
                                    errors.forEach(error => {
                                        errorMessage += error + '<br>';
                                    });
                                });
                            } else {
                                errorMessage = data.message ||
                                    'An error occurred while creating the PAM.';
                            }

                            createPamErrors.innerHTML = errorMessage;
                            createPamErrors.classList.remove('d-none');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);

                        // Handle unauthorized access
                        if (error.status === 403) {
                            const errorData = error.response?.json?.();
                            if (errorData?.error_code === 'INSUFFICIENT_ROLE') {
                                createPamErrors.innerHTML =
                                    'Access denied. You need superadmin privileges to create PAM.';
                            } else {
                                createPamErrors.innerHTML =
                                    'Access denied. You do not have permission to perform this action.';
                            }
                        } else if (error.status === 401) {
                            createPamErrors.innerHTML = 'Your session has expired. Please login again.';
                        } else {
                            createPamErrors.innerHTML =
                                'An unexpected error occurred. Please try again.';
                        }

                        createPamErrors.classList.remove('d-none');
                    })
                    .finally(() => {
                        // Re-enable submit button
                        submitPamBtn.disabled = false;
                        submitPamBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Create PAM';
                    });
            });
        });

        // Form submission for editing PAM
        document.addEventListener('DOMContentLoaded', function() {
            const editPamForm = document.getElementById('editPamForm');
            const submitEditPamBtn = document.getElementById('submitEditPamBtn');
            const editPamErrors = document.getElementById('editPamErrors');

            if (editPamForm) {
                editPamForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    // Get form elements safely
                    const elements = {
                        id: document.getElementById('edit_pam_id'),
                        name: document.getElementById('edit_pam_name'),
                        code: document.getElementById('edit_pam_code'),
                        address: document.getElementById('edit_pam_address'),
                        activeCheckbox: document.getElementById('edit_pam_is_active')
                    };

                    // Basic client-side validation
                    const id = elements.id ? elements.id.value : '';
                    const name = elements.name ? elements.name.value.trim() : '';
                    const code = elements.code ? elements.code.value.trim() : '';
                    const address = elements.address ? elements.address.value.trim() : '';

                    if (!id || !name || !code || !address) {
                        if (editPamErrors) {
                            editPamErrors.innerHTML =
                                'Please fill in all required fields (Name, Code, and Address).';
                            editPamErrors.classList.remove('d-none');
                        }
                        return;
                    }

                    // Disable submit button
                    if (submitEditPamBtn) {
                        submitEditPamBtn.disabled = true;
                        submitEditPamBtn.innerHTML =
                            '<span class="spinner-border spinner-border-sm me-1"></span>Updating...';
                    }

                    if (editPamErrors) {
                        editPamErrors.classList.add('d-none');
                    }

                    const formData = new FormData(editPamForm);
                    const submitData = Object.fromEntries(formData.entries());

                    // Handle checkbox
                    submitData.is_active = elements.activeCheckbox ?
                        (elements.activeCheckbox.checked ? '1' : '0') : '0';

                    // Convert to PUT request for Laravel
                    submitData._method = 'PUT';

                    fetch(`/pam/${id}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(submitData)
                        })
                        .then(response => {
                            if (!response.ok) {
                                return response.json().then(data => {
                                    throw {
                                        status: response.status,
                                        response: data
                                    };
                                }).catch(() => {
                                    throw {
                                        status: response.status
                                    };
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                // Close modal safely
                                const modalElement = document.getElementById('editPamModal');
                                if (modalElement) {
                                    const modal = bootstrap.Modal.getInstance(modalElement);
                                    if (modal) {
                                        modal.hide();
                                    }
                                }

                                // Show success message
                                showNotification(data.message || 'PAM updated successfully!',
                                    'success');

                                // Reload page after a short delay to show updated data
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            } else {
                                // Show error messages
                                let errorMessage = '';
                                if (data.errors) {
                                    Object.values(data.errors).forEach(errors => {
                                        errors.forEach(error => {
                                            errorMessage += error + '<br>';
                                        });
                                    });
                                } else {
                                    errorMessage = data.message ||
                                        'An error occurred while updating the PAM.';
                                }

                                if (editPamErrors) {
                                    editPamErrors.innerHTML = errorMessage;
                                    editPamErrors.classList.remove('d-none');
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);

                            // Handle unauthorized access
                            let errorMessage = 'An unexpected error occurred. Please try again.';
                            if (error.status === 403) {
                                errorMessage = 'Access denied. You do not have permission to perform this action.';
                            } else if (error.status === 401) {
                                errorMessage = 'Your session has expired. Please login again.';
                            }

                            if (editPamErrors) {
                                editPamErrors.innerHTML = errorMessage;
                                editPamErrors.classList.remove('d-none');
                            }
                        })
                        .finally(() => {
                            // Re-enable submit button
                            if (submitEditPamBtn) {
                                submitEditPamBtn.disabled = false;
                                submitEditPamBtn.innerHTML =
                                    '<i class="bi bi-check-lg me-1"></i>Update PAM';
                            }
                        });
                });
            }
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
