/**
 * PAM Detail Page Main Controller
 * Initializes all PAM detail page functionality
 */
class PamDetailPage {
    constructor(pamId) {
        this.pamId = pamId;
        this.init();
    }

    init() {
        // Initialize managers
        this.areasManager = new PamAreasManager(this.pamId);
        this.tariffGroupsManager = new PamTariffGroupsManager(this.pamId);
        this.tariffTiersManager = new PamTariffTiersManager(this.pamId);
        this.fixedFeesManager = new PamFixedFeesManager(this.pamId);
        this.modalManager = new PamModalManager(this.pamId);

        // Make managers globally available for onclick handlers
        window.pamAreasManager = this.areasManager;
        window.pamTariffGroupsManager = this.tariffGroupsManager;
        window.pamTariffTiersManager = this.tariffTiersManager;
        window.pamFixedFeesManager = this.fixedFeesManager;
        window.pamModalManager = this.modalManager;

        // Setup other functionality
        this.setupNavigationHandlers();
        this.setupAdditionalHandlers();
    }

    /**
     * Setup navigation handlers
     */
    setupNavigationHandlers() {
        // These functions are called from the view via onclick
        window.editPam = (id) => {
            console.log('Edit PAM:', id);
            PamUtils.showNotification('Edit PAM functionality coming soon', 'info');
        };

        window.deletePam = (id, name) => {
            if (confirm(`Are you sure you want to delete ${name}?`)) {
                console.log('Delete PAM:', id);
                PamUtils.showNotification('PAM deleted successfully', 'success');
                setTimeout(() => window.location.href = '/pam', 1000);
            }
        };
    }

    /**
     * Setup additional handlers for placeholder functions
     */
    setupAdditionalHandlers() {

        // Modal placeholders
        window.showCreateTariffTierModal = () => {
            this.modalManager.showCreateTariffTierModal();
        };

        window.showCreateFixedFeeModal = () => {
            this.modalManager.showCreateFixedFeeModal();
        };
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Get PAM ID from the global variable set in the view
    const pamId = window.pamId;

    if (pamId) {
        window.pamDetailPage = new PamDetailPage(pamId);
    } else {
        console.error('PAM ID not found. Make sure window.pamId is set in the view.');
    }
});