/**
 * PAM Fixed Fees Management
 * Handles all fixed fee-related CRUD operations
 */
class PamFixedFeesManager {
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
        // Create Fixed Fee Form
        const createFixedFeeForm = document.getElementById('createFixedFeeForm');
        if (createFixedFeeForm) {
            createFixedFeeForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleCreateFixedFee();
            });
        }

        // Edit Fixed Fee Form
        const editFixedFeeForm = document.getElementById('editFixedFeeForm');
        if (editFixedFeeForm) {
            editFixedFeeForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleEditFixedFee();
            });
        }
    }

    /**
     * Edit fixed fee - fetch data and show modal
     */
    editFixedFee(id) {
        // Try to get data from table first
        const fixedFeeRow = document.querySelector(`tr[data-fixed-fee-id="${id}"]`);
        if (fixedFeeRow) {
            // Extract data from table row
            const cells = fixedFeeRow.getElementsByTagName('td');
            const fixedFeeData = {
                id: id,
                name: cells[0].textContent.trim(),
                amount: cells[2].textContent.replace(/[^0-9.]/g, ''),
                description: cells[4].textContent.trim(),
                is_active: cells[5].textContent.trim() === 'Active'
            };

            this.modalManager.showEditFixedFeeModal(fixedFeeData);
        } else {
            // Fetch from server
            this.fetchFixedFeeData(id);
        }
    }

    /**
     * Delete fixed fee
     */
    deleteFixedFee(id, name) {
        if (confirm(`Are you sure you want to delete ${name}? This action cannot be undone.`)) {
            fetch(`/pam/${this.pamId}/fixed-fees/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': PamUtils.getCsrfToken(),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    PamUtils.showNotification(data.message || 'Fixed fee deleted successfully', 'success');

                    // Remove fixed fee row from table with animation
                    PamUtils.removeTableRow(`tr[data-fixed-fee-id="${id}"]`);

                    // Update fixed fee count badge
                    PamUtils.updateBadgeCount('#fees-tab .badge', -1);

                    // Reload page after delay
                    PamUtils.reloadPage(1000);
                } else {
                    PamUtils.showNotification(data.message || 'Failed to delete fixed fee', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                PamUtils.showNotification('An error occurred while deleting the fixed fee', 'danger');
            });
        }
    }

    /**
     * Fetch fixed fee data from server
     */
    fetchFixedFeeData(id) {
        fetch(`/pam/${this.pamId}/fixed-fees/${id}/edit`, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.modalManager.showEditFixedFeeModal(data.data);
            } else {
                PamUtils.showNotification('Failed to fetch fixed fee data', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            PamUtils.showNotification('An error occurred while fetching fixed fee data', 'danger');
        });
    }

    /**
     * Handle create fixed fee form submission
     */
    handleCreateFixedFee() {
        const form = document.getElementById('createFixedFeeForm');

        PamUtils.submitForm(form, {
            loadingText: 'Creating...',
            onSuccess: (data) => {
                // Close modal and reset form
                this.modalManager.resetAndCloseModal('createFixedFeeForm', 'createFixedFeeModal');

                // Reload page to show new data
                PamUtils.reloadPage(1000);
            }
        });
    }

    /**
     * Handle edit fixed fee form submission
     */
    handleEditFixedFee() {
        const form = document.getElementById('editFixedFeeForm');
        const fixedFeeId = document.getElementById('editFixedFeeId').value;

        // Update form action
        form.action = `/pam/${this.pamId}/fixed-fees/${fixedFeeId}`;

        PamUtils.submitForm(form, {
            method: 'POST', // Form has @method('PUT') in Laravel
            loadingText: 'Updating...',
            onSuccess: (data) => {
                // Close modal
                this.modalManager.closeModal('editFixedFeeModal');

                // Reload page to show updated data
                PamUtils.reloadPage(1000);
            }
        });
    }

    /**
     * Show create fixed fee modal
     */
    showCreateFixedFeeModal() {
        this.modalManager.showCreateFixedFeeModal();
    }

    /**
     * Toggle fixed fee status
     */
    toggleStatus(id) {
        fetch(`/pam/${this.pamId}/fixed-fees/${id}/toggle`, {
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
function editFixedFee(id) {
    if (window.pamFixedFeesManager) {
        window.pamFixedFeesManager.editFixedFee(id);
    }
}

function deleteFixedFee(id, name) {
    if (window.pamFixedFeesManager) {
        window.pamFixedFeesManager.deleteFixedFee(id, name);
    }
}

function showCreateFixedFeeModal() {
    if (window.pamFixedFeesManager) {
        window.pamFixedFeesManager.showCreateFixedFeeModal();
    }
}

function toggleFixedFeeStatus(id) {
    if (window.pamFixedFeesManager) {
        window.pamFixedFeesManager.toggleStatus(id);
    }
}