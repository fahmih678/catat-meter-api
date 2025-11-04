<!-- Create Area Modal -->
<div class="modal fade" id="createAreaModal" tabindex="-1" aria-labelledby="createAreaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="createAreaModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Add New Area
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createAreaForm" method="POST" action="{{ route('pam.areas.store', $pam->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="areaName" class="form-label">Area Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="areaName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="areaCode" class="form-label">Area Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="areaCode" name="code" required>
                    </div>
                    <div class="mb-3">
                        <label for="areaDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="areaDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="areaStatus" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="areaStatus" name="is_active" required>
                            <option value="">Select Status</option>
                            <option value="1" selected>Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <small>Areas help organize customers by geographical regions within the PAM service
                            area.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Create Area
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Area Modal -->
<div class="modal fade" id="editAreaModal" tabindex="-1" aria-labelledby="editAreaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="editAreaModalLabel">
                    <i class="bi bi-pencil me-2"></i>Edit Area
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editAreaForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="area_id" id="editAreaId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editAreaName" class="form-label">Area Name <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editAreaName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editAreaCode" class="form-label">Area Code <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editAreaCode" name="code" required>
                    </div>
                    <div class="mb-3">
                        <label for="editAreaDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editAreaDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editAreaStatus" class="form-label">Status <span
                                class="text-danger">*</span></label>
                        <select class="form-select" id="editAreaStatus" name="is_active" required>
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
                        <i class="bi bi-pencil me-2"></i>Update Area
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
