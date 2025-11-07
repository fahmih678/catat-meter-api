// Customer Management JavaScript
// Handle customer CRUD operations and meter management

let currentPamId;

// Initialize when DOM is ready
function initializeCustomerManagement() {
    // Check if required elements exist
    if (!checkRequiredElements()) {
        console.warn('Some required elements are missing. Customer management may not work properly.');
    }

    // Auto-submit form when filters change
    const filterInputs = ['search', 'filter_area_id', 'status', 'per_page'];
    filterInputs.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', function() {
                if (id !== 'search') {
                    const filterForm = document.getElementById('filterForm');
                    if (filterForm) {
                        filterForm.submit();
                    }
                }
            });
        }
    });

    // Search on Enter key with debounce
    const searchInput = document.getElementById('search');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const filterForm = document.getElementById('filterForm');
                if (filterForm) {
                    filterForm.submit();
                }
            }, 500);
        });
    }

    // Handle Add Customer Form Submission
    const addForm = document.getElementById('addCustomerForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleAddCustomer();
        });
    }

    // Handle Edit Customer Form Submission
    const editForm = document.getElementById('editCustomerForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleEditCustomer();
        });
    }

    console.log('Customer Management initialized successfully');
}

// Modal accessibility improvements
function setupModalAccessibility() {
    const modals = ['addCustomerModal', 'editCustomerModal', 'viewCustomerModal'];

    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        // Add event listeners for modal show/hide
        modal.addEventListener('show.bs.modal', function() {
            // Ensure proper focus management
            const firstInput = modal.querySelector('input:not([readonly]), select, textarea, button');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        });

        modal.addEventListener('shown.bs.modal', function() {
            // Trap focus within modal
            modal.setAttribute('aria-modal', 'true');
            modal.removeAttribute('aria-hidden');
        });

        modal.addEventListener('hide.bs.modal', function() {
            // Clean up focus trap
            modal.removeAttribute('aria-modal');
            modal.setAttribute('aria-hidden', 'true');
        });
    });
}

// Initialize on DOM content loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        initializeCustomerManagement();
        setupModalAccessibility();
    });
} else {
    initializeCustomerManagement();
    setupModalAccessibility();
}

// Initialize PAM ID
function initializePamId(pamId) {
    currentPamId = pamId;
}

// Form Data Management
function loadFormData(type, callback) {
    const formPrefix = type === 'add' ? '' : 'edit_';

    if (!currentPamId) {
        showNotification('PAM ID tidak tersedia', 'danger');
        if (callback && typeof callback === 'function') {
            callback();
        }
        return;
    }

    fetch(`/pam/${currentPamId}/customers/form-data`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                populateFormData(formPrefix, data.data);

                if (callback && typeof callback === 'function') {
                    callback();
                }
            } else {
                showNotification(data.message || 'Gagal memuat data form', 'danger');
                if (callback && typeof callback === 'function') {
                    callback();
                }
            }
        })
        .catch(error => {
            console.error('Load form data error:', error);
            showNotification('Gagal memuat data form: ' + error.message, 'danger');
            if (callback && typeof callback === 'function') {
                callback();
            }
        });
}

function populateFormData(formPrefix, data) {
    // Populate areas
    const areaSelect = document.getElementById(formPrefix + 'area_id');
    if (areaSelect) {
        areaSelect.innerHTML = '<option value="">Pilih Area</option>';
        if (data.areas && data.areas.length > 0) {
            data.areas.forEach(area => {
                areaSelect.innerHTML += `<option value="${area.id}">${area.name} (${area.code})</option>`;
            });
        } else {
            areaSelect.innerHTML = '<option value="">Tidak ada area tersedia</option>';
        }
    }

    // Populate tariff groups
    const tariffSelect = document.getElementById(formPrefix + 'tariff_group_id');
    if (tariffSelect) {
        tariffSelect.innerHTML = '<option value="">Pilih Grup Tarif</option>';
        if (data.tariffGroups && data.tariffGroups.length > 0) {
            data.tariffGroups.forEach(tariff => {
                tariffSelect.innerHTML += `<option value="${tariff.id}">${tariff.name}</option>`;
            });
        } else {
            tariffSelect.innerHTML = '<option value="">Tidak ada grup tarif tersedia</option>';
        }
    }

    // Populate users
    const userSelect = document.getElementById(formPrefix + 'user_id');
    if (userSelect) {
        userSelect.innerHTML = '<option value="">Pilih Pengguna</option>';
        if (data.users && data.users.length > 0) {
            data.users.forEach(user => {
                userSelect.innerHTML += `<option value="${user.id}">${user.name}</option>`;
            });
        } else {
            userSelect.innerHTML = '<option value="">Tidak ada pengguna tersedia</option>';
        }
    }
}

