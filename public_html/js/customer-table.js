/**
 * Customer Table JavaScript
 * Handles customer table interactions and meter management
 */

// Global variables
let currentCustomerId = null;
let currentPamId = window.currentPamId;

// Debug: Log when script is loaded
console.log('customer-table.js loaded successfully');
console.log('Initial currentPamId:', currentPamId);

/**
 * View customer details
 */
function viewCustomer(customerId) {
    console.log('viewCustomer called with customerId:', customerId);

    const modalElement = document.getElementById('viewCustomerModal');
    const modal = new bootstrap.Modal(modalElement);

    // Show loading first
    document.getElementById('viewCustomerContent').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Memuat data pelanggan...</p>
        </div>
    `;

    // Fix modal accessibility by removing aria-hidden temporarily
    modalElement.removeAttribute('aria-hidden');
    modal.show();

    // Re-add aria-hidden after modal is fully shown
    setTimeout(() => {
        modalElement.setAttribute('aria-hidden', 'true');
    }, 300);

    // Load customer data
    fetch(`customers/${customerId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const customer = data.data;
                document.getElementById('viewCustomerContent').innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="bi bi-person me-2"></i>Informasi Pelanggan
                            </h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>No. Pelanggan:</strong></td>
                                    <td><span class="badge bg-info text-dark">${customer.customer_number}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Nama:</strong></td>
                                    <td>${customer.name}</td>
                                </tr>
                                <tr>
                                    <td><strong>Alamat:</strong></td>
                                    <td>${customer.address || '-'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Telepon:</strong></td>
                                    <td>${customer.phone || '-'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge ${customer.is_active ? 'bg-success' : 'bg-warning'}">
                                            ${customer.is_active ? 'Aktif' : 'Tidak Aktif'}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="bi bi-geo-alt me-2"></i>Area & Tarif
                            </h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Area:</strong></td>
                                    <td>
                                        ${customer.area ?
                                            `<span class="badge bg-secondary">${customer.area.name}</span>` :
                                            '<span class="text-muted">-</span>'}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Grup Tarif:</strong></td>
                                    <td>
                                        ${customer.tariffGroup ?
                                            `<span class="badge bg-warning text-dark">${customer.tariffGroup.name}</span>` :
                                            '<span class="text-muted">-</span>'}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Pengguna:</strong></td>
                                    <td>
                                        ${customer.user ?
                                            `<span class="badge bg-success">${customer.user.name}</span>` :
                                            '<span class="text-muted">-</span>'}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Jumlah Meter:</strong></td>
                                    <td>
                                        ${customer.meters ?
                                            `<span class="badge bg-info">${customer.meters.length} meter</span>` :
                                            '<span class="text-muted">0 meter</span>'}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                `;
            } else {
                showToast(data.message || 'Gagal memuat data pelanggan', 'error');
                modal.hide();
            }
        })
        .catch(error => {
            console.error('Error loading customer:', error);
            showToast('Gagal memuat data pelanggan', 'error');
            modal.hide();
        });
}

/**
 * Edit customer
 */
function editCustomer(customerId) {
    console.log('editCustomer called with customerId:', customerId);

    // Load customer data for editing
    fetch(`customers/${customerId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const customer = data.data;

                // Set form values
                document.getElementById('edit_customer_id').value = customer.id;
                document.getElementById('edit_customer_number').value = customer.customer_number;
                document.getElementById('edit_name').value = customer.name;
                document.getElementById('edit_address').value = customer.address || '';
                document.getElementById('edit_phone').value = customer.phone || '';
                document.getElementById('edit_area_id').value = customer.area_id || '';
                document.getElementById('edit_tariff_group_id').value = customer.tariff_group_id || '';
                document.getElementById('edit_user_id').value = customer.user_id || '';
                document.getElementById('edit_is_active').checked = customer.is_active;

                // Show modal with accessibility fix
                const modalElement = document.getElementById('editCustomerModal');
                const modal = new bootstrap.Modal(modalElement);

                // Fix modal accessibility
                modalElement.removeAttribute('aria-hidden');
                modal.show();

                setTimeout(() => {
                    modalElement.setAttribute('aria-hidden', 'true');
                }, 300);
            } else {
                showToast(data.message || 'Gagal memuat data pelanggan', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading customer for edit:', error);
            showToast('Gagal memuat data pelanggan', 'error');
        });
}

/**
 * Delete customer
 */
function deleteCustomer(customerId, customerName) {
    console.log('deleteCustomer called with customerId:', customerId, 'customerName:', customerName);

    if (!confirm(`Apakah Anda yakin ingin menghapus pelanggan "${customerName}"?`)) {
        return;
    }

    fetch(`customers/${customerId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            // Refresh the page or table
            location.reload();
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting customer:', error);
        showToast('Gagal menghapus pelanggan', 'error');
    });
}

/**
 * Export customers
 */
function exportCustomers() {
    console.log('exportCustomers called');

    // Get current filters from URL
    const urlParams = new URLSearchParams(window.location.search);
    const exportUrl = `customers/export?${urlParams.toString()}`;

    showToast('Mengekspor data pelanggan...', 'info');

    fetch(exportUrl)
        .then(response => {
            if (response.ok) {
                return response.blob();
            }
            throw new Error('Export failed');
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `pelanggan_export_${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            showToast('Export berhasil diunduh', 'success');
        })
        .catch(error => {
            console.error('Error exporting customers:', error);
            showToast('Gagal mengekspor data pelanggan', 'error');
        });
}

/**
 * Refresh table
 */
function refreshTable() {
    console.log('refreshTable called');
    showToast('Memuat ulang data...', 'info');
    location.reload();
}

/**
 * Generate customer number
 */
function generateCustomerNumber(event) {
    if (event) {
        event.preventDefault();
    }

    const customerNumberField = document.getElementById('customer_number');
    const generateBtn = event ? event.target : null;

    if (generateBtn) {
        generateBtn.disabled = true;
        generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generate...';
    }

    fetch(`generate-customer-number`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                customerNumberField.value = data.customer_number;
            } else {
                showToast(data.message || 'Gagal generate nomor pelanggan', 'error');
            }
        })
        .catch(error => {
            console.error('Error generating customer number:', error);
            showToast('Gagal generate nomor pelanggan', 'error');
        })
        .finally(() => {
            if (generateBtn) {
                generateBtn.disabled = false;
                generateBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Generate';
            }
        });
}

/**
 * Generate meter number (for add customer modal)
 */
function generateMeterNumber(event) {
    if (event) {
        event.preventDefault();
    }

    const meterNumberField = document.getElementById('meter_number');
    const generateBtn = event ? event.target : null;

    if (generateBtn) {
        generateBtn.disabled = true;
        generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generate...';
    }

    fetch(`generate-meter-number`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                meterNumberField.value = data.meter_number;
            } else {
                showToast(data.message || 'Gagal generate nomor meter', 'error');
            }
        })
        .catch(error => {
            console.error('Error generating meter number:', error);
            showToast('Gagal generate nomor meter', 'error');
        })
        .finally(() => {
            if (generateBtn) {
                generateBtn.disabled = false;
                generateBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Generate';
            }
        });
}

