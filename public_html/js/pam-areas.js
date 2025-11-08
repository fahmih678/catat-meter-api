/**
 * PAM Areas Management
 * Handles all area-related CRUD operations
 */
class PamAreasManager {
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
        // Create Area Form
        const createAreaForm = document.getElementById('createAreaForm');
        if (createAreaForm) {
            createAreaForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleCreateArea();
            });
        }

        // Edit Area Form
        const editAreaForm = document.getElementById('editAreaForm');
        if (editAreaForm) {
            editAreaForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleEditArea();
            });
        }
    }

    /**
     * Edit area - fetch data and show modal
     */
    editArea(id) {
        // Try to get data from table first
        const areaRow = document.querySelector(`tr[data-area-id="${id}"]`);
        if (areaRow) {
            // Extract data from table row
            const cells = areaRow.getElementsByTagName('td');
            const areaData = {
                id: id,
                code: cells[0].textContent.trim(),
                name: cells[1].textContent.trim(),
                description: cells[2].textContent.trim(),
            };

            this.modalManager.showEditAreaModal(areaData);
        } else {
            // Fetch from server
            this.fetchAreaData(id);
        }
    }

    /**
     * Delete area
     */
    deleteArea(id, name) {
        if (confirm(`Are you sure you want to delete ${name}? This action cannot be undone.`)) {
            fetch(`/pam/${this.pamId}/areas/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': PamUtils.getCsrfToken(),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    PamUtils.showNotification(data.message || 'Area deleted successfully', 'success');

                    // Remove area row from table with animation
                    PamUtils.removeTableRow(`tr[data-area-id="${id}"]`);

                    // Update area count badge
                    PamUtils.updateBadgeCount('#areas-tab .badge', -1);

                    // Reload page after delay
                    PamUtils.reloadPage(1000);
                } else {
                    PamUtils.showNotification(data.message || 'Failed to delete area', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                PamUtils.showNotification('An error occurred while deleting the area', 'danger');
            });
        }
    }

    /**
     * Fetch area data from server
     */
    fetchAreaData(id) {
        fetch(`/pam/${this.pamId}/areas/${id}/edit`, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.modalManager.showEditAreaModal(data.data);
            } else {
                PamUtils.showNotification('Failed to fetch area data', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            PamUtils.showNotification('An error occurred while fetching area data', 'danger');
        });
    }

    /**
     * Handle create area form submission
     */
    handleCreateArea() {
        const form = document.getElementById('createAreaForm');

        PamUtils.submitForm(form, {
            loadingText: 'Creating...',
            onSuccess: (data) => {
                // Close modal and reset form
                this.modalManager.resetAndCloseModal('createAreaForm', 'createAreaModal');

                // Reload page to show new data
                PamUtils.reloadPage(1000);
            }
        });
    }

    /**
     * Handle edit area form submission
     */
    handleEditArea() {
        const form = document.getElementById('editAreaForm');
        const areaId = document.getElementById('editAreaId').value;

        // Update form action
        form.action = `/pam/${this.pamId}/areas/${areaId}`;

        PamUtils.submitForm(form, {
            method: 'POST', // Form has @method('PUT') in Laravel
            loadingText: 'Updating...',
            onSuccess: (data) => {
                // Close modal
                this.modalManager.closeModal('editAreaModal');

                // Reload page to show updated data
                PamUtils.reloadPage(1000);
            }
        });
    }

    /**
     * Show create area modal
     */
    showCreateAreaModal() {
        this.modalManager.showCreateAreaModal();
    }
}

// Global functions for onclick handlers
function editArea(id) {
    if (window.pamAreasManager) {
        window.pamAreasManager.editArea(id);
    }
}

function deleteArea(id, name) {
    if (window.pamAreasManager) {
        window.pamAreasManager.deleteArea(id, name);
    }
}

function showCreateAreaModal() {
    if (window.pamAreasManager) {
        window.pamAreasManager.showCreateAreaModal();
    }
}