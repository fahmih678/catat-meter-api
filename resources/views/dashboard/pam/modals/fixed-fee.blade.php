<!-- Create Fixed Fee Modal -->
<div class="modal fade" id="createFixedFeeModal" tabindex="-1" aria-labelledby="createFixedFeeModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title" id="createFixedFeeModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Add New Fixed Fee
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createFixedFeeForm" method="POST" action="{{ route('pam.fixed-fees.store', $pam->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="fixedFeeGroup" class="form-label">Tariff Group <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="fixedFeeGroup" name="tariff_group_id" required>
                                <option value="">--Select Tariff Group--</option>
                                @foreach ($tariffGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="fixedFeeName" class="form-label">Fee Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="fixedFeeName" name="name" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="fixedFeeAmount" class="form-label">Amount <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="fixedFeeAmount" name="amount" min="0"
                                step="0.01" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fixedEffectiveFrom" class="form-label">Effective From <span
                                    class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="fixedEffectiveFrom" name="effective_from"
                                required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fixedEffectiveTo" class="form-label">Effective To <span
                                    class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="fixedEffectiveTo" name="effective_to">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="fixedFeeDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="fixedFeeDescription" name="description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="fixedFeeStatus" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="fixedFeeStatus" name="is_active" required>
                            <option value="">Select Status</option>
                            <option value="1" selected>Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <small>Fixed fees are recurring charges that are applied to customers regardless of
                            usage.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-plus-circle me-2"></i>Create Fixed Fee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Fixed Fee Modal -->
<div class="modal fade" id="editFixedFeeModal" tabindex="-1" aria-labelledby="editFixedFeeModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="editFixedFeeModalLabel">
                    <i class="bi bi-pencil me-2"></i>Edit Fixed Fee
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editFixedFeeForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="fixed_fee_id" id="editFixedFeeId">
                <input type="hidden" name="_method" value="PUT">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="editFixedFeeGroup" class="form-label">Tariff Group <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="editFixedFeeGroup" name="tariff_group_id" required>
                                <option value="">--Select Tariff Group--</option>
                                @foreach ($tariffGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="editFixedFeeName" class="form-label">Fee Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editFixedFeeName" name="name"
                                required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="editFixedFeeAmount" class="form-label">Amount <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="editFixedFeeAmount" name="amount"
                                min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editFixedEffectiveFrom" class="form-label">Effective From <span
                                    class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="editFixedEffectiveFrom"
                                name="effective_from" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editFixedEffectiveTo" class="form-label">Effective To <span
                                    class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="editFixedEffectiveTo"
                                name="effective_to">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editFixedFeeDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editFixedFeeDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editFixedFeeStatus" class="form-label">Status <span
                                class="text-danger">*</span></label>
                        <select class="form-select" id="editFixedFeeStatus" name="is_active" required>
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
                        <i class="bi bi-pencil me-2"></i>Update Fixed Fee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