/**
 * Show add customer modal
 */
function showAddCustomerModal() {
    console.log('showAddCustomerModal called');

    // Reset form
    const form = document.getElementById('addCustomerForm');
    if (form) {
        form.reset();
    }

    // Reset customer number field
    const customerNumberField = document.getElementById('customer_number');
    if (customerNumberField) {
        customerNumberField.value = '';
        customerNumberField.readOnly = true;
    }

    // Reset meter number field
    const meterNumberField = document.getElementById('meter_number');
    if (meterNumberField) {
        meterNumberField.value = '';
        meterNumberField.readOnly = true;
    }

    // Set default date for installation
    const installedAtField = document.getElementById('installed_at');
    if (installedAtField) {
        installedAtField.valueAsDate = new Date();
    }

    // Show modal with accessibility fix
    const modalElement = document.getElementById('addCustomerModal');
    const modal = new bootstrap.Modal(modalElement);

    modalElement.removeAttribute('aria-hidden');
    modal.show();

    setTimeout(() => {
        modalElement.setAttribute('aria-hidden', 'true');
    }, 300);
}

/**
 * Reset filters
 */
function resetFilters() {
    console.log('resetFilters called');

    // Reset form values
    document.getElementById('search').value = '';
    document.getElementById('filter_area_id').value = '';
    document.getElementById('status').value = '';
    document.getElementById('per_page').value = '10';

    // Submit form to reset
    document.getElementById('filterForm').submit();
}

