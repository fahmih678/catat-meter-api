/**
 * PAM Tariff Groups Management
 * Handles all tariff group-related CRUD operations
 */
class PamTariffGroupsManager {
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
        // Create Tariff Group Form
        const createTariffGroupForm = document.getElementById('createTariffGroupForm');
        if (createTariffGroupForm) {
            createTariffGroupForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleCreateTariffGroup();
            });
        }

        // Edit Tariff Group Form
        const editTariffGroupForm = document.getElementById('editTariffGroupForm');
        if (editTariffGroupForm) {
            editTariffGroupForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleEditTariffGroup();
            });
        }
    }

    /**
     * Edit tariff group - fetch data and show modal
     */
    editTariffGroup(id) {
        // Try to get data from table first
        const tariffGroupRow = document.querySelector(`tr[data-tariff-group-id="${id}"]`);
        if (tariffGroupRow) {
            // Extract data from table row
            const cells = tariffGroupRow.getElementsByTagName('td');
            const tariffGroupData = {
                id: id,
                name: cells[0].textContent.trim(),
                description: cells[1].textContent.trim(),
                is_active: cells[4].textContent.trim() === 'Active'
            };

            this.modalManager.showEditTariffGroupModal(tariffGroupData);
        } else {
            // Fetch from server
            this.fetchTariffGroupData(id);
        }
    }

    /**
     * Delete tariff group
     */
    deleteTariffGroup(id, name) {
        if (confirm(`Are you sure you want to delete ${name}? This action cannot be undone.`)) {
            fetch(`/pam/${this.pamId}/tariff-groups/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': PamUtils.getCsrfToken(),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    PamUtils.showNotification(data.message || 'Tariff group deleted successfully', 'success');

                    // Remove tariff group row from table with animation
                    PamUtils.removeTableRow(`tr[data-tariff-group-id="${id}"]`);

                    // Update tariff group count badge
                    PamUtils.updateBadgeCount('#tariffs-tab .badge', -1);

                    // Reload page after delay
                    PamUtils.reloadPage(1000);
                } else {
                    PamUtils.showNotification(data.message || 'Failed to delete tariff group', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                PamUtils.showNotification('An error occurred while deleting the tariff group', 'danger');
            });
        }
    }

    /**
     * Fetch tariff group data from server
     */
    fetchTariffGroupData(id) {
        fetch(`/pam/${this.pamId}/tariff-groups/${id}/edit`, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.modalManager.showEditTariffGroupModal(data.data);
            } else {
                PamUtils.showNotification('Failed to fetch tariff group data', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            PamUtils.showNotification('An error occurred while fetching tariff group data', 'danger');
        });
    }

    /**
     * Handle create tariff group form submission
     */
    handleCreateTariffGroup() {
        const form = document.getElementById('createTariffGroupForm');

        PamUtils.submitForm(form, {
            loadingText: 'Creating...',
            onSuccess: (data) => {
                // Close modal and reset form
                this.modalManager.resetAndCloseModal('createTariffGroupForm', 'createTariffGroupModal');

                // Reload page to show new data
                PamUtils.reloadPage(1000);
            }
        });
    }

    /**
     * Handle edit tariff group form submission
     */
    handleEditTariffGroup() {
        const form = document.getElementById('editTariffGroupForm');
        const tariffGroupId = document.getElementById('editTariffGroupId').value;

        // Update form action
        form.action = `/pam/${this.pamId}/tariff-groups/${tariffGroupId}`;

        PamUtils.submitForm(form, {
            method: 'POST', // Form has @method('PUT') in Laravel
            loadingText: 'Updating...',
            onSuccess: (data) => {
                // Close modal
                this.modalManager.closeModal('editTariffGroupModal');

                // Reload page to show updated data
                PamUtils.reloadPage(1000);
            }
        });
    }

    /**
     * Show create tariff group modal
     */
    showCreateTariffGroupModal() {
        this.modalManager.showCreateTariffGroupModal();
    }

    /**
     * Manage tiers for a tariff group
     */
    manageTiers(id) {
        // Navigate to tariff tiers tab with this group filtered
        window.pamTabsManager.switchToTab('tiers-tab');
        PamUtils.showNotification('Loading tariff tiers for this group...', 'info');
    }
}

// Global functions for onclick handlers
function editTariffGroup(id) {
    if (window.pamTariffGroupsManager) {
        window.pamTariffGroupsManager.editTariffGroup(id);
    }
}

function deleteTariffGroup(id, name) {
    if (window.pamTariffGroupsManager) {
        window.pamTariffGroupsManager.deleteTariffGroup(id, name);
    }
}

function showCreateTariffGroupModal() {
    if (window.pamTariffGroupsManager) {
        window.pamTariffGroupsManager.showCreateTariffGroupModal();
    }
}

function manageTiers(id) {
    if (window.pamTariffGroupsManager) {
        window.pamTariffGroupsManager.manageTiers(id);
    }
}