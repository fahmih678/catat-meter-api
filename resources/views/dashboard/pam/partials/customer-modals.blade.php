<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel"
    data-bs-backdrop="static" data-bs-keyboard="true" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addCustomerModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Tambah Pelanggan Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="addCustomerForm" method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="customer_number" class="form-label fw-semibold">
                                Nomor Pelanggan <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="customer_number" name="customer_number"
                                    placeholder="Klik tombol Generate" readonly aria-describedby="customerNumberHelp">
                                <button class="btn btn-outline-secondary" type="button" onclick="generateCustomerNumber(event)"
                                    aria-label="Generate nomor pelanggan otomatis">
                                    <i class="bi bi-arrow-clockwise"></i> Generate
                                </button>
                            </div>
                            <div class="invalid-feedback"></div>
                            <small id="customerNumberHelp" class="form-text text-muted">
                                Klik tombol Generate untuk membuat nomor pelanggan otomatis
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold">
                                Nama Pelanggan <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label fw-semibold">
                                Alamat <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="phone" class="form-label fw-semibold">Telepon</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="area_id" class="form-label fw-semibold">
                                Area <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="area_id" name="area_id" required>
                                <option value="">Pilih Area</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="tariff_group_id" class="form-label fw-semibold">
                                Grup Tarif <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="tariff_group_id" name="tariff_group_id" required>
                                <option value="">Pilih Grup Tarif</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="user_id" class="form-label fw-semibold">Pengguna</label>
                            <select class="form-select" id="user_id" name="user_id">
                                <option value="">Pilih Pengguna</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <hr>
                            <h6 class="text-primary mb-3">
                                <i class="bi bi-speedometer2 me-2"></i>Informasi Meter
                            </h6>
                        </div>
                        <div class="col-md-4">
                            <label for="meter_number" class="form-label fw-semibold">Nomor Meter</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="meter_number" name="meter_number"
                                    placeholder="Opsional: Isi jika ada meter" aria-describedby="meterNumberHelp">
                                <button class="btn btn-outline-secondary" type="button" onclick="generateMeterNumber(event)"
                                    aria-label="Generate nomor meter otomatis">
                                    <i class="bi bi-arrow-clockwise"></i> Generate
                                </button>
                            </div>
                            <div class="invalid-feedback"></div>
                            <small id="meterNumberHelp" class="form-text text-muted">
                                Opsional: Generate nomor meter otomatis atau input manual
                            </small>
                        </div>
                        <div class="col-md-4">
                            <label for="installed_at" class="form-label fw-semibold">Tanggal Pasang <span
                                    class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="installed_at" name="installed_at"
                                required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="initial_installed_meter" class="form-label fw-semibold">Awal Meter
                                (m³)</label>
                            <input type="number" step="0.01" min="0" class="form-control"
                                id="initial_installed_meter" name="initial_installed_meter" placeholder="0.00">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label for="meter_notes" class="form-label fw-semibold">Catatan Meter</label>
                            <textarea class="form-control" id="meter_notes" name="meter_notes" rows="2"
                                placeholder="Opsional: Catatan tambahan untuk meter"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                    checked>
                                <label class="form-check-label fw-semibold" for="is_active">
                                    Aktif
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
                        <i class="bi bi-check-circle me-2"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel"
    data-bs-backdrop="static" data-bs-keyboard="true" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editCustomerModalLabel">
                    <i class="bi bi-pencil me-2"></i>Edit Pelanggan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="editCustomerForm" method="POST">
                <input type="hidden" id="edit_customer_id" name="customer_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_customer_number" class="form-label fw-semibold">
                                Nomor Pelanggan <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="edit_customer_number"
                                name="customer_number" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_name" class="form-label fw-semibold">
                                Nama Pelanggan <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label for="edit_address" class="form-label fw-semibold">
                                Alamat <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="edit_address" name="address" rows="2" required></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_phone" class="form-label fw-semibold">Telepon</label>
                            <input type="tel" class="form-control" id="edit_phone" name="phone">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_area_id" class="form-label fw-semibold">
                                Area <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="edit_area_id" name="area_id" required>
                                <option value="">Pilih Area</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_tariff_group_id" class="form-label fw-semibold">
                                Grup Tarif <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="edit_tariff_group_id" name="tariff_group_id" required>
                                <option value="">Pilih Grup Tarif</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_user_id" class="form-label fw-semibold">Pengguna</label>
                            <select class="form-select" id="edit_user_id" name="user_id">
                                <option value="">Pilih Pengguna</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <hr>
                            <h6 class="text-primary mb-3">
                                <i class="bi bi-speedometer2 me-2"></i>Informasi Meter
                            </h6>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">Aksi Meter</label>
                            <select class="form-select" id="edit_meter_action" name="meter_action"
                                onchange="toggleMeterFields()">
                                <option value="">Tidak ada perubahan</option>
                                <option value="add">Tambah meter baru</option>
                                <option value="update">Update meter yang ada</option>
                                <option value="remove">Nonaktifkan meter</option>
                            </select>
                        </div>
                        <div id="editMeterFields" style="display: none;">
                            <div class="col-md-6">
                                <label for="edit_meter_id" class="form-label fw-semibold">Pilih Meter</label>
                                <select class="form-select" id="edit_meter_id" name="meter_id">
                                    <option value="">Pilih Meter</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_meter_number" class="form-label fw-semibold">Nomor Meter</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="edit_meter_number"
                                        name="meter_number" aria-describedby="editMeterNumberHelp">
                                    <button class="btn btn-outline-secondary" type="button" onclick="generateEditMeterNumber(event)"
                                        aria-label="Generate nomor meter otomatis untuk edit">
                                        <i class="bi bi-arrow-clockwise"></i> Generate
                                    </button>
                                </div>
                                <div class="invalid-feedback"></div>
                                <small id="editMeterNumberHelp" class="form-text text-muted">
                                    Generate nomor meter baru untuk update atau tambah meter
                                </small>
                            </div>
                            <div class="col-md-4">
                                <label for="edit_installed_at" class="form-label fw-semibold">Tanggal Pasang</label>
                                <input type="date" class="form-control" id="edit_installed_at"
                                    name="installed_at">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label for="edit_initial_installed_meter" class="form-label fw-semibold">Awal Meter
                                    (m³)</label>
                                <input type="number" step="0.01" min="0" class="form-control"
                                    id="edit_initial_installed_meter" name="initial_installed_meter">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label for="edit_meter_notes" class="form-label fw-semibold">Catatan Meter</label>
                                <input type="text" class="form-control" id="edit_meter_notes" name="meter_notes">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="edit_is_active"
                                    name="is_active">
                                <label class="form-check-label fw-semibold" for="edit_is_active">
                                    Aktif
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle me-2"></i>Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Customer Modal -->
<div class="modal fade" id="viewCustomerModal" tabindex="-1" aria-labelledby="viewCustomerModalLabel"
    data-bs-backdrop="static" data-bs-keyboard="true" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewCustomerModalLabel">
                    <i class="bi bi-eye me-2"></i>Detail Pelanggan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewCustomerContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>