// Number Generation
function generateCustomerNumber(event) {
    // Prevent default action if event exists
    if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
    }

    // Get button reference safely
    let button;
    if (event && event.currentTarget) {
        button = event.currentTarget;
    } else {
        // Fallback to finding button by text content
        button = document.querySelector('button[onclick*="generateCustomerNumber"]');
    }

    // Validate button reference
    if (!button) {
        console.error('Button reference not found for generateCustomerNumber');
        showNotification('Tidak dapat menemukan tombol generate', 'danger');
        return;
    }

    const originalText = button.innerHTML;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';
    button.disabled = true;

    // Check if currentPamId is available
    if (!currentPamId) {
        button.innerHTML = originalText;
        button.disabled = false;
        showNotification('PAM ID tidak tersedia', 'danger');
        return;
    }

    fetch(`/pam/${currentPamId}/generate-customer-number`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const customerNumberInput = document.getElementById('customer_number');
                if (customerNumberInput) {
                    customerNumberInput.value = data.data.customer_number;
                    showNotification('Nomor pelanggan berhasil digenerate', 'success');
                } else {
                    showNotification('Input nomor pelanggan tidak ditemukan', 'danger');
                }
            } else {
                showNotification(data.message || 'Gagal generate nomor pelanggan', 'danger');
            }
        })
        .catch(error => {
            console.error('Generate customer number error:', error);
            showNotification('Terjadi kesalahan saat generate nomor pelanggan', 'danger');
        })
        .finally(() => {
            if (button) {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        });
}

function generateMeterNumber(event) {
    // Prevent default action if event exists
    if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
    }

    // Get button reference safely
    let button;
    if (event && event.currentTarget) {
        button = event.currentTarget;
    } else {
        // Fallback to finding button by text content
        button = document.querySelector('button[onclick*="generateMeterNumber"]');
    }

    // Validate button reference
    if (!button) {
        console.error('Button reference not found for generateMeterNumber');
        showNotification('Tidak dapat menemukan tombol generate', 'danger');
        return;
    }

    const originalText = button.innerHTML;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';
    button.disabled = true;

    // Check if currentPamId is available
    if (!currentPamId) {
        button.innerHTML = originalText;
        button.disabled = false;
        showNotification('PAM ID tidak tersedia', 'danger');
        return;
    }

    fetch(`/pam/${currentPamId}/generate-meter-number`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const meterNumberInput = document.getElementById('meter_number');
                if (meterNumberInput) {
                    meterNumberInput.value = data.data.meter_number;
                    showNotification('Nomor meter berhasil digenerate', 'success');
                } else {
                    showNotification('Input nomor meter tidak ditemukan', 'danger');
                }
            } else {
                showNotification(data.message || 'Gagal generate nomor meter', 'danger');
            }
        })
        .catch(error => {
            console.error('Generate meter number error:', error);
            showNotification('Terjadi kesalahan saat generate nomor meter', 'danger');
        })
        .finally(() => {
            if (button) {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        });
}

function generateEditMeterNumber(event) {
    // Prevent default action if event exists
    if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
    }

    // Get button reference safely
    let button;
    if (event && event.currentTarget) {
        button = event.currentTarget;
    } else {
        // Fallback to finding button by text content
        button = document.querySelector('button[onclick*="generateEditMeterNumber"]');
    }

    // Validate button reference
    if (!button) {
        console.error('Button reference not found for generateEditMeterNumber');
        showNotification('Tidak dapat menemukan tombol generate', 'danger');
        return;
    }

    const originalText = button.innerHTML;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';
    button.disabled = true;

    // Check if currentPamId is available
    if (!currentPamId) {
        button.innerHTML = originalText;
        button.disabled = false;
        showNotification('PAM ID tidak tersedia', 'danger');
        return;
    }

    fetch(`/pam/${currentPamId}/generate-meter-number`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const editMeterNumberInput = document.getElementById('edit_meter_number');
                if (editMeterNumberInput) {
                    editMeterNumberInput.value = data.data.meter_number;
                    showNotification('Nomor meter berhasil digenerate', 'success');
                } else {
                    showNotification('Input nomor meter edit tidak ditemukan', 'danger');
                }
            } else {
                showNotification(data.message || 'Gagal generate nomor meter', 'danger');
            }
        })
        .catch(error => {
            console.error('Generate edit meter number error:', error);
            showNotification('Terjadi kesalahan saat generate nomor meter', 'danger');
        })
        .finally(() => {
            if (button) {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        });
}

