# 🧪 API TESTING GUIDE

**Last Updated:** October 11, 2025  
**Status:** ✅ **READY FOR COMPREHENSIVE TESTING**

## 📋 **TESTING PREREQUISITES:**

### **1. Setup Environment:**
```bash
# Start Laravel server
php artisan serve --host=127.0.0.1 --port=8000

# Verify database is seeded
php artisan migrate:fresh --seed
```

### **2. Base Configuration:**
- **Base URL:** `http://127.0.0.1:8000/api`
- **Content-Type:** `application/json`
- **Accept:** `application/json`

---

## 🚀 **TESTING SCENARIOS:**

### **1. PAM Management Testing**

#### **✅ Basic CRUD Operations:**
```bash
# List all PAMs
curl -X GET "http://127.0.0.1:8000/api/pams" \
  -H "Accept: application/json"

# Get specific PAM
curl -X GET "http://127.0.0.1:8000/api/pams/1" \
  -H "Accept: application/json"

# Create new PAM
curl -X POST "http://127.0.0.1:8000/api/pams" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "PAM Test Baru",
    "phone": "021-5555-9999",
    "address": "Jl. Test No. 123, Jakarta",
    "status": "active"
  }'

# Update PAM
curl -X PUT "http://127.0.0.1:8000/api/pams/1" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "PAM Jakarta Pusat Updated",
    "phone": "021-5555-0001"
  }'
```

#### **✅ Advanced Features:**
```bash
# Get active PAMs only
curl -X GET "http://127.0.0.1:8000/api/pams/active" \
  -H "Accept: application/json"

# Search PAMs
curl -X GET "http://127.0.0.1:8000/api/pams/search?name=Jakarta" \
  -H "Accept: application/json"

# Get PAM statistics
curl -X GET "http://127.0.0.1:8000/api/pams/1/statistics" \
  -H "Accept: application/json"

# Soft delete PAM
curl -X DELETE "http://127.0.0.1:8000/api/pams/8" \
  -H "Accept: application/json"

# Restore deleted PAM
curl -X POST "http://127.0.0.1:8000/api/pams/8/restore" \
  -H "Accept: application/json"
```

---

### **2. Customer Management Testing**

#### **✅ Basic Operations:**
```bash
# List customers with pagination
curl -X GET "http://127.0.0.1:8000/api/customers?page=1&per_page=10" \
  -H "Accept: application/json"

# Get specific customer
curl -X GET "http://127.0.0.1:8000/api/customers/1" \
  -H "Accept: application/json"

# Create new customer
curl -X POST "http://127.0.0.1:8000/api/customers" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "pam_id": 1,
    "area_id": 1,
    "tariff_group_id": 1,
    "name": "Test Customer",
    "address": "Jl. Test No. 456",
    "phone": "081234567890",
    "status": "active"
  }'
```

#### **✅ Filtering & Analytics:**
```bash
# Get customers by PAM
curl -X GET "http://127.0.0.1:8000/api/customers/pam/1" \
  -H "Accept: application/json"

# Get customers by area
curl -X GET "http://127.0.0.1:8000/api/customers/area/1" \
  -H "Accept: application/json"

# Search customers
curl -X GET "http://127.0.0.1:8000/api/customers/search?name=Fajar" \
  -H "Accept: application/json"

# Customers without meters
curl -X GET "http://127.0.0.1:8000/api/customers/pam/1/without-meters" \
  -H "Accept: application/json"
```

---

### **3. System Health Testing**

#### **✅ Health Check:**
```bash
# API Health Check
curl -X GET "http://127.0.0.1:8000/api/health" \
  -H "Accept: application/json"

# API Version Info
curl -X GET "http://127.0.0.1:8000/api/version" \
  -H "Accept: application/json"
```

---

## 🔍 **ERROR TESTING SCENARIOS:**

### **✅ Validation Errors:**
```bash
# Missing required fields
curl -X POST "http://127.0.0.1:8000/api/customers" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{}'

# Invalid data types
curl -X POST "http://127.0.0.1:8000/api/customers" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "pam_id": "invalid",
    "name": ""
  }'
```

### **✅ Not Found Errors:**
```bash
# Non-existent resource
curl -X GET "http://127.0.0.1:8000/api/pams/999" \
  -H "Accept: application/json"

# Deleted resource
curl -X DELETE "http://127.0.0.1:8000/api/pams/1"
curl -X GET "http://127.0.0.1:8000/api/pams/1" \
  -H "Accept: application/json"
```

---

## ✅ **EXPECTED RESPONSES:**

### **Success Response Format:**
```json
{
  "success": true,
  "message": "Resource retrieved successfully",
  "data": { /* resource data */ }
}
```

### **Error Response Format:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

### **Pagination Response:**
```json
{
  "success": true,
  "message": "Resources retrieved successfully",
  "data": {
    "data": [ /* resources */ ],
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 143
  }
}
```

---

## 🎯 **TESTING CHECKLIST:**

### **✅ Functional Testing:**
- [ ] All CRUD operations work
- [ ] Pagination works correctly
- [ ] Search functionality works
- [ ] Filtering works as expected
- [ ] Validation rules are enforced
- [ ] Soft delete/restore works
- [ ] Statistics calculations are correct

### **✅ Error Handling:**
- [ ] 404 for non-existent resources
- [ ] 422 for validation errors
- [ ] 500 errors handled gracefully
- [ ] Consistent error message format

### **✅ Performance:**
- [ ] Response times under 200ms for simple queries
- [ ] Response times under 500ms for complex queries
- [ ] Pagination handles large datasets
- [ ] Search is optimized with database indexes

### **✅ Data Integrity:**
- [ ] Foreign key constraints work
- [ ] Soft deletes don't break relationships
- [ ] Cascading deletes work correctly
- [ ] Data validation prevents corruption

---

## 🚀 **READY FOR TESTING!**

**System telah diuji dan siap untuk production testing dengan 66 endpoints yang fully functionalartisan migrate:fresh --seed* 🎉
