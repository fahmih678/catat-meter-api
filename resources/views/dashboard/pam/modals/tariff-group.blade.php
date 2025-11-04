<!-- Create Tariff Group Modal -->
<div class="modal fade" id="createTariffGroupModal" tabindex="-1" aria-labelledby="createTariffGroupModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="createTariffGroupModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Add New Tariff Group
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createTariffGroupForm" method="POST" action="{{ route('pam.tariff-groups.store', $pam->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="tariffGroupName" class="form-label">Group Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tariffGroupName" name="name" required>
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

<!-- Edit Tariff Group Modal -->
<div class="modal fade" id="editTariffGroupModal" tabindex="-1" aria-labelledby="editTariffGroupModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="editTariffGroupModalLabel">
                    <i class="bi bi-pencil me-2"></i>Edit Tariff Group
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTariffGroupForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="tariff_group_id" id="editTariffGroupId">
                <input type="hidden" name="_method" value="PUT">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="editTariffGroupName" class="form-label">Group Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editTariffGroupName" name="name" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editTariffGroupDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editTariffGroupDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editTariffGroupStatus" class="form-label">Status <span
                                class="text-danger">*</span></label>
                        <select class="form-select" id="editTariffGroupStatus" name="is_active" required>
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
                        <i class="bi bi-pencil me-2"></i>Update Tariff Group
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