/**
 * Apply filters
 */
function applyFilters() {
    console.log('applyFilters called');
    document.getElementById('filterForm').submit();
}

// Expose all functions to global scope for onclick handlers
window.viewCustomer = viewCustomer;
window.editCustomer = editCustomer;
window.deleteCustomer = deleteCustomer;
window.viewCustomerMeters = viewCustomerMeters;
window.viewMeterReadings = viewMeterReadings;
window.editMeter = editMeter;
window.deleteMeter = deleteMeter;
window.showAddMeterModal = showAddMeterModal;
window.showAddReadingModal = showAddReadingModal;
window.showAddCustomerModal = showAddCustomerModal;
window.editReading = editReading;
window.deleteReading = deleteReading;
window.backToCustomerMeters = backToCustomerMeters;
window.exportCustomers = exportCustomers;
window.refreshTable = refreshTable;
window.generateCustomerNumber = generateCustomerNumber;
window.generateMeterNumber = generateMeterNumber;
window.resetFilters = resetFilters;
window.applyFilters = applyFilters;

// Fallback: get PAM ID from URL if not properly set
if (!currentPamId || currentPamId === 0) {
    const pathParts = window.location.pathname.split('/');
    const pamIndex = pathParts.indexOf('pam');
    if (pamIndex !== -1 && pathParts.length > pamIndex + 1) {
        currentPamId = pathParts[pamIndex + 1];
    }
}

/**
 * View customer meters
 */
function viewCustomerMeters(customerId) {
    console.log('viewCustomerMeters called with customerId:', customerId);
    console.log('currentPamId:', currentPamId);

    currentCustomerId = customerId;

    const modalElement = document.getElementById('customerMetersModal');
    const modal = new bootstrap.Modal(modalElement);

    // Show loading
    document.getElementById('customerInfoSection').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Memuat data pelanggan...</p>
        </div>
    `;
    document.getElementById('customerMetersTableBody').innerHTML = `
        <tr>
            <td colspan="7" class="text-center">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <span class="ms-2">Memuat data meter...</span>
            </td>
        </tr>
    `;

    // Fix modal accessibility
    modalElement.removeAttribute('aria-hidden');
    modal.show();

    setTimeout(() => {
        modalElement.setAttribute('aria-hidden', 'true');
    }, 300);

    // Load customer info and meters
    Promise.all([
        loadCustomerInfo(customerId),
        loadCustomerMeters(customerId)
    ]).catch(error => {
        console.error('Error loading customer data:', error);
        showToast('Gagal memuat data pelanggan', 'error');
        modal.hide();
    });
}

/**
 * Load customer information
 */
function loadCustomerInfo(customerId) {
    return fetch(`customers/${customerId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const customer = data.data;
                document.getElementById('customerInfoSection').innerHTML = `
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title mb-2">${customer.name}</h6>
                            <p class="card-text mb-1">
                                <strong>No. Pelanggan:</strong>
                                <span class="badge bg-info text-dark">${customer.customer_number}</span>
                            </p>
                            <p class="card-text mb-1">
                                <strong>Alamat:</strong> ${customer.address || '-'}
                            </p>
                            <p class="card-text mb-1">
                                <strong>Telepon:</strong> ${customer.phone || '-'}
                            </p>
                            <p class="card-text mb-0">
                                <strong>Status:</strong>
                                <span class="badge ${customer.is_active ? 'bg-success' : 'bg-warning'}">
                                    ${customer.is_active ? 'Aktif' : 'Tidak Aktif'}
                                </span>
                            </p>
                        </div>
                    </div>
                `;
            } else {
                throw new Error(data.message || 'Gagal memuat data pelanggan');
            }
        });
}

/**
 * Load customer meters
 */
function loadCustomerMeters(customerId) {
    return fetch(`customers/${customerId}/meters`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderCustomerMetersTable(data.data);
            } else {
                throw new Error(data.message || 'Gagal memuat data meter');
            }
        });
}

/**
 * Render customer meters table
 */
