/**
 * PAM Utility Functions
 * Common helper functions used across PAM management
 */
class PamUtils {
    /**
     * Show notification toast
     */
    static showNotification(message, type = 'info') {
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        const container = document.createElement('div');
        container.innerHTML = toastHtml;
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';

        document.body.appendChild(container);

        const toast = new bootstrap.Toast(container.querySelector('.toast'));
        toast.show();

        setTimeout(() => {
            container.remove();
        }, 3000);
    }

    /**
     * Get CSRF token from meta tag
     */
    static getCsrfToken() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            throw new Error('CSRF token not found');
        }
        return csrfToken.getAttribute('content');
    }

    /**
     * Set button loading state
     */
    static setButtonLoading(button, loadingText, isLoading = true) {
        if (isLoading) {
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = `<i class="bi bi-hourglass-split me-2"></i>${loadingText}`;
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || button.innerHTML;
        }
    }

    /**
     * Generate default code based on current date and random number
     */
    static generateCode(prefix = '') {
        const date = new Date();
        const randomNum = String(Math.floor(Math.random() * 1000)).padStart(3, '0');
        return prefix + date.getFullYear() + String(date.getMonth() + 1).padStart(2, '0') + randomNum;
    }

    /**
     * Remove table row with animation
     */
    static removeTableRow(selector) {
        const row = document.querySelector(selector);
        if (row) {
            row.style.transition = 'opacity 0.3s';
            row.style.opacity = '0';
            setTimeout(() => row.remove(), 300);
        }
    }

    /**
     * Update badge count
     */
    static updateBadgeCount(selector, change) {
        const badge = document.querySelector(selector);
        if (badge) {
            const currentCount = parseInt(badge.textContent);
            const newCount = Math.max(0, currentCount + change);
            badge.textContent = newCount;
        }
    }

    /**
     * Reload page after delay
     */
    static reloadPage(delay = 1000) {
        setTimeout(() => {
            window.location.reload();
        }, delay);
    }

    /**
     * Handle form submission with fetch API
     */
    static async submitForm(form, options = {}) {
        const {
            method = 'POST',
            onSuccess = null,
            onError = null,
            finallyCallback = null,
            loadingText = 'Processing...'
        } = options;

        const submitButton = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);

        try {
            // Set loading state
            this.setButtonLoading(submitButton, loadingText, true);

            const response = await fetch(form.action || form.dataset.action, {
                method: method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                if (onSuccess) onSuccess(data);
                this.showNotification(data.message || 'Operation completed successfully', 'success');
            } else {
                if (onError) onError(data);
                this.showNotification(data.message || 'Operation failed', 'danger');
            }

            return data;
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('An error occurred while processing the request', 'danger');
            if (onError) onError({ success: false, message: error.message });
            throw error;
        } finally {
            // Restore button state
            this.setButtonLoading(submitButton, '', false);
            if (finallyCallback) finallyCallback();
        }
    }
}

// Make available globally
window.PamUtils = PamUtils;