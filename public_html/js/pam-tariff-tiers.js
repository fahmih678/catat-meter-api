/**
 * PAM Tariff Tiers Management
 * Handles all tariff tier-related CRUD operations
 */
class PamTariffTiersManager {
    constructor(pamId) {
        this.pamId = pamId;
        this.modalManager = null;
        this.init();
    }

    init() {
        this.modalManager = new PamModalManager(this.pamId);
        this.setupFormListeners();
    }

    /**
     * Setup form event listeners
     */
    setupFormListeners() {
        // Create Tariff Tier Form
        const createTariffTierForm = document.getElementById('createTariffTierForm');
        if (createTariffTierForm) {
            createTariffTierForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleCreateTariffTier();
            });
        }

        // Edit Tariff Tier Form
        const editTariffTierForm = document.getElementById('editTariffTierForm');
        if (editTariffTierForm) {
            editTariffTierForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleEditTariffTier();
            });
        }
    }

    /**
     * Edit tariff tier - fetch data and show modal
     */
    editTariffTier(id) {
        // Try to get data from table first
        const tariffTierRow = document.querySelector(`tr[data-tariff-tier-id="${id}"]`);
        if (tariffTierRow) {
            // Extract data from table row
            const cells = tariffTierRow.getElementsByTagName('td');
            const tariffTierData = {
                id: id,
                tariff_group_name: cells[0].textContent.trim(),
                description: cells[1].textContent.trim(),
                meter_range: cells[2].textContent.trim(),
                amount: cells[3].textContent.replace(/[^0-9.]/g, ''),
                is_active: cells[4].textContent.trim() === 'Active'
            };

            this.modalManager.showEditTariffTierModal(tariffTierData);
        } else {
            // Fetch from server
            this.fetchTariffTierData(id);
        }
    }

    /**
     * Delete tariff tier
     */
    deleteTariffTier(id, name) {
        if (confirm(`Are you sure you want to delete ${name}? This action cannot be undone.`)) {
            fetch(`/pam/${this.pamId}/tiers/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': PamUtils.getCsrfToken(),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    PamUtils.showNotification(data.message || 'Tariff tier deleted successfully', 'success');

                    // Remove tariff tier row from table with animation
                    PamUtils.removeTableRow(`tr[data-tariff-tier-id="${id}"]`);

                    // Update tariff tier count badge
                    PamUtils.updateBadgeCount('#tiers-tab .badge', -1);

                    // Reload page after delay
                    PamUtils.reloadPage(1000);
                } else {
                    PamUtils.showNotification(data.message || 'Failed to delete tariff tier', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                PamUtils.showNotification('An error occurred while deleting the tariff tier', 'danger');
            });
        }
    }

    /**
     * Fetch tariff tier data from server
     */
    fetchTariffTierData(id) {
        fetch(`/pam/${this.pamId}/tiers/${id}/edit`, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.modalManager.showEditTariffTierModal(data.data);
            } else {
                PamUtils.showNotification('Failed to fetch tariff tier data', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            PamUtils.showNotification('An error occurred while fetching tariff tier data', 'danger');
        });
    }

    /**
     * Handle create tariff tier form submission
     */
    handleCreateTariffTier() {
        const form = document.getElementById('createTariffTierForm');

        PamUtils.submitForm(form, {
            loadingText: 'Creating...',
            onSuccess: (data) => {
                // Close modal and reset form
                this.modalManager.resetAndCloseModal('createTariffTierForm', 'createTariffTierModal');

                // Reload page to show new data
                PamUtils.reloadPage(1000);
            }
        });
    }

    /**
     * Handle edit tariff tier form submission
     */
    handleEditTariffTier() {
        const form = document.getElementById('editTariffTierForm');
        const tariffTierId = document.getElementById('editTariffTierId').value;

        // Update form action
        form.action = `/pam/${this.pamId}/tiers/${tariffTierId}`;

        PamUtils.submitForm(form, {
            method: 'POST', // Form has @method('PUT') in Laravel
            loadingText: 'Updating...',
            onSuccess: (data) => {
                // Close modal
                this.modalManager.closeModal('editTariffTierModal');

                // Reload page to show updated data
                PamUtils.reloadPage(1000);
            }
        });
    }

    /**
     * Show create tariff tier modal
     */
    showCreateTariffTierModal() {
        this.modalManager.showCreateTariffTierModal();
    }

    /**
     * Toggle tariff tier status
     */
    toggleStatus(id) {
        fetch(`/pam/${this.pamId}/tiers/${id}/toggle`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': PamUtils.getCsrfToken(),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                PamUtils.showNotification(data.message || 'Status updated successfully', 'success');
                PamUtils.reloadPage(1000);
            } else {
                PamUtils.showNotification(data.message || 'Failed to update status', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            PamUtils.showNotification('An error occurred while updating status', 'danger');
        });
    }
}

// Global functions for onclick handlers
function editTariffTier(id) {
    if (window.pamTariffTiersManager) {
        window.pamTariffTiersManager.editTariffTier(id);
    }
}

function deleteTariffTier(id, name) {
    if (window.pamTariffTiersManager) {
        window.pamTariffTiersManager.deleteTariffTier(id, name);
    }
}

function showCreateTariffTierModal() {
    if (window.pamTariffTiersManager) {
        window.pamTariffTiersManager.showCreateTariffTierModal();
    }
}

function toggleTariffTierStatus(id) {
    if (window.pamTariffTiersManager) {
        window.pamTariffTiersManager.toggleStatus(id);
    }
}