<!-- Edit Meter Reading Modal -->
<div class="modal fade" id="editMeterReadingModal" tabindex="-1" aria-labelledby="editMeterReadingModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editMeterReadingModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Pembacaan Meter
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editMeterReadingForm" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="meter_reading_id" id="meter_reading_id">
                <input type="hidden" name="customer_id" id="customer_id">

                <div class="modal-body">
                    <!-- Informasi Pelanggan (Readonly) -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h6 class="card-title mb-3">
                                <i class="bi bi-info-circle me-2"></i>Informasi Pelanggan
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <small class="text-muted">Pelanggan</small>
                                        <div class="fw-bold" id="info_customer_name">-</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <small class="text-muted">No. Meter</small>
                                        <div class="fw-bold" id="info_meter_number">-</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-2">
                                        <small class="text-muted">Angka Awal</small>
                                        <div class="fw-bold text-primary" id="info_previous_reading">0.0 m³</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-2">
                                        <small class="text-muted">Pemakaian Saat Ini</small>
                                        <div class="fw-bold text-info" id="info_volume_usage">0.0 m³</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-2">
                                        <small class="text-muted">Tanggal Baca</small>
                                        <div class="fw-bold" id="info_reading_at">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Edit Fields -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="edit_current_reading" class="form-label">
                                    <i class="bi bi-arrow-up me-1"></i>Angka Akhir (m³)
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="0.1" class="form-control form-control-lg"
                                    id="edit_current_reading" name="current_reading" required
                                    placeholder="Masukkan angka akhir meter">
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <small class="text-muted">Angka akhir meter yang terbaca</small>
                                    <small class="text-info fw-bold">
                                        <i class="bi bi-droplet me-1"></i>
                                        Pemakaian: <span id="volume_usage_display">0.0</span> m³
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_notes" class="form-label">
                            <i class="bi bi-chat-left-text me-1"></i>Catatan
                        </label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="3"
                            placeholder="Tambahkan catatan atau keterangan..."></textarea>
                        <small class="text-muted">Opsional: Tambahkan catatan mengenai pembacaan ini</small>
                    </div>

                    <div class="mb-3">
                        <label for="edit_photo" class="form-label">
                            <i class="bi bi-camera me-1"></i>Photo Meter
                        </label>
                        <div class="d-flex align-items-start gap-3">
                            <div class="flex-grow-1">
                                <input type="file" class="form-control" id="edit_photo" name="photo"
                                    accept="image/*">
                                <small class="text-muted">Opsional: Ganti photo meter (maksimal 2MB, format: JPG,
                                    PNG)</small>
                            </div>
                            <div id="current_photo_container" class="d-none">
                                <a href="#" id="current_photo_link" target="_blank"
                                    class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-eye me-1"></i>Lihat Photo
                                </a>
                            </div>
                        </div>
                    </div>

                  </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
    // Helper function for number formatting
    function number_format(num, decimals) {
        return parseFloat(num).toFixed(decimals);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const editModal = new bootstrap.Modal(document.getElementById('editMeterReadingModal'));
        const editForm = document.getElementById('editMeterReadingForm');

        // Edit button handler
        document.querySelectorAll('.edit-meter-reading').forEach(button => {
            button.addEventListener('click', function() {
                const readingId = this.dataset.id;
                const data = JSON.parse(this.dataset.reading);

                // Populate form with editable fields only
                document.getElementById('customer_id').value = data.customer_id;
                document.getElementById('meter_reading_id').value = readingId;
                document.getElementById('edit_current_reading').value = data.current_reading;

                // Populate information fields
                document.getElementById('info_customer_name').textContent = data.customer_name || '-';
                document.getElementById('info_meter_number').textContent = data.meter_number || '-';
                document.getElementById('info_previous_reading').textContent = number_format(data.previous_reading, 1) + ' m³';
                document.getElementById('info_volume_usage').textContent = number_format(data.volume_usage, 1) + ' m³';
                document.getElementById('info_reading_at').textContent = data.reading_at || '-';

                // Notes field
                const notesField = document.getElementById('edit_notes');
                if (notesField) {
                    notesField.value = data.notes || '';
                }

                // Store original values for comparison
                editForm.dataset.originalCurrentReading = data.current_reading;
                editForm.dataset.previousReading = data.previous_reading;

                // Handle photo display
                const photoContainer = document.getElementById('current_photo_container');
                const photoLink = document.getElementById('current_photo_link');
                if (data.photo_url && data.photo_url !== '') {
                    photoContainer.classList.remove('d-none');
                    photoLink.href = data.photo_url;
                } else {
                    photoContainer.classList.add('d-none');
                }

                // Initialize volume usage display
                calculateVolumeUsage();

                editModal.show();
            });
        });

        // Calculate volume usage and show preview
        function calculateVolumeUsage() {
            const previous = parseFloat(editForm.dataset.previousReading) || 0;
            const current = parseFloat(document.getElementById('edit_current_reading').value) || 0;
            const usage = Math.max(0, current - previous);

            // Update volume usage display
            const usageDisplay = document.getElementById('volume_usage_display');
            if (usageDisplay) {
                usageDisplay.textContent = usage.toFixed(1);
            }

            // Add visual feedback
            const currentField = document.getElementById('edit_current_reading');
            if (usage < 0) {
                currentField.classList.add('is-invalid');
                if (currentField.parentElement.querySelector('.invalid-feedback')) {
                    currentField.parentElement.querySelector('.invalid-feedback').textContent =
                        'Angka akhir tidak boleh kurang dari angka awal';
                } else {
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.textContent = 'Angka akhir tidak boleh kurang dari angka awal';
                    currentField.parentElement.appendChild(feedback);
                }
            } else {
                currentField.classList.remove('is-invalid');
                const feedback = currentField.parentElement.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.remove();
                }
            }

            return usage;
        }

        // Add event listener for current reading
        const currentReadingField = document.getElementById('edit_current_reading');
        if (currentReadingField) {
            currentReadingField.addEventListener('input', calculateVolumeUsage);
        }

        // Form submission
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            // Validation
            const currentReading = parseFloat(formData.get('current_reading'));
            const previousReading = parseFloat(this.dataset.previousReading);

            if (currentReading < previousReading) {
                alert('Angka akhir tidak boleh lebih kecil dari angka awal (' + previousReading + ' m³)');
                return;
            }

            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;

            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i>Menyimpan...';

            const url = `{{ route('pam.meter-readings.update', ['pamId' => $pam->id, 'meterReadingId' => ':id']) }}`
                .replace(':id', formData.get('meter_reading_id'));

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
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
                    // Show success message
                    const successAlert = document.createElement('div');
                    successAlert.className = 'alert alert-success alert-dismissible fade show';
                    successAlert.innerHTML = `
                        <i class="bi bi-check-circle me-2"></i>
                        ${data.message || 'Data berhasil diperbarui'}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;

                    // Insert alert before the modal
                    const modalDialog = document.querySelector('.modal-dialog');
                    modalDialog.parentNode.insertBefore(successAlert, modalDialog);

                    // Auto hide modal and reload
                    setTimeout(() => {
                        editModal.hide();
                        location.reload();
                    }, 1000);
                } else {
                    alert(data.message || 'Terjadi kesalahan saat menyimpan data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            });
        });
    });
</script>