// Modal Management
function showAddCustomerModal() {
    loadFormData('add', function() {
        const form = document.getElementById('addCustomerForm');
        if (form) {
            form.reset();
            clearValidationErrors('addCustomerForm');
        }

        const modalElement = document.getElementById('addCustomerModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);

            // Set up focus trap
            modalElement.addEventListener('shown.bs.modal', function() {
                const firstInput = modalElement.querySelector('#name');
                if (firstInput) {
                    setTimeout(() => firstInput.focus(), 100);
                }
            }, { once: true });

            modal.show();

            setTimeout(() => {
                // Find the generate button and click it programmatically
                const generateButton = document.querySelector('button[onclick*="generateCustomerNumber(event)"]');
                if (generateButton) {
                    generateButton.click();
                } else {
                    console.warn('Generate customer number button not found');
                }
            }, 500);
        }
    });
}

function editCustomer(id) {
    if (!id) {
        showNotification('ID pelanggan tidak valid', 'danger');
        return;
    }

    if (!currentPamId) {
        showNotification('PAM ID tidak tersedia', 'danger');
        return;
    }

    showNotification('Memuat data pelanggan...', 'info');

    loadFormData('edit', function() {
        fetch(`/pam/${currentPamId}/customers/${id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    populateEditForm(data.data);

                    const modalElement = document.getElementById('editCustomerModal');
                    if (modalElement) {
                        const modal = new bootstrap.Modal(modalElement);

                        // Set up focus trap for better accessibility
                        modalElement.addEventListener('shown.bs.modal', function() {
                            const firstInput = modalElement.querySelector('#edit_name');
                            if (firstInput) {
                                setTimeout(() => firstInput.focus(), 100);
                            }
                        }, { once: true });

                        modal.show();
                    }
                } else {
                    showNotification(data.message || 'Gagal memuat data pelanggan', 'danger');
                }
            })
            .catch(error => {
                console.error('Edit customer load error:', error);
                showNotification('Terjadi kesalahan saat memuat data pelanggan', 'danger');
            });
    });
}

function populateEditForm(customer) {
    // Clear validation errors first
    clearValidationErrors('editCustomerForm');

    // Populate basic customer fields with null checks
    const elements = {
        'edit_customer_id': customer.id,
        'edit_customer_number': customer.customer_number,
        'edit_name': customer.name,
        'edit_address': customer.address,
        'edit_phone': customer.phone || '',
        'edit_area_id': customer.area_id || '',
        'edit_tariff_group_id': customer.tariff_group_id || '',
        'edit_user_id': customer.user_id || ''
    };

    Object.keys(elements).forEach(elementId => {
        const element = document.getElementById(elementId);
        if (element) {
            element.value = elements[elementId] || '';
        }
    });

    // Set checkbox
    const isActiveElement = document.getElementById('edit_is_active');
    if (isActiveElement) {
        isActiveElement.checked = customer.is_active;
    }

    // Populate meter dropdown
    const meterSelect = document.getElementById('edit_meter_id');
    if (meterSelect) {
        meterSelect.innerHTML = '<option value="">Pilih Meter</option>';

        if (customer.meters && customer.meters.length > 0) {
            customer.meters.forEach(meter => {
                if (meter.is_active) {
                    meterSelect.innerHTML += `<option value="${meter.id}">${meter.meter_number}</option>`;
                }
            });
        }
    }

    // Reset meter action
    const meterActionElement = document.getElementById('edit_meter_action');
    if (meterActionElement) {
        meterActionElement.value = '';
    }

    const meterFieldsElement = document.getElementById('editMeterFields');
    if (meterFieldsElement) {
        meterFieldsElement.style.display = 'none';
    }

    clearEditMeterFields();
}

// Customer CRUD Operations
function handleAddCustomer() {
    clearValidationErrors('addCustomerForm');

    const formData = new FormData(document.getElementById('addCustomerForm'));
    const data = Object.fromEntries(formData);
    data.is_active = document.getElementById('is_active') ? document.getElementById('is_active').checked : true;

    showNotification('Menyimpan pelanggan...', 'info');

    fetch(`/pam/${currentPamId}/customers`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('addCustomerModal'));
            if (modal) modal.hide();
            refreshTable();
        } else {
            if (data.errors) {
                showValidationErrors('addCustomerForm', data.errors);
            }
            showNotification(data.message || 'Gagal menambahkan pelanggan', 'danger');
        }
    })
    .catch(error => {
        console.error('Add customer error:', error);
        showNotification('Terjadi kesalahan saat menambahkan pelanggan', 'danger');
    });
}

function handleEditCustomer() {
    clearValidationErrors('editCustomerForm');

    const customerId = document.getElementById('edit_customer_id').value;
    if (!customerId) {
        showNotification('ID pelanggan tidak ditemukan', 'danger');
        return;
    }

    const formData = new FormData(document.getElementById('editCustomerForm'));
    const data = Object.fromEntries(formData);
    data.is_active = document.getElementById('edit_is_active') ? document.getElementById('edit_is_active').checked : false;

    showNotification('Memperbarui pelanggan...', 'info');

    fetch(`/pam/${currentPamId}/customers/${customerId}`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('editCustomerModal'));
            if (modal) modal.hide();
            refreshTable();
        } else {
            if (data.errors) {
                showValidationErrors('editCustomerForm', data.errors);
            }
            showNotification(data.message || 'Gagal memperbarui pelanggan', 'danger');
        }
    })
    .catch(error => {
        console.error('Edit customer error:', error);
        showNotification('Terjadi kesalahan saat memperbarui pelanggan', 'danger');
    });
}

function viewCustomer(id) {
    if (!id) {
        showNotification('ID pelanggan tidak valid', 'danger');
        return;
    }

    showNotification('Memuat data pelanggan...', 'info');

    fetch(`/pam/${currentPamId}/customers/${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayCustomerDetails(data.data);

                const modalElement = document.getElementById('viewCustomerModal');
                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);

                    // Set up focus trap for better accessibility
                    modalElement.addEventListener('shown.bs.modal', function() {
                        // Focus on close button for view-only modal
                        const closeButton = modalElement.querySelector('.btn-close');
                        if (closeButton) {
                            setTimeout(() => closeButton.focus(), 100);
                        }
                    }, { once: true });

                    modal.show();
                }
            } else {
                showNotification(data.message || 'Gagal memuat data pelanggan', 'danger');
            }
        })
        .catch(error => {
            console.error('View customer error:', error);
            showNotification('Terjadi kesalahan saat memuat data pelanggan', 'danger');
        });
}