function renderCustomerMetersTable(meters) {
    const tbody = document.getElementById('customerMetersTableBody');

    if (meters.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <div class="text-muted">
                        <i class="bi bi-speedometer2 fs-1 d-block mb-2"></i>
                        <p>Belum ada meter untuk pelanggan ini</p>
                        <button class="btn btn-success btn-sm" onclick="showAddMeterModal()">
                            <i class="bi bi-plus-circle me-1"></i>Tambah Meter Pertama
                        </button>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = meters.map((meter, index) => `
        <tr>
            <td>${index + 1}</td>
            <td>
                <span class="badge bg-info">${meter.meter_number}</span>
            </td>
            <td>${formatDate(meter.installed_at)}</td>
            <td>${meter.initial_installed_meter || 0} m³</td>
            <td>
                <span class="badge ${meter.is_active ? 'bg-success' : 'bg-warning'}">
                    ${meter.is_active ? 'Aktif' : 'Tidak Aktif'}
                </span>
            </td>
            <td>${meter.notes || '-'}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-info" onclick="viewMeterReadings(${meter.id}, '${meter.meter_number}')"
                            title="Lihat Pembacaan">
                        <i class="bi bi-clipboard-data"></i>
                    </button>
                    <button class="btn btn-outline-warning" onclick="editMeter(${meter.id})"
                            title="Edit Meter">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteMeter(${meter.id}, '${meter.meter_number}')"
                            title="Hapus Meter">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

/**
 * Show add meter modal
 */
function showAddMeterModal() {
    // Reset form
    document.getElementById('addEditMeterForm').reset();
    document.getElementById('meter_id').value = '';
    document.getElementById('meter_customer_id').value = currentCustomerId;
    document.getElementById('meter_action').value = 'add';

    // Update modal header
    document.getElementById('addEditMeterModalHeader').className = 'modal-header bg-success text-white';
    document.getElementById('addEditMeterModalLabel').innerHTML = `
        <i class="bi bi-plus-circle me-2"></i>Tambah Meter Baru
    `;
    document.getElementById('saveMeterBtn').className = 'btn btn-success';
    document.getElementById('saveMeterBtn').innerHTML = `
        <i class="bi bi-check-circle me-2"></i>Simpan Meter
    `;

    // Generate initial meter number
    generateAddEditMeterNumber();

    // Show modal with accessibility fix
    const modalElement = document.getElementById('addEditMeterModal');
    const modal = new bootstrap.Modal(modalElement);

    modalElement.removeAttribute('aria-hidden');
    modal.show();

    setTimeout(() => {
        modalElement.setAttribute('aria-hidden', 'true');
    }, 300);
}

/**
 * Show edit meter modal
 */
function editMeter(meterId) {
    // Load meter data
    fetch(`meters/${meterId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const meter = data.data;

                // Set form data
                document.getElementById('meter_id').value = meter.id;
                document.getElementById('meter_customer_id').value = meter.customer_id;
                document.getElementById('meter_action').value = 'edit';
                document.getElementById('add_edit_meter_number').value = meter.meter_number;
                document.getElementById('add_edit_installed_at').value = meter.installed_at;
                document.getElementById('add_edit_initial_installed_meter').value = meter.initial_installed_meter || '';
                document.getElementById('add_edit_meter_notes').value = meter.notes || '';
                document.getElementById('add_edit_meter_is_active').checked = meter.is_active;

                // Update modal header
                document.getElementById('addEditMeterModalHeader').className = 'modal-header bg-warning text-dark';
                document.getElementById('addEditMeterModalLabel').innerHTML = `
                    <i class="bi bi-pencil me-2"></i>Edit Meter
                `;
                document.getElementById('saveMeterBtn').className = 'btn btn-warning';
                document.getElementById('saveMeterBtn').innerHTML = `
                    <i class="bi bi-check-circle me-2"></i>Update Meter
                `;

                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('addEditMeterModal'));
                modal.show();
            } else {
                showToast(data.message || 'Gagal memuat data meter', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading meter data:', error);
            showToast('Gagal memuat data meter', 'error');
        });
}

/**
 * Delete meter
 */
function deleteMeter(meterId, meterNumber) {
    if (!confirm(`Apakah Anda yakin ingin menghapus meter "${meterNumber}"?`)) {
        return;
    }

    fetch(`meters/${meterId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            loadCustomerMeters(currentCustomerId);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting meter:', error);
        showToast('Gagal menghapus meter', 'error');
    });
}

/**
 * View meter readings
 */
function viewMeterReadings(meterId, meterNumber) {
    console.log('viewMeterReadings called with meterId:', meterId, 'meterNumber:', meterNumber);

    // Close customer meters modal first
    const metersModal = bootstrap.Modal.getInstance(document.getElementById('customerMetersModal'));
    if (metersModal) {
        metersModal.hide();
    }

    // Show loading state
    showToast(`Memuat data pembacaan meter ${meterNumber}...`, 'info');

    // Open modal with meter readings
    showMeterReadingsModal(meterId, meterNumber);
}

/**
 * Show meter readings in modal (alternative option)
 */
function showMeterReadingsModal(meterId, meterNumber) {
    // Create modal dynamically if not exists
    let readingsModal = document.getElementById('meterReadingsModal');
    if (!readingsModal) {
        const modalHtml = `
            <div class="modal fade" id="meterReadingsModal" tabindex="-1" aria-labelledby="meterReadingsModalLabel"
                data-bs-backdrop="static" data-bs-keyboard="true" role="dialog">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="meterReadingsModalLabel">
                                <i class="bi bi-speedometer2 me-2"></i>Pembacaan Meter: <span id="meterNumberDisplay"></span>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <button class="btn btn-success" onclick="showAddReadingModal()">
                                        <i class="bi bi-plus-circle me-2"></i>Tambah Pembacaan Baru
                                    </button>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button class="btn btn-outline-secondary" onclick="backToCustomerMeters()">
                                        <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar Meter
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover" id="meterReadingsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal Baca</th>
                                            <th>Awal (m³)</th>
                                            <th>Akhir (m³)</th>
                                            <th>Pemakaian (m³)</th>
                                            <th>Status</th>
                                            <th>Catatan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="meterReadingsTableBody">
                                        <tr>
                                            <td colspan="8" class="text-center">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <span class="ms-2">Memuat data pembacaan...</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-2"></i>Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        readingsModal = document.getElementById('meterReadingsModal');
    }

    // Set meter number display and store current meter ID
    document.getElementById('meterNumberDisplay').textContent = meterNumber;
    setCurrentMeterId(meterId);

    // Show modal
    const modal = new bootstrap.Modal(readingsModal);
    modal.show();

    // Load meter readings
    loadMeterReadings(meterId);
}

/**
 * Load meter readings data
 */
function loadMeterReadings(meterId) {
    fetch(`meters/${meterId}/readings`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderMeterReadingsTable(data.data);
            } else {
                showToast(data.message || 'Gagal memuat data pembacaan', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading meter readings:', error);
            showToast('Gagal memuat data pembacaan', 'error');
        });
}

/**
 * Render meter readings table
 */
function renderMeterReadingsTable(readings) {
    const tbody = document.getElementById('meterReadingsTableBody');

    if (readings.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4">
                    <div class="text-muted">
                        <i class="bi bi-clipboard-data fs-1 d-block mb-2"></i>
                        <p>Belum ada data pembacaan untuk meter ini</p>
                        <button class="btn btn-success btn-sm" onclick="showAddReadingModal()">
                            <i class="bi bi-plus-circle me-1"></i>Tambah Pembacaan Pertama
                        </button>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = readings.map((reading, index) => `
        <tr>
            <td>${index + 1}</td>
            <td>${formatDate(reading.reading_date)}</td>
            <td>${reading.previous_reading || 0} m³</td>
            <td>${reading.current_reading} m³</td>
            <td>
                <span class="badge bg-info">${reading.usage} m³</span>
            </td>
            <td>
                <span class="badge ${reading.status === 'verified' ? 'bg-success' : 'bg-warning'}">
                    ${reading.status === 'verified' ? 'Terverifikasi' : 'Menunggu Verifikasi'}
                </span>
            </td>
            <td>${reading.notes || '-'}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="editReading(${reading.id})"
                            title="Edit Pembacaan">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteReading(${reading.id})"
                            title="Hapus Pembacaan">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

/**
 * Back to customer meters modal
 */
function backToCustomerMeters() {
    // Close readings modal
    const readingsModal = bootstrap.Modal.getInstance(document.getElementById('meterReadingsModal'));
    if (readingsModal) {
        readingsModal.hide();
    }

    // Reopen customer meters modal
    viewCustomerMeters(currentCustomerId);
}

/**
 * Show add reading modal
 */
function showAddReadingModal() {
    // Get current meter info from the modal title
    const meterNumberDisplay = document.getElementById('meterNumberDisplay');
    const meterNumber = meterNumberDisplay ? meterNumberDisplay.textContent : '';

    // Create add reading modal dynamically if not exists
    let addReadingModal = document.getElementById('addReadingModal');
    if (!addReadingModal) {
        const modalHtml = `
            <div class="modal fade" id="addReadingModal" tabindex="-1" aria-labelledby="addReadingModalLabel"
                data-bs-backdrop="static" data-bs-keyboard="true" role="dialog">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title" id="addReadingModalLabel">
                                <i class="bi bi-plus-circle me-2"></i>Tambah Pembacaan Meter Baru
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <form id="addReadingForm" method="POST">
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="reading_date" class="form-label fw-semibold">
                                            Tanggal Pembacaan <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" class="form-control" id="reading_date" name="reading_date" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="current_reading" class="form-label fw-semibold">
                                            Angka Meter Akhir (m³) <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" step="0.01" min="0" class="form-control" id="current_reading"
                                            name="current_reading" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="previous_reading" class="form-label fw-semibold">
                                            Angka Meter Awal (m³)
                                        </label>
                                        <input type="number" step="0.01" min="0" class="form-control"
                                            id="previous_reading" name="previous_reading" placeholder="0.00">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="reader_name" class="form-label fw-semibold">Nama Pembaca</label>
                                        <input type="text" class="form-control" id="reader_name" name="reader_name"
                                            placeholder="Nama pembaca meter">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-12">
                                        <label for="reading_notes" class="form-label fw-semibold">Catatan</label>
                                        <textarea class="form-control" id="reading_notes" name="notes" rows="2"
                                            placeholder="Opsional: Catatan tambahan untuk pembacaan"></textarea>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" id="reading_status" name="status">
                                            <label class="form-check-label fw-semibold" for="reading_status">
                                                Terverifikasi
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle me-2"></i>Batal
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle me-2"></i>Simpan Pembacaan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        addReadingModal = document.getElementById('addReadingModal');

        // Add form submission handler
        document.getElementById('addReadingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            saveReading();
        });
    }

    // Set today's date as default
    document.getElementById('reading_date').valueAsDate = new Date();

    // Show modal
    const modal = new bootstrap.Modal(addReadingModal);
    modal.show();
}

/**
 * Save reading (create new)
 */
function saveReading() {
    const form = document.getElementById('addReadingForm');
    const formData = new FormData(form);

    // Get current meter ID (you might need to store this somewhere accessible)
    const currentMeterId = getCurrentMeterId();

    if (!currentMeterId) {
        showToast('Meter ID tidak ditemukan', 'error');
        return;
    }

    fetch(`meters/${currentMeterId}/readings`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            reading_date: formData.get('reading_date'),
            current_reading: formData.get('current_reading'),
            previous_reading: formData.get('previous_reading') || 0,
            notes: formData.get('notes'),
            status: formData.get('status') ? 'verified' : 'pending',
            reader_name: formData.get('reader_name')
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('addReadingModal')).hide();
            loadMeterReadings(currentMeterId);
        } else {
            if (data.errors) {
                // Handle validation errors
                let errorMessage = 'Validasi gagal:\n';
                Object.values(data.errors).forEach(errors => {
                    errors.forEach(error => {
                        errorMessage += `• ${error}\n`;
                    });
                });
                showToast(errorMessage, 'error');
            } else {
                showToast(data.message || 'Gagal menyimpan pembacaan', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error saving reading:', error);
        showToast('Gagal menyimpan pembacaan', 'error');
    });
}

/**
 * Edit reading
 */
function editReading(readingId) {
    // This would open an edit modal pre-filled with reading data
    showToast('Fitur edit pembacaan dalam pengembangan', 'info');
}

/**
 * Delete reading
 */
function deleteReading(readingId) {
    if (!confirm('Apakah Anda yakin ingin menghapus pembacaan ini?')) {
        return;
    }

    const currentMeterId = getCurrentMeterId();
    if (!currentMeterId) {
        showToast('Meter ID tidak ditemukan', 'error');
        return;
    }

    fetch(`meters/${currentMeterId}/readings/${readingId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            loadMeterReadings(currentMeterId);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting reading:', error);
        showToast('Gagal menghapus pembacaan', 'error');
    });
}

/**
 * Get current meter ID (helper function)
 */
function getCurrentMeterId() {
    // You can store this in a global variable or get it from the current context
    // For now, let's assume we have it stored somewhere
    return window.currentMeterId || null;
}

/**
 * Set current meter ID (helper function)
 */
function setCurrentMeterId(meterId) {
    window.currentMeterId = meterId;
}

/**
 * Generate meter number for add/edit modal
 */
function generateAddEditMeterNumber(event) {
    if (event) {
        event.preventDefault();
    }

    const meterNumberField = document.getElementById('add_edit_meter_number');
    const generateBtn = event ? event.target : null;

    if (generateBtn) {
        generateBtn.disabled = true;
        generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generate...';
    }

    fetch(`customers/${currentCustomerId}/generate-meter-number`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                meterNumberField.value = data.meter_number;
            } else {
                showToast(data.message || 'Gagal generate nomor meter', 'error');
            }
        })
        .catch(error => {
            console.error('Error generating meter number:', error);
            showToast('Gagal generate nomor meter', 'error');
        })
        .finally(() => {
            if (generateBtn) {
                generateBtn.disabled = false;
                generateBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Generate';
            }
        });
}

/**
 * Format date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    // Create toast container if not exists
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }

    const toastId = 'toast-' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';

    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);

    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement);
    toast.show();

    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

// Handle form submissions
document.addEventListener('DOMContentLoaded', function() {
    // Add/Edit Customer Form Handler
    const addCustomerForm = document.getElementById('addCustomerForm');
    if (addCustomerForm) {
        addCustomerForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('customers', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    customer_number: formData.get('customer_number'),
                    name: formData.get('name'),
                    address: formData.get('address'),
                    phone: formData.get('phone'),
                    area_id: formData.get('area_id'),
                    tariff_group_id: formData.get('tariff_group_id'),
                    user_id: formData.get('user_id'),
                    meter_number: formData.get('meter_number'),
                    installed_at: formData.get('installed_at'),
                    initial_installed_meter: formData.get('initial_installed_meter'),
                    meter_notes: formData.get('meter_notes'),
                    is_active: formData.get('is_active') ? 1 : 0
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('addCustomerModal')).hide();
                    location.reload();
                } else {
                    if (data.errors) {
                        let errorMessage = 'Validasi gagal:\n';
                        Object.values(data.errors).forEach(errors => {
                            errors.forEach(error => {
                                errorMessage += `• ${error}\n`;
                            });
                        });
                        showToast(errorMessage, 'error');
                    } else {
                        showToast(data.message || 'Gagal menyimpan pelanggan', 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error saving customer:', error);
                showToast('Gagal menyimpan pelanggan', 'error');
            });
        });
    }

    // Edit Customer Form Handler
    const editCustomerForm = document.getElementById('editCustomerForm');
    if (editCustomerForm) {
        editCustomerForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const customerId = formData.get('customer_id');

            fetch(`customers/${customerId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    customer_number: formData.get('customer_number'),
                    name: formData.get('name'),
                    address: formData.get('address'),
                    phone: formData.get('phone'),
                    area_id: formData.get('area_id'),
                    tariff_group_id: formData.get('tariff_group_id'),
                    user_id: formData.get('user_id'),
                    is_active: formData.get('is_active') ? 1 : 0
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('editCustomerModal')).hide();
                    location.reload();
                } else {
                    if (data.errors) {
                        let errorMessage = 'Validasi gagal:\n';
                        Object.values(data.errors).forEach(errors => {
                            errors.forEach(error => {
                                errorMessage += `• ${error}\n`;
                            });
                        });
                        showToast(errorMessage, 'error');
                    } else {
                        showToast(data.message || 'Gagal memperbarui pelanggan', 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error updating customer:', error);
                showToast('Gagal memperbarui pelanggan', 'error');
            });
        });
    }

    // Add/Edit Meter Form Handler
    const addEditMeterForm = document.getElementById('addEditMeterForm');
    if (addEditMeterForm) {
        addEditMeterForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const meterId = document.getElementById('meter_id').value;
            const action = document.getElementById('meter_action').value;
            const isEdit = action === 'edit';

            const url = isEdit ?
                `meters/${meterId}` :
                `customers/${currentCustomerId}/meters`;

            const method = isEdit ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    meter_number: formData.get('meter_number'),
                    installed_at: formData.get('installed_at'),
                    initial_installed_meter: formData.get('initial_installed_meter'),
                    notes: formData.get('notes'),
                    is_active: formData.get('is_active') ? 1 : 0
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('addEditMeterModal')).hide();
                    loadCustomerMeters(currentCustomerId);
                } else {
                    if (data.errors) {
                        // Handle validation errors
                        let errorMessage = 'Validasi gagal:\n';
                        Object.values(data.errors).forEach(errors => {
                            errors.forEach(error => {
                                errorMessage += `• ${error}\n`;
                            });
                        });
                        showToast(errorMessage, 'error');
                    } else {
                        showToast(data.message || 'Gagal menyimpan meter', 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error saving meter:', error);
                showToast('Gagal menyimpan meter', 'error');
            });
        });
    }

    // Load form data when modals are shown
    const addCustomerModal = document.getElementById('addCustomerModal');
    if (addCustomerModal) {
        addCustomerModal.addEventListener('show.bs.modal', function() {
            loadCustomerFormData();
        });
    }

    const editCustomerModal = document.getElementById('editCustomerModal');
    if (editCustomerModal) {
        editCustomerModal.addEventListener('show.bs.modal', function() {
            loadCustomerFormData();
        });
    }
});

