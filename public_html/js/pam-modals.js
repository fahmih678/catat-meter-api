/**
 * PAM Modal Management
 * Handles all modal-related operations
 */
class PamModalManager {
    constructor(pamId) {
        this.pamId = pamId;
    }

    /**
     * Show Create Area Modal
     */
    showCreateAreaModal() {
        const modal = new bootstrap.Modal(document.getElementById('createAreaModal'));

        // Generate default code
        const code = PamUtils.generateCode('AR');
        document.getElementById('areaCode').value = code;

        // Reset form
        const form = document.getElementById('createAreaForm');
        form.reset();
        document.getElementById('areaCode').value = code; // Keep the generated code

        modal.show();
    }

    /**
     * Show Edit Area Modal
     */
    showEditAreaModal(areaData) {
        // Populate form fields
        document.getElementById('editAreaId').value = areaData.id;
        document.getElementById('editAreaName').value = areaData.name;
        document.getElementById('editAreaCode').value = areaData.code;
        document.getElementById('editAreaDescription').value = areaData.description || '';

        const modal = new bootstrap.Modal(document.getElementById('editAreaModal'));
        modal.show();
    }

    /**
     * Show Create Tariff Group Modal
     */
    showCreateTariffGroupModal() {
        const modal = new bootstrap.Modal(document.getElementById('createTariffGroupModal'));

        modal.show();
    }

    /**
     * Show Edit Tariff Group Modal
     */
    showEditTariffGroupModal(tariffGroupData) {
        // Populate form fields
        document.getElementById('editTariffGroupId').value = tariffGroupData.id;
        document.getElementById('editTariffGroupName').value = tariffGroupData.name;
        document.getElementById('editTariffGroupDescription').value = tariffGroupData.description || '';
        document.getElementById('editTariffGroupStatus').value = tariffGroupData.is_active ? '1' : '0';

        const modal = new bootstrap.Modal(document.getElementById('editTariffGroupModal'));
        modal.show();
    }

    /**
     * Show Create Tariff Tier Modal
     */
    showCreateTariffTierModal() {
        const modal = new bootstrap.Modal(document.getElementById('createTariffTierModal'));

        // Reset form
        const form = document.getElementById('createTariffTierForm');
        form.reset();
        document.getElementById('tariffTierStatus').value = '1'; // Set to Active by default

        modal.show();
    }

    /**
     * Show Edit Tariff Tier Modal
     */
    showEditTariffTierModal(tariffTierData) {
        // Populate form fields
        document.getElementById('editTariffTierId').value = tariffTierData.id;
        document.getElementById('editTariffTierGroup').value = tariffTierData.tariff_group_id;
        document.getElementById('editTariffMeterMin').value = tariffTierData.meter_min;
        document.getElementById('editTariffMeterMax').value = tariffTierData.meter_max;
        document.getElementById('editTariffAmount').value = tariffTierData.amount;
        document.getElementById('editTariffDescription').value = tariffTierData.description || '';
        document.getElementById('editTariffTierStatus').value = tariffTierData.is_active ? '1' : '0';

        // Format dates
        let effectiveFrom = tariffTierData.effective_from;
        let effectiveTo = tariffTierData.effective_to;
        if (effectiveFrom) {
            const date = new Date(effectiveFrom);
            const formattedDate = date.toISOString().split('T')[0];
            document.getElementById('editTariffEffectiveFrom').value = formattedDate;
        }
        if (effectiveTo) {
            const dateTo = new Date(effectiveTo);
            const formattedDateTo = dateTo.toISOString().split('T')[0];
            document.getElementById('editTariffEffectiveTo').value = formattedDateTo;
        }

        const modal = new bootstrap.Modal(document.getElementById('editTariffTierModal'));
        modal.show();
    }

    /**
     * Show Edit Fixed Fee Modal
     */
    showEditFixedFeeModal(fixedFeeData) {
        // Populate form fields
        document.getElementById('editFixedFeeId').value = fixedFeeData.id;
        document.getElementById('editFixedFeeGroup').value = fixedFeeData.tariff_group_id;
        document.getElementById('editFixedFeeName').value = fixedFeeData.name;
        document.getElementById('editFixedFeeAmount').value = fixedFeeData.amount;
        document.getElementById('editFixedFeeDescription').value = fixedFeeData.description || '';
        document.getElementById('editFixedFeeStatus').value = fixedFeeData.is_active ? '1' : '0';

        let effectiveFrom = fixedFeeData.effective_from;
        let effectiveTo = fixedFeeData.effective_to;
        if (effectiveFrom) {
            const date = new Date(effectiveFrom);
            const formattedDate = date.toISOString().split('T')[0]; // hasil: '2025-11-04'
            document.getElementById('editFixedEffectiveFrom').value = formattedDate;
        }
        if (effectiveTo) {  
            const dateTo = new Date(effectiveTo);
            const formattedDateTo = dateTo.toISOString().split('T')[0]; // hasil: '2025-11-04'
            document.getElementById('editFixedEffectiveTo').value = formattedDateTo;
        }

        const modal = new bootstrap.Modal(document.getElementById('editFixedFeeModal'));
        modal.show();
    }

    /**
     * Show Create Fixed Fee Modal
     */
    showCreateFixedFeeModal() {
        const modal = new bootstrap.Modal(document.getElementById('createFixedFeeModal'));

        // Reset form
        const form = document.getElementById('createFixedFeeForm');
        form.reset();
        document.getElementById('fixedFeeStatus').value = '1'; // Set to Active by default

        modal.show();
    }

    /**
     * Close modal
     */
    closeModal(modalId) {
        const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
        if (modal) {
            modal.hide();
        }
    }

    /**
     * Reset form and close modal
     */
    resetAndCloseModal(formId, modalId) {
        const form = document.getElementById(formId);
        if (form) {
            form.reset();
        }
        this.closeModal(modalId);
    }
}