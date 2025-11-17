# Catat Meter API V1 Documentation

## Overview

This is the official API documentation for Catat Meter Application V1. The API provides endpoints for water utility management including meter reading, billing, payments, and reporting.

**Base URL:** `http://localhost:8000/api/v1`

**Authentication:** Bearer Token (Laravel Sanctum)
**Content-Type:** `application/json` (except for file uploads)

**Available Endpoints:** 25 endpoints across 8 modules

## Table of Contents

1. [Authentication](#authentication)
2. [Meter Reading Management](#meter-reading-management)
3. [Payment Management](#payment-management)
4. [Reports](#reports)
5. [User Management](#user-management)
6. [Customer Management](#customer-management)
7. [PAM Management](#pam-management)
8. [Registered Month Management](#registered-month-management)
9. [Error Responses](#error-responses)
10. [Response Formats](#response-formats)

---

## Authentication

The authentication module provides user authentication and profile management functionality.

**Available Endpoints:** 4 endpoints

### Login
**POST** `/auth/login`

Authenticate user and receive access token.

**Request Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
  "email": "string|required|email|max:255",
  "password": "string|required|min:1",
  "device_name": "string|nullable|max:100"
}
```

**Example Request:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "device_name": "mobile-app"
  }'
```

**Response (200):**
```json
{
  "status": "success",
    "message": "Login successful",
    "data": {
        "user": {
            "id": 2,
            "name": "Fahmi Habibi",
            "email": "fahmih678@gmail.com",
            "roles": [
                "admin",
                "loket",
                "customer"
            ],
            "status": "active",
            "photo": "http://127.0.0.1:8000/storage/users/users_2_1763089265_Uf2kPUYfUL.png"
        },
        "pam": {
            "id": 1,
            "name": "Sumber Waras",
            "logo": null
        },
        "token": "48|0LKU6r3femMkngohGlCHzCDRchNQ6SI4UTcfYsgA4df4867e",
        "token_type": "Bearer"
    }
}
```

**Response (422):**
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "email": [
            "The email field must be a valid email address."
        ],
        "device_name": [
            "The device name field must be a string."
        ]
    }
}
```

**Error Responses:**
- `401`: Invalid credentials
- `404`: User not found
- `403`: Account inactive
- `422`: Validation errors

---

### Get Profile
**GET** `/auth/profile`

Get current user profile information.

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Response (200):**
```json
{
    "status": "success",
    "message": "Profile retrieved successfully",
    "data": {
        "user": {
            "id": 2,
            "name": "Fahmi Habibi",
            "email": "fahmih678@gmail.com",
            "phone": "081234567810",
            "roles": [
                "admin",
                "loket",
                "customer"
            ],
            "status": "active",
            "photo": "http://127.0.0.1:8000/storage/users/users_2_1763089265_Uf2kPUYfUL.png"
        },
        "pam": {
            "id": 1,
            "name": "Sumber Waras"
        }
    }
}
```

---

### Update Profile
**PUT** `/auth/profile`

Update current user profile information.

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body (multipart/form-data):**
```
name: string|sometimes|max:50
email: string|sometimes|email|max:150|unique:users,{id}
phone: string|sometimes|max:20
password: string|sometimes|min:6
photo: file|sometimes|image|mimes:jpg,jpeg,png|max:5120
```

**Example Request:**
```bash
curl -X PUT http://localhost:8000/api/v1/auth/profile \
  -H "Authorization: Bearer {token}" \
  -F "name=John Doe Updated" \
  -F "phone=08123456789" \
  -F "photo=@profile.jpg"
```

**Response (200):**
```json
{
  "status": "success",
  "message": "Profile updated successfully",
  "data": {
    "user": {
      "name": "John Doe Updated",
      "updated_at": "2024-01-15 10:30:00"
    }
  }
}
```

---

### Logout
**POST** `/auth/logout`

Logout from current device.

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Response (200):**
```json
{
  "status": "success",
  "message": "Logout successful"
}
```

---

---

## Meter Reading Management

The meter reading module provides comprehensive functionality for managing water meter readings, customer data, and reading workflows.

**Available Endpoints:** 6 endpoints

### Get Meter Reading List
**GET** `/meter-readings/list`

Get list of meter readings with filtering and pagination.

**Access Roles:** `admin`, `catat_meter`, `loket` + PAM Scope

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Query Parameters:**
```
registered_month_id: integer|nullable|exists:registered_months,id
search: string|nullable|max:255
status: enum|nullable|in:draft,pending,paid
area_id: integer|nullable|exists:areas,id
per_page: integer|nullable|min:10|max:100|default:20
page: integer|nullable|min:1|default:1
sort_by: enum|nullable|in:customer_id,status|default:customer_id
sort_order: enum|nullable|in:asc,desc|default:desc
```

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/v1/meter-readings/list?registered_month_id=1&status=pending&per_page=10" \
  -H "Authorization: Bearer {token}"
```

**Response (200):**
```json
{
  "status": "success",
  "message": "Data pencatatan meter berhasil diambil",
  "data": {
    "items": [
      {
        "id": 1,
        "period": "2024-01",
        "customer": {
          "id": 1,
          "name": "Budi Santoso",
          "number": "CUS001",
          "area_name": "Area A"
        },
        "meter_number": "MTR001",
        "current_reading": 150.5,
        "volume_usage": 25.5,
        "bill": {
          "id": 1,
          "total_bill": 150000,
          "due_date": "2024-02-15"
        },
        "notes": "Pembacaan normal",
        "status": "pending",
        "reading_by": "Petugas A",
        "reading_at": "2024-01-15 10:30:00"
      }
    ],
    "pagination": {
      "total": 150,
      "has_more_pages": true
    }
  }
}
```

---

### Get Unrecorded Customers
**GET** `/customers/unrecorded`

Get list of customers that haven't been recorded for meter reading in the specified month.

**Access Roles:** `admin`, `catat_meter`, `loket` + PAM Scope

**Query Parameters:**
```
registered_month_id: integer|required|exists:registered_months,id
area_id: integer|nullable|exists:areas,id
search: string|nullable|max:255
per_page: integer|nullable|min:10|max:100|default:25
page: integer|nullable|min:1|default:1
sort_by: enum|nullable|in:customer_id,customer_number,area_name,meter_number,created_at|default:customer_id
sort_order: enum|nullable|in:asc,desc|default:asc
```

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/v1/customers/unrecorded?registered_month_id=1&area_id=1&search=budi" \
  -H "Authorization: Bearer {token}"
```

**Response (200):**
```json
{
  "status": "success",
  "message": "Data retrieved successfully",
  "data": {
    "items": [
      {
        "customer_id": 1,
        "name": "Budi Santoso",
        "number": "CUS001",
        "area_name": "Area A",
        "meter_number": "MTR001"
      }
    ],
    "registered_month_id": 1,
    "month": "2024-01",
    "pagination": {
      "total": 20,
      "has_more_pages": true
    },
    "summary": {
      "unrecorded": 50
    }
  }
}
```

---

### Get Meter Reading Form
**GET** `/customers/{id}/meter-reading-form`

Get customer and meter data for meter reading input.

**Access Roles:** `admin`, `catat_meter`, `loket` + PAM Scope

**Path Parameters:**
```
id: integer|required - Customer ID
```

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/v1/customers/1/meter-reading-form" \
  -H "Authorization: Bearer {token}"
```

**Response (200):**
```json
{
  "status": "success",
  "message": "Data berhasil diambil",
  "data": {
    "customer_id": 1,
    "name": "Budi Santoso",
    "number": "CUS001",
    "area_name": "Area A",
    "pam_name": "PDAM Kota",
    "meter": {
      "id": 1,
      "number": "MTR001",
      "last_reading": 125.0
    },
  }
}
```

---

### Store Meter Reading
**POST** `/meter-readings/store`

Store new meter reading data with optional photo.

**Access Roles:** `admin`, `catat_meter`, `loket` + PAM Scope

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body (multipart/form-data):**
```
customer_id: integer|required|exists:customers,id
registered_month_id: integer|required|exists:registered_months,id
current_reading: decimal|required|min:0
notes: string|nullable|max:1000
reading_by: integer|nullable|exists:users,id
photo: file|nullable|image|mimes:jpg,jpeg,png|max:2048
```

**Example Request:**
```bash
curl -X POST "http://localhost:8000/api/v1/meter-readings/store" \
  -H "Authorization: Bearer {token}" \
  -F "customer_id=1" \
  -F "registered_month_id=1" \
  -F "current_reading=150.5" \
  -F "notes=Pembacaan normal" \
  -F "photo=@meter_photo.jpg"
```

**Response (201):**
```json
{
  "status": "success",
  "message": "Meter reading berhasil disimpan",
  "data": {
    "id": 1,
    "current_reading": "6501.65",
    "volume_usage": "5508.55",
    "reading_at": "2024-01-15 10:30:00"
  }
}
```

**Error Responses:**
- `409`: Customer already recorded for this month
- `422`: Validation errors
- `404`: Customer or meter not found

---

### Submit Meter Reading to Pending
**PUT** `/meter-readings/{meterReadingId}/submit-to-pending`

Submit meter reading from draft status to pending and generate billing.

**Access Roles:** `admin`, `catat_meter`, `loket` + PAM Scope

**Path Parameters:**
```
meterReadingId: integer|required - Meter Reading ID
```

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "notes": "string|nullable|max:1000"
}
```

**Example Request:**
```bash
curl -X PUT "http://localhost:8000/api/v1/meter-readings/1/submit-to-pending" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"notes": "Siap untuk dibilling"}'
```

**Response (200):**
```json
{
    "status": "success",
    "message": "Meter reading berhasil disubmit ke status pending dan billing telah dibuat.",
    "data": {
        "customer": {
            "name": "Bp Wiyono"
        },
        "bill": {
            "bill_number": "BILL-1-202511-0077",
            "total_bill": "5602900.00",
            "due_date": "2025-12-10"
        }
    }
}
```

---

### Delete Meter Reading
**POST** `/meter-readings/{meterReadingId}/destroy`

Delete meter reading (draft status only).

**Access Roles:** `admin`, `catat_meter` + PAM Scope

**Path Parameters:**
```
meterReadingId: integer|required - Meter Reading ID
```

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Example Request:**
```bash
curl -X POST "http://localhost:8000/api/v1/meter-readings/1/destroy" \
  -H "Authorization: Bearer {token}"
```

**Response (200):**
```json
{
  "status": "success",
  "message": "Meter reading berhasil dihapus"
}
```

**Error Responses:**
- `404`: Meter reading not found or not draft status

---

## Payment Management

The payment module handles billing and payment processing for water utility services.

**Available Endpoints:** 3 endpoints

### Get Customer Bills
**GET** `/customers/{customerId}/bills`

Get list of pending bills for specific customer.

**Access Roles:** `admin`, `catat_meter`, `loket` + PAM Scope

**Path Parameters:**
```
customerId: integer|required - Customer ID
```

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/v1/customers/1/bills" \
  -H "Authorization: Bearer {token}"
```

**Response (200):**
```json

{
    "status": "success",
    "message": "Bills retrieved successfully",
    "data": {
        "customer": {
            "id": 104,
            "name": "SDN Tuban 3",
            "number": "22090098"
        },
        "meter_number": "98",
        "area": "RT 03",
        "tagihan": [
            {
                "id": 87,
                "bill_number": "BILL-1-202511-0072",
                "period": "2025-11-01",
                "due_date": "2025-12-10",
                "volume_usage": "13.52",
                "total_bill": "7600.00",
                "tariff_snapshot": {
                    "tariff_name": "sosial",
                    "created_at": "2025-11-12T03:09:59.539147Z",
                    "tariff_tiers": [
                        {
                            "range": "0.00 - 10.00",
                            "rate": "0.00",
                            "volume_used": 10,
                            "subtotal": 0
                        },
                        {
                            "range": "10.00 - 10000.00",
                            "rate": "600.00",
                            "volume_used": 3.5,
                            "subtotal": 2100
                        }
                    ],
                    "fixed_fees": [
                        {
                            "fee_name": "Abunemen",
                            "amount": "5500.00",
                            "description": "Biaya abunemen bulanan"
                        }
                    ],
                    "total_fixed_fees": 5500,
                    "total_tier_charge": 2100,
                    "total_bill": 7600
                }
            }
        ]
    }
}
```

---

### Pay Bills
**POST** `/customers/{customerId}/bills/pay`

Process payment for multiple bills.

**Access Roles:** `admin`, `catat_meter`, `loket` + PAM Scope

**Path Parameters:**
```
customerId: integer|required - Customer ID
```

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "bill_ids": "array|required|min:1",
  "bill_ids.*": "integer|exists:bills,id",
  "payment_method": "required|in:cash,transfer,ewallet"
}
```

**Example Request:**
```bash
curl -X POST "http://localhost:8000/api/v1/customers/1/bills/pay" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "bill_ids": [1, 2, 3],
    "payment_method": "cash"
  }'
```

**Response (200):**
```json
{
    "status": "success",
    "message": "1 bills paid successfully",
    "data": {
        "updated_bills": [
            {
                "id": 88,
                "bill_number": "BILL-1-202511-0073",
                "total_bill": "30100.00",
                "paid_at": "2025-11-15 13:17:42"
            }
        ],
        "customer_id": 10
    }
}
```

**Error Responses:**
- `404`: Customer not found or no pending bills
- `207`: Some bills updated with errors

---

### Delete Payment
**DELETE** `/bills/{billId}`

Remove paid bill and update meter reading status back to pending.

**Access Roles:** `admin`, `catat_meter`, `loket` + PAM Scope

**Path Parameters:**
```
billId: integer|required - Bill ID
```

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Example Request:**
```bash
curl -X DELETE "http://localhost:8000/api/v1/bills/1" \
  -H "Authorization: Bearer {token}"
```

**Response (200):**
```json
{
    "status": "success",
    "message": "Bill removed successfully and meter reading status updated to pending",
    "data": {
        "bill_id": 88
    }
}
```

**Error Responses:**
- `400`: Only paid bills can be removed
- `404`: Bill not found

---

## Reports

The reports module provides financial and operational reporting capabilities with export functionality.

**Available Endpoints:** 2 endpoints

### Get Monthly Payment Report
**GET** `/reports/monthly-payment-report`

Get monthly payment report with filtering and summary.

**Access Roles:** `admin`, `catat_meter`, `loket` + PAM Scope

**Query Parameters:**
```
period: string|nullable|date_format:Y-m|default:current_month
```

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/v1/reports/monthly-payment-report?period=2024-01" \
  -H "Authorization: Bearer {token}"
```

**Response (200):**
```json
{
  "status": "success",
  "message": "Laporan pembayaran bulanan berhasil diambil",
  "data": {
    "available_registered_months": [
      {
        "period": "2024-01",
        "month_name": "January 2024",
        "status": "closed"
      }
    ],
    "period": "2024-01",
    "payment_data": [
      {
        "bill_id": 1,
        "bill_number": "BILL/2024/001",
        "customer_name": "Budi Santoso",
        "customer_number": "CUS001",
        "total_bill": 150000,
        "status": "paid",
        "payment_method": "cash",
        "period": "Jan 2024",
        "issued_at": "01 Jan 2024",
        "paid_at": "15 Jan 2024",
        "paid_by": "Kasir A"
      }
    ],
    "summary": {
      "total_payments": 150,
      "total_amounts": 15000000
    }
  }
}
```

---

### Download Payment Report
**GET** `/reports/download-payment-report`

Download monthly payment report in PDF format.

**Access Roles:** `admin`, `catat_meter`, `loket` + PAM Scope

**Query Parameters:**
```
period: string|nullable|date_format:Y-m|default:current_month
```

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/v1/reports/download-payment-report?period=2024-01" \
  -H "Authorization: Bearer {token}" \
  -o "payment_report_2024-01.pdf"
```

**Response (200):**
```
Content-Type: application/pdf
Content-Disposition: attachment; filename="laporan_pembayaran_2024-01.pdf"
Content-Length: 1234567
```

**Error Responses:**
- `404`: No payment data found for period

---

## User Management

The user management module provides comprehensive user administration capabilities including role management and access control.

**Available Endpoints:** 6 endpoints

### Get Users
**GET** `/users`

Get list of users with filtering and pagination.

**Access Roles:** `superadmin`, `admin`

**Query Parameters:**
```
pam_id: integer|nullable|exists:pams,id
role: string|nullable|exists:roles,name
status: enum|nullable|in:active,inactive
search: string|nullable
per_page: integer|nullable|min:1|max:100|default:20
sort_by: enum|nullable|in:id,name,email,phone,created_at,updated_at|default:id
sort_order: enum|nullable|in:asc,desc|default:desc
```

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/v1/users?role=admin&status=active&per_page=10" \
  -H "Authorization: Bearer {token}"
```

**Response (200):**
```json
{
  "status": "success",
  "message": "Users retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "name": "Admin User",
        "email": "admin@example.com",
        "phone": "08123456789",
        "roles": ["admin"],
        "status": "active",
        "photo": "http://example.com/storage/users/photo.jpg",
        "pam": {
          "id": 1,
          "name": "PDAM Kota"
        }
      }
    ],
    "pagination": {
      "total": 50,
      "has_more_pages": true
    }
  }
}
```

---

### Get User Details
**GET** `/users/{id}`

Get detailed information about a specific user.

**Access Roles:** `superadmin`, `admin`

**Path Parameters:**
```
id: integer|required - User ID
```

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/v1/users/1" \
  -H "Authorization: Bearer {token}"
```

**Response (200):**
```json
{
  "status": "success",
  "message": "User details retrieved successfully",
  "data": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "phone": "08123456789",
    "roles": ["admin"],
    "status": "active",
    "photo": "http://example.com/storage/users/photo.jpg"
  }
}
```

---

### Update User
**PUT** `/users/{id}`

Update user information.

**Access Roles:** `superadmin`, `admin`

**Path Parameters:**
```
id: integer|required - User ID
```

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body (multipart/form-data):**
```
name: string|sometimes|max:50
email: string|sometimes|email|max:150|unique:users,{id}
phone: string|sometimes|max:20
status: boolean|sometimes
password: string|sometimes|min:6
pam_id: integer|sometimes|nullable|exists:pams,id
photo: file|sometimes|image|mimes:jpg,jpeg,png|max:5120
```

**Example Request:**
```bash
curl -X PUT "http://localhost:8000/api/v1/users/1" \
  -H "Authorization: Bearer {token}" \
  -F "name=Updated Name" \
  -F "status=1" \
  -F "photo=@new_photo.jpg"
```

**Response (200):**
```json
{
  "status": "success",
  "message": "User updated successfully",
  "data": {
    "id": 1,
    "name": "Updated Name",
    "email": "admin@example.com",
    "phone": "08123456789",
    "status": "active",
    "pam_id": 1,
    "photo": "http://example.com/storage/users/new_photo.jpg",
    "updated_at": "2024-01-15 10:30:00"
  }
}
```

---

### Assign Role
**POST** `/users/{id}/assign-role`

Assign role to user.

**Access Roles:** `superadmin`, `admin`

**Path Parameters:**
```
id: integer|required - User ID
```

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "role": "string|required|exists:roles,name"
}
```

**Example Request:**
```bash
curl -X POST "http://localhost:8000/api/v1/users/1/assign-role" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"role": "admin"}'
```

**Response (200):**
```json
{
  "status": "success",
  "message": "Role assigned successfully",
  "data": {
    "user_id": 1,
    "role": "admin",
    "roles": ["admin", "catat_meter"]
  }
}
```

**Error Responses:**
- `403`: Cannot assign superadmin role (admin only)
- `400`: User already has the role

---

### Remove Role
**DELETE** `/users/{id}/remove-role`

Remove role(s) from user.

**Access Roles:** `superadmin`, `admin`

**Path Parameters:**
```
id: integer|required - User ID
```

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body (single role):**
```json
{
  "role": "string|nullable|exists:roles,name"
}
```

**Request Body (multiple roles):**
```json
{
  "roles": "array|nullable",
  "roles.*": "string|exists:roles,name"
}
```

**Example Request:**
```bash
curl -X DELETE "http://localhost:8000/api/v1/users/1/remove-role" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"role": "catat_meter"}'
```

**Response (200):**
```json
{
  "status": "success",
  "message": "Roles removed successfully",
  "data": {
    "user_id": 1,
    "removed_roles": ["catat_meter"],
    "not_found_roles": [],
    "current_roles": ["admin"]
  }
}
```

**Error Responses:**
- `403`: Cannot remove own superadmin role

---

### Delete User
**DELETE** `/users/{id}`

Delete user (soft delete).

**Access Roles:** `superadmin` only

**Path Parameters:**
```
id: integer|required - User ID
```

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Example Request:**
```bash
curl -X DELETE "http://localhost:8000/api/v1/users/1" \
  -H "Authorization: Bearer {token}"
```

**Response (200):**
```json
{
  "status": "success",
  "message": "User deleted successfully",
  "data": {
    "name": "John Doe",
    "deleted_at": "2024-01-15 10:30:00"
  }
}
```

**Error Responses:**
- `403`: Cannot delete own account

---

## Customer Management

The customer module provides bill viewing functionality for customer users.

**Available Endpoints:** 1 endpoint

### Get My Bills
**GET** `/me/bills`

Get bills for the authenticated customer user.

**Access Roles:** `customer`

**Query Parameters:**
```
customer_id: integer|nullable|exists:customers,id
status: enum|nullable|in:pending,paid
per_page: integer|nullable|min:5|max:50|default:10
```

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/v1/me/bills?status=pending&per_page=10" \
  -H "Authorization: Bearer {token}"
```

**Response (200):**
```json
{
  "status": "success",
  "message": "Bills retrieved successfully",
  "data": {
    "items": [
      {
        "customer_name": "Budi Santoso",
        "customer_number": "CUS001",
        "meter_number": "MTR001",
        "area": "Area A",
        "periode": "2024-01",
        "due_date": "2024-02-15",
        "status": "pending",
        "previous_reading": 125.0,
        "current_reading": 150.5,
        "volume_usage": 25.5,
        "total_bill": 150000,
        "bill_number": "BILL/2024/001",
        "payment_method": "-",
        "paid_at": null,
        "issued_at": "2024-01-01 00:00:00"
      }
    ],
    "customers": [
      {
        "id": 1,
        "name": "Budi Santoso",
        "customer_number": "CUS001"
      }
    ],
    "pagination": {
      "total": 5,
      "has_more_pages": false
    }
  }
}
```

---

## PAM Management

The PAM management module provides access to water utility company (PAM) information based on user roles.

**Available Endpoints:** 1 endpoint

### Get PAMs
**GET** `/pams`

Get list of PAMs based on user role.

**Access Roles:** `superadmin`, `admin`

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/v1/pams" \
  -H "Authorization: Bearer {token}"
```

**Response (200):**
```json
{
  "status": "success",
  "message": "PAMs retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "PDAM Kota Bandung"
    },
    {
      "id": 2,
      "name": "PDAM Kota Jakarta"
    }
  ]
}
```

**Note:**
- SuperAdmin: Can see all active PAMs
- Admin: Can only see their own PAM

---

## Registered Month Management

The registered month module manages monthly periods for meter reading operations with statistics tracking.

**Available Endpoints:** 2 endpoints

### Get Month List
**GET** `/registered-months/list/{year}`

Get list of registered months for specific year with statistics.

**Access Roles:** `admin`, `catat_meter`, `loket` + PAM Scope

**Path Parameters:**
```
year: integer|required
```

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/v1/registered-months/list/2024" \
  -H "Authorization: Bearer {token}"
```

**Response (200):**
```json
{
  "status": "success",
  "message": "Month list retrieved successfully",
  "data": {
    "year": 2024,
    "available_years": [2024, 2023, 2022],
    "items": [
      {
        "id": 1,
        "month_name": "Januari",
        "year": 2024,
        "recorded_customers": 150,
        "total_customers": 200,
        "total_usage": "588.50",
        "total_bills": "599600.00",
        "status": "open"
      },
      {
        "id": 2,
        "month_name": "Februari",
        "year": 2024,
        "recorded_customers": 180,
        "total_customers": 200,
        "total_usage": "588.50",
        "total_bills": "599600.00",
        "status": "closed"
      }
    ]
  }
}
```

---

### Create Registered Month
**POST** `/registered-months/store`

Create new registered month period.

**Access Roles:** `admin`, `catat_meter`, `loket` + PAM Scope

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "pam_id": "integer|required|exists:pams,id",
  "period": "string|required|date_format:Y-m-d"
}
```

**Example Request:**
```bash
curl -X POST "http://localhost:8000/api/v1/registered-months/store" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "pam_id": 1,
    "period": "2024-03-01"
  }'
```

**Response (201):**
```json
{
  "status": "success",
  "message": "Month created successfully",
  "data": {
    "id": 3,
    "pam_id": 1,
    "period": "2024-03-01",
    "total_customers": 200,
    "total_usage": 0,
    "total_bills": 0,
    "status": "open",
    "registered_by": 1,
    "created_at": "2024-01-15 10:30:00",
    "updated_at": "2024-01-15 10:30:00"
  }
}
```

**Error Responses:**
- `409`: Month period already exists for this PAM
- `403`: Cannot create month for different PAM (non-superadmin)

---

## Error Responses

### Standard Error Format
```json
{
  "status": "error",
  "message": "Error description",
  "code": 400,
  "errors": {
    "field_name": ["Error message"]
  }
}
```

### Common HTTP Status Codes
- `200`: Success
- `201`: Created
- `207`: Multi-Status (some operations succeeded, some failed)
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Not Found
- `409`: Conflict
- `422`: Validation Error
- `500`: Internal Server Error

### Validation Error Example (422)
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 6 characters."]
  }
}
```

### Access Denied Example (403)
```json
{
  "status": "error",
  "message": "Access denied. You can only access your own PAM data.",
  "code": 403
}
```

---

## Response Formats

### Success Response
```json
{
  "status": "success",
  "message": "Operation successful",
  "data": {
    // Response data here
  }
}
```

### Created Response
```json
{
  "status": "success",
  "message": "Resource created successfully",
  "data": {
    // Created resource data
  }
}
```

### Paginated Response
```json
{
  "status": "success",
  "message": "Data retrieved successfully",
  "data": {
    "items": [
      // Array of items
    ],
    "pagination": {
      "total": 150,
      "has_more_pages": true,
      "current_page": 1,
      "per_page": 20
    }
  }
}
```

---

## Authentication & Security

### Bearer Token Authentication
Include the access token in the Authorization header:
```
Authorization: Bearer {your-token-here}
```

### Role-Based Access Control
- **superadmin**: Full access to all PAMs and features
- **admin**: Full access to own PAM data and user management
- **catat_meter**: Meter reading and basic operations for own PAM
- **loket**: Payment processing for own PAM
- **customer**: View own bills only

### PAM Scope Filtering
Non-superadmin users can only access data from their own PAM. This is enforced at:
- Route level (pam.scope middleware)
- Controller level (explicit filtering)
- Database query level (where clauses)

---

## File Uploads

### Supported File Types
- **Images**: `.jpg`, `.jpeg`, `.png`
- **Maximum Size**: 2MB for meter photos, 2MB for profile photos

### Upload Format
Use `multipart/form-data` for file uploads:
```bash
curl -X POST "http://localhost:8000/api/v1/endpoint" \
  -H "Authorization: Bearer {token}" \
  -F "field1=value1" \
  -F "photo=@file.jpg"
