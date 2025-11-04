/**
 * PAM Tabs Management
 * Handles tab navigation and localStorage persistence
 */
class PamTabsManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupTabListeners();
        this.restoreActiveTab();
    }

    setupTabListeners() {
        const tabButtons = document.querySelectorAll('#pamTabs button[data-bs-toggle="tab"]');

        tabButtons.forEach(button => {
            button.addEventListener('shown.bs.tab', (e) => {
                const target = e.target.getAttribute('data-bs-target');
                localStorage.setItem('activePamTab', target);
            });
        });
    }

    restoreActiveTab() {
        const activeTab = localStorage.getItem('activePamTab');
        if (activeTab) {
            const tabButton = document.querySelector(`#pamTabs button[data-bs-target="${activeTab}"]`);
            if (tabButton) {
                const tab = new bootstrap.Tab(tabButton);
                tab.show();
            }
        }
    }

    switchToTab(tabId) {
        const tabButton = document.getElementById(tabId);
        if (tabButton) {
            const tab = new bootstrap.Tab(tabButton);
            tab.show();
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.pamTabsManager = new PamTabsManager();
});