/**
 * Load customer form data (areas, tariff groups, users)
 */
function loadCustomerFormData() {
    console.log('Loading customer form data...');

    fetch('customers/form-data')
        .then(response => response.json())
        .then(data => {
            console.log('Form data response:', data);

            if (data.success) {
                const areas = data.data?.areas || data.areas || [];
                const tariffGroups = data.data?.tariff_groups || data.tariffGroups || data.tariff_groups || [];
                const users = data.data?.users || data.users || [];

                console.log('Areas:', areas);
                console.log('Tariff Groups:', tariffGroups);
                console.log('Users:', users);

                // Populate area select
                const areaSelect = document.getElementById('area_id');
                const editAreaSelect = document.getElementById('edit_area_id');

                if (areaSelect && Array.isArray(areas)) {
                    areaSelect.innerHTML = '<option value="">Pilih Area</option>' +
                        areas.map(area => `<option value="${area.id}">${area.name}</option>`).join('');
                }

                if (editAreaSelect && Array.isArray(areas)) {
                    editAreaSelect.innerHTML = '<option value="">Pilih Area</option>' +
                        areas.map(area => `<option value="${area.id}">${area.name}</option>`).join('');
                }

                // Populate tariff group select
                const tariffGroupSelect = document.getElementById('tariff_group_id');
                const editTariffGroupSelect = document.getElementById('edit_tariff_group_id');

                if (tariffGroupSelect && Array.isArray(tariffGroups)) {
                    tariffGroupSelect.innerHTML = '<option value="">Pilih Grup Tarif</option>' +
                        tariffGroups.map(group => `<option value="${group.id}">${group.name}</option>`).join('');
                }

                if (editTariffGroupSelect && Array.isArray(tariffGroups)) {
                    editTariffGroupSelect.innerHTML = '<option value="">Pilih Grup Tarif</option>' +
                        tariffGroups.map(group => `<option value="${group.id}">${group.name}</option>`).join('');
                }

                // Populate user select
                const userSelect = document.getElementById('user_id');
                const editUserSelect = document.getElementById('edit_user_id');

                if (userSelect && Array.isArray(users)) {
                    userSelect.innerHTML = '<option value="">Pilih Pengguna</option>' +
                        users.map(user => `<option value="${user.id}">${user.name}</option>`).join('');
                }

                if (editUserSelect && Array.isArray(users)) {
                    editUserSelect.innerHTML = '<option value="">Pilih Pengguna</option>' +
                        users.map(user => `<option value="${user.id}">${user.name}</option>`).join('');
                }

                console.log('Form data loaded successfully');
            } else {
                console.error('Form data API returned error:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading form data:', error);

            // Fallback: try to populate with empty options to prevent broken UI
            const selects = ['area_id', 'edit_area_id', 'tariff_group_id', 'edit_tariff_group_id', 'user_id', 'edit_user_id'];
            selects.forEach(selectId => {
                const select = document.getElementById(selectId);
                if (select) {
                    select.innerHTML = '<option value="">Data tidak tersedia</option>';
                }
            });
        });
}