```

---

## Rate Limiting & Performance

### Pagination
- Use `per_page` parameter to limit results
- Maximum `per_page`: 100 items
- Default `per_page`: 20 items

### Caching
Some endpoints implement caching for better performance:
- PAM data: 30-60 minutes cache
- Available years: 1 hour cache

### Search Optimization
- Use specific search terms rather than broad queries
- Search works on customer name, number, and meter number
- Use prefix search for better performance (e.g., "bud" instead of "%budi%")

---

## Testing

### Test Credentials
Contact your system administrator for test credentials.

### Common Test Scenarios
1. **Authentication Flow**: Login → Get Profile → Logout
2. **Meter Reading Workflow**: Get Unrecorded Customers → Get Form → Store Reading → Submit to Pending
3. **Payment Flow**: Get Bills → Process Payment → Download Report
4. **User Management**: Create User → Assign Role → Update User → Delete User

### Sample cURL Commands
See individual endpoint documentation for sample cURL commands.

---

## API Endpoints Summary

### Complete Endpoint List

| Module | Endpoints | Total |
|--------|-----------|-------|
| **Authentication** | `POST /auth/login`, `GET /auth/profile`, `PUT /auth/profile`, `POST /auth/logout` | 4 |
| **Meter Reading** | `GET /meter-readings/list`, `GET /customers/unrecorded`, `GET /customers/{id}/meter-reading-form`, `POST /meter-readings/store`, `PUT /meter-readings/{id}/submit-to-pending`, `POST /meter-readings/{id}/destroy` | 6 |
| **Payment** | `GET /customers/{id}/bills`, `POST /customers/{id}/bills/pay`, `DELETE /bills/{id}` | 3 |
| **Reports** | `GET /reports/monthly-payment-report`, `GET /reports/download-payment-report` | 2 |
| **User Management** | `GET /users`, `GET /users/{id}`, `PUT /users/{id}`, `POST /users/{id}/assign-role`, `DELETE /users/{id}/remove-role`, `DELETE /users/{id}` | 6 |
| **Customer** | `GET /me/bills` | 1 |
| **PAM Management** | `GET /pams` | 1 |
| **Registered Month** | `GET /registered-months/list/{year}`, `POST /registered-months/store` | 2 |
| **TOTAL** | | **25 endpoints** |

### Role-Based Access Matrix

| Role | Auth | Meter Reading | Payment | Reports | Users | Customer Bills | PAM | Registered Month |
|------|------|---------------|---------|---------|-------|----------------|-----|------------------|
| **superadmin** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ |
| **admin** | ✅ | ✅ | ✅ | ✅ | ✅ (own PAM) | ❌ | ✅ (own only) | ✅ |
| **catat_meter** | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| **loket** | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| **customer** | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |

### Security Features Implemented

1. **Multi-tenant Isolation**: PAM scope filtering at route, controller, and query levels
2. **Role-based Access Control**: Hierarchical permissions with role validation
3. **Input Validation**: Comprehensive validation rules for all inputs
4. **File Upload Security**: Type and size validation for image uploads
5. **Transaction Management**: Database transactions for data integrity
6. **Rate Limiting**: Pagination controls to prevent abuse
7. **Audit Logging**: Error and access logging for security monitoring

### Performance Optimizations

1. **Query Optimization**: Selective column loading and proper indexing
2. **Caching Strategy**: PAM data and available years caching
3. **Pagination**: Configurable pagination with limits
4. **N+1 Prevention**: Eager loading for related data
5. **Search Optimization**: Prefix-based search with indexes

---

## Support

For API support and questions:
- Contact development team
- Check application logs for debugging
- Review validation error messages for troubleshooting

---

*This documentation is for Catat Meter API V1. Last updated: January 2024*

**Documentation Version:** 1.0.0
**API Version:** v1
**Total Endpoints:** 25
**Last Review:** January 2024

---

## Changelog

### v1.0.0 (January 2024)
- Initial API documentation
- 25 endpoints across 8 modules
- Complete authentication and authorization flow
- Multi-tenant PAM security implementation
- Comprehensive meter reading workflow
- Payment processing and reporting system