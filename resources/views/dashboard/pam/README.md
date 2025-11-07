# Customer Management Component Structure

## Overview
This directory contains the refactored customer management components for better code organization and maintainability.

## File Structure

### Main Template
- `customers.blade.php` - Main template file that includes all components

### Partial Components
- `partials/customer-header.blade.php` - Header section with PAM info and action buttons
- `partials/customer-filters.blade.php` - Search and filter form
- `partials/customer-table.blade.php` - Customer table with pagination
- `partials/customer-modals.blade.php` - All modal dialogs (Add, Edit, View)

### Styles & Scripts
- `partials/customer-styles.blade.php` - All CSS styles specific to customer management
- `../../js/customer-management.js` - JavaScript functionality
- `public/js/customer-management.js` - Published JavaScript file

## Component Dependencies

### Required Data Variables
- `$pam` - PAM model instance
- `$customers` - Paginated customer collection
- `$areas` - Available areas for the PAM
- `$search` - Current search query
- `$areaId` - Current area filter
- `$status` - Current status filter
- `$perPage` - Current pagination setting

### JavaScript Functions
- `initializePamId(pamId)` - Initialize the current PAM ID
- `showAddCustomerModal()` - Show add customer modal
- `editCustomer(id)` - Show edit customer modal
- `viewCustomer(id)` - Show customer details modal
- `deleteCustomer(id, name)` - Delete customer confirmation
- `generateCustomerNumber()` - Generate unique customer number
- `generateMeterNumber()` - Generate unique meter number
- `loadFormData(type, callback)` - Load form data (areas, tariff groups, users)
- `populateFormData(formPrefix, data)` - Populate form dropdowns
- `toggleMeterFields()` - Toggle meter management fields
- `clearEditMeterFields()` - Clear meter form fields

## Usage

### Adding a New Component
1. Create partial file in `partials/` directory
2. Include it in main template using `@include`
3. Add any required CSS to `customer-styles.blade.php`
4. Add any required JavaScript functions to `customer-management.js`

### Modifying Existing Components
1. Edit the relevant partial file
2. Update styles if needed in `customer-styles.blade.php`
3. Update JavaScript functions in `customer-management.js`

## Best Practices

1. **Keep components small and focused** - Each partial should handle one specific UI section
2. **Use meaningful naming** - File names should clearly indicate their purpose
3. **Maintain consistency** - Use consistent naming conventions and structure
4. **Document changes** - Update this README when making significant changes
5. **Test thoroughly** - Test all functionality after refactoring

## Features

### Customer CRUD
- Create new customers with optional meter information
- Edit customer details and manage meters
- View detailed customer information
- Delete customers with safety checks

### Number Generation
- Automatic unique customer number generation
- Automatic unique meter number generation
- Format: `PAMCODE-YYYYMMDD-XXXX` for customers
- Format: `MTR-PAMCODE-YYYYMMDD-XXX` for meters

### Meter Management
- Add new meters to customers
- Update existing meter information
- Deactivate meters (soft delete)
- View meter status and history

### Data Validation
- Form validation with proper error messages
- Unique number validation
- Required field validation
- Real-time error feedback

## Integration

The customer management system integrates with:
- PAM Management (parent relationship)
- Area Management (filtering options)
- Tariff Group Management (customer assignment)
- User Management (user assignment)
- Meter Reading System (future integration)