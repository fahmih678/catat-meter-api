<!-- Create Tariff Tier Modal -->
<div class="modal fade" id="createTariffTierModal" tabindex="-1" aria-labelledby="createTariffTierModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title" id="createTariffTierModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Add New Tariff Tier
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createTariffTierForm" method="POST" action="{{ route('pam.tiers.store', $pam->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="tariffTierGroup" class="form-label">Tariff Group <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="tariffTierGroup" name="tariff_group_id" required>
                                <option value="">--Select Tariff Group--</option>
                                @foreach ($tariffGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tariffMeterMin" class="form-label">Min Meter (m³) <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="tariffMeterMin" name="meter_min"
                                   min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tariffMeterMax" class="form-label">Max Meter (m³) <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="tariffMeterMax" name="meter_max"
                                   min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="tariffAmount" class="form-label">Amount (Rp) <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="tariffAmount" name="amount" min="0"
                                step="0.01" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tariffEffectiveFrom" class="form-label">Effective From <span
                                    class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tariffEffectiveFrom" name="effective_from"
                                required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tariffEffectiveTo" class="form-label">Effective To</label>
                            <input type="date" class="form-control" id="tariffEffectiveTo" name="effective_to">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="tariffDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="tariffDescription" name="description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="tariffTierStatus" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="tariffTierStatus" name="is_active" required>
                            <option value="">Select Status</option>
                            <option value="1" selected>Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <small>Tariff tiers define pricing based on meter usage ranges for each tariff group.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-plus-circle me-2"></i>Create Tariff Tier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Tariff Tier Modal -->
<div class="modal fade" id="editTariffTierModal" tabindex="-1" aria-labelledby="editTariffTierModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="editTariffTierModalLabel">
                    <i class="bi bi-pencil me-2"></i>Edit Tariff Tier
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTariffTierForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="tariff_tier_id" id="editTariffTierId">
                <input type="hidden" name="_method" value="PUT">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="editTariffTierGroup" class="form-label">Tariff Group <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="editTariffTierGroup" name="tariff_group_id" required>
                                <option value="">--Select Tariff Group--</option>
                                @foreach ($tariffGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editTariffMeterMin" class="form-label">Min Meter (m³) <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="editTariffMeterMin" name="meter_min"
                                   min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editTariffMeterMax" class="form-label">Max Meter (m³) <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="editTariffMeterMax" name="meter_max"
                                   min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="editTariffAmount" class="form-label">Amount (Rp) <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="editTariffAmount" name="amount"
                                min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editTariffEffectiveFrom" class="form-label">Effective From <span
                                    class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="editTariffEffectiveFrom"
                                name="effective_from" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editTariffEffectiveTo" class="form-label">Effective To</label>
                            <input type="date" class="form-control" id="editTariffEffectiveTo"
                                name="effective_to">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editTariffDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editTariffDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editTariffTierStatus" class="form-label">Status <span
                                class="text-danger">*</span></label>
                        <select class="form-select" id="editTariffTierStatus" name="is_active" required>
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-pencil me-2"></i>Update Tariff Tier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>