/**
 * Customer Modals JavaScript
 * Handles customer modal interactions and meter management navigation
 */

/**
 * Show customer meters modal from edit customer modal
 */
function showCustomerMetersModal() {
    // Close edit customer modal first
    const editModal = bootstrap.Modal.getInstance(document.getElementById('editCustomerModal'));
    if (editModal) {
        editModal.hide();
    }

    // Show customer meters modal
    viewCustomerMeters(document.getElementById('edit_customer_id').value);
}

/**
 * Close customer meters modal and return to edit customer modal
 */
function closeCustomerMetersModal() {
    const metersModal = bootstrap.Modal.getInstance(document.getElementById('customerMetersModal'));
    if (metersModal) {
        metersModal.hide();
    }

    // Reopen edit customer modal if it was open
    const editCustomerId = document.getElementById('edit_customer_id').value;
    if (editCustomerId) {
        editCustomer(editCustomerId);
    }
}

// Add event listener for when customer meters modal is closed
document.addEventListener('DOMContentLoaded', function() {
    const customerMetersModal = document.getElementById('customerMetersModal');
    if (customerMetersModal) {
        customerMetersModal.addEventListener('hidden.bs.modal', function () {
            // Optional: cleanup if needed
        });
    }
});