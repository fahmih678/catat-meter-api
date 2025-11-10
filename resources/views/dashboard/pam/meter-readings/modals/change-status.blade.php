<!-- Change Status Modal -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeStatusModalLabel">
                    <i class="bi bi-shield-check me-2"></i>Ubah Status Pembacaan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="changeStatusForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="meter_reading_id" id="change_meter_reading_id">
                <input type="hidden" name="current_status" id="change_current_status">

                <div class="modal-body">
                    <!-- Informasi Pembacaan -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h6 class="card-title mb-3">
                                <i class="bi bi-info-circle me-2"></i>Informasi Pembacaan
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <small class="text-muted">Pelanggan</small>
                                        <div class="fw-bold" id="status_customer_name">-</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <small class="text-muted">No. Meter</small>
                                        <div class="fw-bold" id="status_meter_number">-</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <small class="text-muted">Pemakaian</small>
                                        <div class="fw-bold text-primary" id="status_volume_usage">0.0 m³</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <small class="text-muted">Status Saat Ini</small>
                                        <div class="fw-bold" id="status_current_status">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Selection -->
                    <div class="mb-4">
                        <label for="new_status" class="form-label">
                            <i class="bi bi bi-patch-check me-1"></i>Pilih Status Baru
                            <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-lg" id="new_status" name="new_status" required>
                            <option value="">Pilih Status</option>
                            <option value="draft">Draft</option>
                            <option value="pending">Menunggu</option>
                            <option value="paid">Dibayar</option>
                        </select>
                        <small class="text-muted">Pilih status baru untuk pembacaan meter ini</small>
                    </div>

                    <!-- Status Information Cards -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card border-secondary mb-2" data-status-info="draft">
                                <div class="card-body py-2">
                                    <h6 class="card-title mb-1">
                                        <span class="badge bg-secondary">Draft</span>
                                    </h6>
                                    <small class="text-muted">Status awal, pembacaan belum disubmit</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-warning mb-2" data-status-info="pending">
                                <div class="card-body py-2">
                                    <h6 class="card-title mb-1">
                                        <span class="badge bg-warning text-dark">Menunggu</span>
                                    </h6>
                                    <small class="text-muted">Menunggu proses verifikasi dan pembayaran</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-success mb-2" data-status-info="paid">
                                <div class="card-body py-2">
                                    <h6 class="card-title mb-1">
                                        <span class="badge bg-success">Dibayar</span>
                                    </h6>
                                    <small class="text-muted">Pembayaran telah dilunasi, bill akan dibuat</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alert untuk konfirmasi -->
                    <div id="status_alert" class="alert alert-warning d-none">
                        <h6 class="alert-heading">
                            <i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Perubahan
                        </h6>
                        <p id="status_alert_message" class="mb-0"></p>
                    </div>

                    <!-- Bill Information -->
                    <div id="bill_info" class="alert alert-info d-none">
                        <h6 class="alert-heading">
                            <i class="bi bi-info-circle me-2"></i>Informasi Tagihan
                        </h6>
                        <p id="bill_info_message" class="mb-0"></p>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Ubah Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const changeStatusModal = new bootstrap.Modal(document.getElementById('changeStatusModal'));
        const changeStatusForm = document.getElementById('changeStatusForm');

        // Change status button handler
        document.querySelectorAll('.change-status').forEach(button => {
            button.addEventListener('click', function() {
                const readingId = this.dataset.id;
                const data = JSON.parse(this.dataset.reading);

                // Populate form
                document.getElementById('change_meter_reading_id').value = readingId;
                document.getElementById('change_current_status').value = data.status;

                // Populate information fields
                document.getElementById('status_customer_name').textContent = data
                    .customer_name || '-';
                document.getElementById('status_meter_number').textContent = data
                    .meter_number || '-';
                document.getElementById('status_volume_usage').textContent = number_format(data
                    .volume_usage, 1) + ' m³';

                // Current status display
                const currentStatusEl = document.getElementById('status_current_status');
                const statusBadge = getStatusBadge(data.status);
                currentStatusEl.innerHTML = statusBadge;

                // Reset status selection
                document.getElementById('new_status').value = '';

                // Reset alerts
                resetAlerts();

                changeStatusModal.show();
            });
        });

        // Status change handler
        document.getElementById('new_status').addEventListener('change', function() {
            const currentStatus = document.getElementById('change_current_status').value;
            const newStatus = this.value;

            resetAlerts();

            if (newStatus === currentStatus) {
                showInfo('Status baru sama dengan status saat ini.');
                return;
            }

            // Highlight selected status card
            document.querySelectorAll('[data-status-info]').forEach(card => {
                card.classList.remove('border-primary', 'bg-light');
            });

            const selectedCard = document.querySelector(`[data-status-info="${newStatus}"]`);
            if (selectedCard) {
                selectedCard.classList.add('border-primary', 'bg-light');
            }

            // Show appropriate alerts
            handleStatusChange(currentStatus, newStatus);
        });

        function handleStatusChange(currentStatus, newStatus) {
            const alert = document.getElementById('status_alert');
            const alertMessage = document.getElementById('status_alert_message');
            const billInfo = document.getElementById('bill_info');
            const billMessage = document.getElementById('bill_info_message');

            switch (true) {
                case currentStatus === 'draft' && newStatus === 'pending':
                    showSuccess('Pembacaan akan disubmit untuk verifikasi.');
                    break;

                case currentStatus === 'draft' && newStatus === 'paid':
                    showSuccess(
                    'Pembacaan akan langsung ditandai sebagai lunas dan bill akan dibuat otomatis.');
                    showBillInfo(
                        `Bill akan dibuat untuk pemakaian ${document.getElementById('status_volume_usage').textContent}`
                        );
                    break;

                case currentStatus === 'pending' && newStatus === 'paid':
                    showSuccess('Pembacaan akan ditandai sebagai lunas dan bill akan dibuat otomatis.');
                    showBillInfo(
                        `Bill akan dibuat untuk pemakaian ${document.getElementById('status_volume_usage').textContent}`
                        );
                    break;

                case currentStatus === 'pending' && newStatus === 'draft':
                    showWarning('Pembacaan akan dikembalikan ke status draft. Bill yang ada akan dihapus.');
                    break;

                case currentStatus === 'paid' && newStatus === 'pending':
                    showWarning('Status akan diubah menjadi menunggu. Bill akan ditandai sebagai pending.');
                    break;

                case currentStatus === 'paid' && newStatus === 'draft':
                    showDanger('Pembacaan akan dikembalikan ke status draft. Bill yang ada akan dihapus!');
                    break;

                default:
                    showInfo(
                        `Status akan diubah dari ${getStatusText(currentStatus)} ke ${getStatusText(newStatus)}.`
                        );
            }
        }

        function getStatusBadge(status) {
            const badges = {
                'draft': '<span class="badge bg-secondary">Draft</span>',
                'pending': '<span class="badge bg-warning text-dark">Menunggu</span>',
                'paid': '<span class="badge bg-success">Dibayar</span>'
            };
            return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
        }

        function getStatusText(status) {
            const texts = {
                'draft': 'Draft',
                'pending': 'Menunggu',
                'paid': 'Dibayar'
            };
            return texts[status] || status;
        }

        function showSuccess(message) {
            const alert = document.getElementById('status_alert');
            const alertMessage = document.getElementById('status_alert_message');
            alert.className = 'alert alert-success';
            alertMessage.textContent = message;
            alert.classList.remove('d-none');
        }

        function showWarning(message) {
            const alert = document.getElementById('status_alert');
            const alertMessage = document.getElementById('status_alert_message');
            alert.className = 'alert alert-warning';
            alertMessage.innerHTML = `<strong>⚠️ Perhatian:</strong> ${message}`;
            alert.classList.remove('d-none');
        }

        function showDanger(message) {
            const alert = document.getElementById('status_alert');
            const alertMessage = document.getElementById('status_alert_message');
            alert.className = 'alert alert-danger';
            alertMessage.innerHTML = `<strong>❌ Peringatan:</strong> ${message}`;
            alert.classList.remove('d-none');
        }

        function showInfo(message) {
            const alert = document.getElementById('status_alert');
            const alertMessage = document.getElementById('status_alert_message');
            alert.className = 'alert alert-info';
            alertMessage.textContent = message;
            alert.classList.remove('d-none');
        }

        function showBillInfo(message) {
            const billInfo = document.getElementById('bill_info');
            const billMessage = document.getElementById('bill_info_message');
            billMessage.textContent = message;
            billInfo.classList.remove('d-none');
        }

        function resetAlerts() {
            document.getElementById('status_alert').classList.add('d-none');
            document.getElementById('bill_info').classList.add('d-none');

            document.querySelectorAll('[data-status-info]').forEach(card => {
                card.classList.remove('border-primary', 'bg-light');
            });
        }

        // Form submission
        changeStatusForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const currentStatus = formData.get('current_status');
            const newStatus = formData.get('new_status');

            if (newStatus === currentStatus) {
                alert('Status baru tidak boleh sama dengan status saat ini.');
                return;
            }

            // Confirmation for dangerous operations
            if (currentStatus === 'paid' && newStatus !== 'paid') {
                if (!confirm(
                        '⚠️ Perubahan status dari "Dibayar" akan menghapus atau memodifikasi bill yang terkait. Lanjutkan?'
                        )) {
                    return;
                }
            }

            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;

            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i>Memproses...';

            fetch(`{{ route('pam.meter-readings.change-status', ['pamId' => $pam->id, 'meterReadingId' => ':id']) }}`
                    .replace(':id', formData.get('meter_reading_id')), {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        changeStatusModal.hide();
                        location.reload();
                    } else {
                        alert(data.message || 'Terjadi kesalahan saat mengubah status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat mengubah status');
                })
                .finally(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                });
        });
    });
</script>