function deleteCustomer(id, name) {
    if (!id) {
        showNotification('ID pelanggan tidak valid', 'danger');
        return;
    }

    if (confirm(`Apakah Anda yakin ingin menghapus pelanggan "${name}"?`)) {
        showNotification('Menghapus pelanggan...', 'info');

        fetch(`/pam/${currentPamId}/customers/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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
                showNotification(data.message, 'success');
                refreshTable();
            } else {
                showNotification(data.message || 'Gagal menghapus pelanggan', 'danger');
            }
        })
        .catch(error => {
            console.error('Delete customer error:', error);
            showNotification('Terjadi kesalahan saat menghapus pelanggan', 'danger');
        });
    }
}

// Display Functions
function displayCustomerDetails(customer) {
    const content = document.getElementById('viewCustomerContent');

    // Build meters HTML
    let metersHtml = '';
    if (customer.meters && customer.meters.length > 0) {
        metersHtml = customer.meters.map(meter => `
            <tr>
                <td>${meter.meter_number}</td>
                <td>${meter.installed_at ? new Date(meter.installed_at).toLocaleDateString('id-ID') : '-'}</td>
                <td>${meter.initial_installed_meter || 0} m³</td>
                <td>${meter.notes || '-'}</td>
                <td>${meter.is_active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-warning">Tidak Aktif</span>'}</td>
            </tr>
        `).join('');
    } else {
        metersHtml = '<tr><td colspan="5" class="text-center text-muted">Belum ada meter terpasang</td></tr>';
    }

    content.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">Informasi Dasar</h6>
                <table class="table table-sm">
                    <tr><td><strong>Nomor Pelanggan:</strong></td><td>${customer.customer_number}</td></tr>
                    <tr><td><strong>Nama:</strong></td><td>${customer.name}</td></tr>
                    <tr><td><strong>Alamat:</strong></td><td>${customer.address}</td></tr>
                    <tr><td><strong>Telepon:</strong></td><td>${customer.phone || '-'}</td></tr>
                    <tr><td><strong>Status:</strong></td><td>${customer.is_active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-warning">Tidak Aktif</span>'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary">Informasi Terkait</h6>
                <table class="table table-sm">
                    <tr><td><strong>Area:</strong></td><td>${customer.area ? customer.area.name : '-'}</td></tr>
                    <tr><td><strong>Grup Tarif:</strong></td><td>${customer.tariffGroup ? customer.tariffGroup.name : '-'}</td></tr>
                    <tr><td><strong>Pengguna:</strong></td><td>${customer.user ? customer.user.name : '-'}</td></tr>
                </table>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <h6 class="text-primary">
                    <i class="bi bi-speedometer2 me-2"></i>Informasi Meter
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Nomor Meter</th>
                                <th>Tanggal Pasang</th>
                                <th>Awal Meter</th>
                                <th>Catatan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${metersHtml}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
}

// Meter Field Management
function toggleMeterFields() {
    const actionElement = document.getElementById('edit_meter_action');
    const meterFieldsElement = document.getElementById('editMeterFields');
    const meterIdField = document.getElementById('edit_meter_id');

    if (!actionElement || !meterFieldsElement) return;

    const action = actionElement.value;

    if (action === '') {
        meterFieldsElement.style.display = 'none';
    } else {
        meterFieldsElement.style.display = 'block';

        if (action === 'add' && meterIdField && meterIdField.parentElement) {
            meterIdField.parentElement.style.display = 'none';
        } else if (meterIdField && meterIdField.parentElement) {
            meterIdField.parentElement.style.display = 'block';
        }

        const meterFieldsDisabled = action === 'remove';
        const meterFieldIds = ['edit_meter_number', 'edit_installed_at', 'edit_initial_installed_meter', 'edit_meter_notes'];

        meterFieldIds.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.disabled = meterFieldsDisabled;
            }
        });
    }
}

function clearEditMeterFields() {
    const fieldIds = ['edit_meter_id', 'edit_meter_number', 'edit_installed_at', 'edit_initial_installed_meter', 'edit_meter_notes'];

    fieldIds.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.value = '';
        }
    });
}

// Utility Functions
function resetFilters() {
    const form = document.getElementById('filterForm');
    if (form) {
        form.querySelectorAll('input, select').forEach(element => {
            element.value = '';
        });
        form.submit();
    }
}

function refreshTable() {
    window.location.reload();
}

function exportCustomers() {
    const url = new URL(window.location);
    url.searchParams.set('export', 'excel');
    window.open(url.toString(), '_blank');
}

function viewMeterReadings(id) {
    if (id) {
        showNotification('Lihat bacaan meter pelanggan #' + id + ' - Fitur coming soon', 'info');
    } else {
        showNotification('ID pelanggan tidak valid', 'danger');
    }
}

// Additional utility function to check if all required elements exist
function checkRequiredElements() {
    const requiredElements = [
        'filterForm',
        'addCustomerModal',
        'editCustomerModal',
        'viewCustomerModal',
        'addCustomerForm',
        'editCustomerForm'
    ];

    const missingElements = requiredElements.filter(id => !document.getElementById(id));

    if (missingElements.length > 0) {
        console.warn('Missing required elements:', missingElements);
        return false;
    }

    return true;
}

// Enhanced notification system with auto-dismiss
function showNotification(message, type = 'info', autoDismiss = true) {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        `;
        document.body.appendChild(toastContainer);
    }

    const toastHtml = `
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    const toastElement = document.createElement('div');
    toastElement.innerHTML = toastHtml;
    toastContainer.appendChild(toastElement);

    const toast = new bootstrap.Toast(toastElement.querySelector('.toast'), {
        autohide: autoDismiss,
        delay: 3000
    });

    toast.show();

    // Remove toast element after it's hidden
    toastElement.querySelector('.toast').addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

// Validation Helpers
function clearValidationErrors(formId) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.querySelectorAll('.is-invalid').forEach(element => {
        element.classList.remove('is-invalid');
    });
    form.querySelectorAll('.invalid-feedback').forEach(element => {
        element.textContent = '';
    });
}

function showValidationErrors(formId, errors) {
    const form = document.getElementById(formId);
    if (!form) return;

    Object.keys(errors).forEach(field => {
        const input = form.querySelector(`[name="${field}"]`);
        if (input) {
            input.classList.add('is-invalid');
            const feedback = input.parentElement.querySelector('.invalid-feedback');
            if (feedback) {
                const message = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                feedback.textContent = message;
            }
        }
    });
}

// Notification System
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