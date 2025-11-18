<?php

namespace App\Documentation;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Catat Meter API V1",
 *     description="API documentation for Catat Meter - Water Utility Management System. This API provides endpoints for managing customers, meter readings, billing, and payments for water utility companies (PAM).",
 *     @OA\Contact(
 *         email="support@catatmeter.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8000",
 *     description="Catat Meter API Development Server"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="API endpoints for user authentication and profile management"
 * )
 *
 * @OA\Tag(
 *     name="Customer",
 *     description="API endpoints for customer management including unrecorded lists and bill information"
 * )
 *
 * @OA\Tag(
 *     name="Meter Reading",
 *     description="API endpoints for meter reading management and operations"
 * )
 *
 * @OA\Tag(
 *     name="Payment",
 *     description="API endpoints for bill payment operations and management"
 * )
 *
 * @OA\Tag(
 *     name="User Management",
 *     description="API endpoints for user management and role assignments (Admin/SuperAdmin only)"
 * )
 *
 * @OA\Tag(
 *     name="PAM",
 *     description="API endpoints for PAM (Water Utility Company) management"
 * )
 *
 * @OA\Tag(
 *     name="Registered Month",
 *     description="API endpoints for registered month management and reporting periods"
 * )
 *
 * @OA\Tag(
 *     name="Report",
 *     description="API endpoints for generating and downloading reports"
 * )
 *
 * // Authentication Endpoints
 *
 * @OA\Post(
 *     path="/api/v1/auth/login",
 *     tags={"Authentication"},
 *     summary="User login",
 *     description="Authenticate user and return access token",
 *     operationId="login",
 *     security={},
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email","password"},
 *             @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
 *             @OA\Property(property="password", type="string", example="password123"),
 *             @OA\Property(property="device_name", type="string", example="Mobile App")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Login successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Login successful"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="user", type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="John Doe"),
 *                     @OA\Property(property="email", type="string", example="admin@example.com"),
 *                     @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"admin"}),
 *                     @OA\Property(property="status", type="string", example="active")
 *                 ),
 *                 @OA\Property(property="token", type="string", example="1|abc123def456..."),
 *                 @OA\Property(property="token_type", type="string", example="Bearer")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=401,
 *         description="Invalid credentials",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="message", type="string", example="Login failed - Invalid credentials")
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/v1/auth/profile",
 *     tags={"Authentication"},
 *     summary="Get user profile",
 *     description="Get authenticated user profile information",
 *     operationId="getProfile",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="John Doe"),
 *                 @OA\Property(property="email", type="string", example="admin@example.com"),
 *                 @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"admin"}),
 *                 @OA\Property(property="status", type="string", example="active")
 *             )
 *         )
 *     )
 * )
 *
 * // Customer Endpoints
 *
 * @OA\Get(
 *     path="/api/v1/customers/unrecorded",
 *     tags={"Customer"},
 *     summary="Get unrecorded customers list",
 *     description="Retrieve customers who haven't had meter readings recorded for a specific month",
 *     operationId="getUnrecordedCustomers",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="registered_month_id",
 *         in="query",
 *         description="ID of the registered month",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Parameter(
 *         name="area_id",
 *         in="query",
 *         description="Filter by area ID",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Search by customer name, number, or meter number",
 *         required=false,
 *         @OA\Schema(type="string", example="John")
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="items", type="array", @OA\Items(type="object",
 *                     @OA\Property(property="customer_id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="John Doe"),
 *                     @OA\Property(property="number", type="string", example="CUST001"),
 *                     @OA\Property(property="area_name", type="string", example="Area 1"),
 *                     @OA\Property(property="meter_number", type="string", example="M001")
 *                 ))
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/v1/me/bills",
 *     tags={"Customer"},
 *     summary="Get customer bills",
 *     description="Retrieve bills for the authenticated customer",
 *     operationId="getMyBills",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         description="Filter by bill status",
 *         required=false,
 *         @OA\Schema(type="string", enum={"pending","paid"}, example="pending")
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="items", type="array", @OA\Items(type="object",
 *                     @OA\Property(property="bill_number", type="string", example="BILL-2024-001"),
 *                     @OA\Property(property="customer_name", type="string", example="John Doe"),
 *                     @OA\Property(property="total_bill", type="number", format="float", example=25000),
 *                     @OA\Property(property="status", type="string", example="pending")
 *                 ))
 *             )
 *         )
 *     )
 * )
 *
 * // Meter Reading Endpoints
 *
 * @OA\Get(
 *     path="/api/v1/meter-readings/list",
 *     tags={"Meter Reading"},
 *     summary="Get meter readings list",
 *     description="Retrieve list of meter readings with filtering options",
 *     operationId="getMeterReadingList",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="registered_month_id",
 *         in="query",
 *         description="Filter by registered month ID",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Search by customer name, number, or meter number",
 *         required=false,
 *         @OA\Schema(type="string", example="John")
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="items", type="array", @OA\Items(type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="customer_name", type="string", example="John Doe"),
 *                     @OA\Property(property="meter_number", type="string", example="M001"),
 *                     @OA\Property(property="current_reading", type="number", format="float", example=115.5),
 *                     @OA\Property(property="status", type="string", example="draft")
 *                 ))
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/v1/meter-readings/store",
 *     tags={"Meter Reading"},
 *     summary="Store new meter reading",
 *     description="Record a new meter reading for a customer",
 *     operationId="storeMeterReading",
 *     security={{"sanctum": {}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"customer_id","registered_month_id","current_reading"},
 *             @OA\Property(property="customer_id", type="integer", example=1),
 *             @OA\Property(property="registered_month_id", type="integer", example=1),
 *             @OA\Property(property="current_reading", type="number", format="float", example=115.5),
 *             @OA\Property(property="photo", type="string", format="binary", description="Meter reading photo (optional)"),
 *             @OA\Property(property="notes", type="string", example="Meter shows normal reading", description="Additional notes (optional)")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Meter reading created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Meter reading recorded successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="current_reading", type="number", format="float", example=115.5),
 *                 @OA\Property(property="volume_usage", type="number", format="float", example=15.5),
 *                 @OA\Property(property="status", type="string", example="draft")
 *             )
 *         )
 *     )
 * )
 *
 * // Payment Endpoints
 *
 * @OA\Get(
 *     path="/api/v1/customers/{customerId}/bills",
 *     tags={"Payment"},
 *     summary="Get customer bills",
 *     description="Retrieve bills for a specific customer",
 *     operationId="getCustomerBills",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="customerId",
 *         in="path",
 *         description="Customer ID",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="items", type="array", @OA\Items(type="object",
 *                     @OA\Property(property="bill_number", type="string", example="BILL-2024-001"),
 *                     @OA\Property(property="customer_name", type="string", example="John Doe"),
 *                     @OA\Property(property="total_bill", type="number", format="float", example=27500),
 *                     @OA\Property(property="status", type="string", example="pending")
 *                 ))
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/v1/customers/{customerId}/bills/pay",
 *     tags={"Payment"},
 *     summary="Pay customer bills",
 *     description="Process payment for one or more customer bills",
 *     operationId="payBills",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="customerId",
 *         in="path",
 *         description="Customer ID",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"bill_ids","payment_method"},
 *             @OA\Property(property="bill_ids", type="array", @OA\Items(type="integer"), example={1,2}),
 *             @OA\Property(property="payment_method", type="string", enum={"cash","transfer","edc"}, example="cash"),
 *             @OA\Property(property="amount_paid", type="number", format="float", example=55000.0)
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Payment processed successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Payment processed successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="payment_id", type="integer", example=1),
 *                 @OA\Property(property="total_amount", type="number", format="float", example=55000.0),
 *                 @OA\Property(property="payment_method", type="string", example="cash")
 *             )
 *         )
 *     )
 * )
 *
 * // User Management Endpoints
 *
 * @OA\Get(
 *     path="/api/v1/users",
 *     tags={"User Management"},
 *     summary="Get users list",
 *     description="Retrieve list of users with filtering and pagination",
 *     operationId="getUsers",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Search by name or email",
 *         required=false,
 *         @OA\Schema(type="string", example="john")
 *     ),
 *
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         description="Number of items per page",
 *         required=false,
 *         @OA\Schema(type="integer", default=25, minimum=10, maximum=100)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="items", type="array", @OA\Items(type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="John Doe"),
 *                     @OA\Property(property="email", type="string", example="john@example.com"),
 *                     @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"admin"}),
 *                     @OA\Property(property="status", type="string", example="active")
 *                 ))
 *             )
 *         )
 *     )
 * )
 *
 * // PAM Endpoints
 *
 * @OA\Get(
 *     path="/api/v1/pams",
 *     tags={"PAM"},
 *     summary="Get PAMs list",
 *     description="Retrieve list of PAM (Water Utility Companies)",
 *     operationId="getPams",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Search by PAM name",
 *         required=false,
 *         @OA\Schema(type="string", example="PDAM")
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="items", type="array", @OA\Items(type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="PDAM Kota"),
 *                     @OA\Property(property="code", type="string", example="PDM001"),
 *                     @OA\Property(property="status", type="string", example="active"),
 *                     @OA\Property(property="total_customers", type="integer", example=1500)
 *                 ))
 *             )
 *         )
 *     )
 * )
 *
 * // Additional Important Endpoints
 *
 * @OA\Put(
 *     path="/api/v1/meter-readings/{meterReadingId}/submit-to-pending",
 *     tags={"Meter Reading"},
 *     summary="Submit meter reading to pending",
 *     description="Change meter reading status from draft to pending for billing",
 *     operationId="submitToPending",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="meterReadingId",
 *         in="path",
 *         description="Meter reading ID",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Meter reading submitted to pending successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Meter reading submitted for billing"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="status", type="string", example="pending")
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/v1/registered-months/list/{year}",
 *     tags={"Registered Month"},
 *     summary="Get months list for a year",
 *     description="Retrieve list of registered months for a specific year",
 *     operationId="getMonthList",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="year",
 *         in="path",
 *         description="Year (YYYY format)",
 *         required=true,
 *         @OA\Schema(type="integer", example=2024)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="year", type="integer", example=2024),
 *                 @OA\Property(property="months", type="array", @OA\Items(type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="month", type="string", example="2024-01"),
 *                     @OA\Property(property="month_name", type="string", example="Januari 2024"),
 *                     @OA\Property(property="is_active", type="boolean", example=true)
 *                 ))
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/v1/reports/monthly-payment-report",
 *     tags={"Report"},
 *     summary="Get monthly payment report",
 *     description="Generate monthly payment report with detailed statistics",
 *     operationId="getMonthlyPaymentReport",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="registered_month_id",
 *         in="query",
 *         description="ID of the registered month",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="period", type="string", example="Januari 2024"),
 *                 @OA\Property(property="summary", type="object",
 *                     @OA\Property(property="total_customers", type="integer", example=1500),
 *                     @OA\Property(property="total_bills", type="integer", example=1450),
 *                     @OA\Property(property="total_payments", type="integer", example=1200),
 *                     @OA\Property(property="total_amount_paid", type="number", format="float", example=30000000.0)
 *                 )
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Delete(
 *     path="/api/v1/bills/{billId}",
 *     tags={"Payment"},
 *     summary="Cancel bill payment",
 *     description="Cancel or reverse a bill payment (admin only)",
 *     operationId="destroyBill",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="billId",
 *         in="path",
 *         description="Bill ID",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Bill payment cancelled successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Bill payment cancelled successfully")
 *         )
 *     )
 * )
 *
 * @OA\Delete(
 *     path="/api/v1/meter-readings/{meterReadingId}/destroy",
 *     tags={"Meter Reading"},
 *     summary="Delete meter reading",
 *     description="Delete a meter reading (only if status is draft)",
 *     operationId="deleteMeterReadingByDestroyMethod",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="meterReadingId",
 *         in="path",
 *         description="Meter reading ID",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Meter reading deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Meter reading deleted successfully")
 *         )
 *     )
 * )
 *
 * // Additional Missing Endpoints
 *
 * @OA\Put(
 *     path="/api/v1/auth/profile",
 *     tags={"Authentication"},
 *     summary="Update user profile",
 *     description="Update authenticated user profile information",
 *     operationId="updateProfile",
 *     security={{"sanctum": {}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="name", type="string", example="John Doe Updated"),
 *             @OA\Property(property="phone", type="string", example="+628123456789"),
 *             @OA\Property(property="address", type="string", example="123 Main St Updated"),
 *             @OA\Property(property="current_password", type="string", format="password", example="currentpass123"),
 *             @OA\Property(property="password", type="string", format="password", example="newpass123"),
 *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpass123")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Profile updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Profile updated successfully")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/v1/auth/logout",
 *     tags={"Authentication"},
 *     summary="User logout",
 *     description="Logout user and revoke access token",
 *     operationId="logout",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Response(
 *         response=200,
 *         description="Logout successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Logout successful")
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/v1/customers/{id}/meter-reading-form",
 *     tags={"Meter Reading"},
 *     summary="Get meter reading form for customer",
 *     description="Get form data and previous meter reading information for inputting new meter reading",
 *     operationId="getMeterReadingForm",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="Customer ID",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Parameter(
 *         name="registered_month_id",
 *         in="query",
 *         description="ID of the registered month",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="customer_id", type="integer", example=1),
 *                 @OA\Property(property="customer_name", type="string", example="John Doe"),
 *                 @OA\Property(property="meter_number", type="string", example="M001"),
 *                 @OA\Property(property="previous_reading", type="number", format="float", example=100.0),
 *                 @OA\Property(property="previous_period", type="string", example="2023-12")
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/v1/reports/download-payment-report",
 *     tags={"Report"},
 *     summary="Download payment report",
 *     description="Download payment report in Excel or PDF format",
 *     operationId="downloadPaymentReport",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="registered_month_id",
 *         in="query",
 *         description="ID of the registered month",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Parameter(
 *         name="format",
 *         in="query",
 *         description="Export format",
 *         required=false,
 *         @OA\Schema(type="string", enum={"excel","pdf"}, default="excel")
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="File download successful",
 *         @OA\Header(
 *             header="Content-Type",
 *             description="File content type",
 *             @OA\Schema(type="string", example="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/v1/registered-months/available-months-report",
 *     tags={"Registered Month"},
 *     summary="Get available months for reports",
 *     description="Retrieve list of available months that can be used for generating reports",
 *     operationId="getAvailableMonthsReport",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="year",
 *         in="query",
 *         description="Filter by year",
 *         required=false,
 *         @OA\Schema(type="integer", example=2024)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="available_years", type="array", @OA\Items(type="integer"), example={2022,2023,2024}),
 *                 @OA\Property(property="available_months", type="array", @OA\Items(type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="month", type="string", example="2024-01"),
 *                     @OA\Property(property="has_meter_readings", type="boolean", example=true)
 *                 ))
 *             )
 *         )
 *     )
 * )
 *
 * // User Management Additional Endpoints
 *
 * @OA\Get(
 *     path="/api/v1/users/{id}",
 *     tags={"User Management"},
 *     summary="Get user details",
 *     description="Retrieve detailed information about a specific user",
 *     operationId="getUser",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="User ID",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="John Doe"),
 *                 @OA\Property(property="email", type="string", example="admin@example.com"),
 *                 @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"admin"}),
 *                 @OA\Property(property="status", type="string", example="active")
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Put(
 *     path="/api/v1/users/{id}",
 *     tags={"User Management"},
 *     summary="Update user",
 *     description="Update user information",
 *     operationId="updateUser",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="User ID",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="name", type="string", example="John Doe Updated"),
 *             @OA\Property(property="email", type="string", format="email", example="john.updated@example.com"),
 *             @OA\Property(property="phone", type="string", example="+628123456789"),
 *             @OA\Property(property="status", type="string", enum={"active","inactive"}, example="active")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="User updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="User updated successfully")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/v1/users/{id}/assign-role",
 *     tags={"User Management"},
 *     summary="Assign role to user",
 *     description="Assign a role to a user",
 *     operationId="assignRole",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="User ID",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"role"},
 *             @OA\Property(property="role", type="string", enum={"superadmin","admin","catat_meter","loket","customer"}, example="admin")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Role assigned successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Role assigned successfully")
 *         )
 *     )
 * )
 *
 * @OA\Delete(
 *     path="/api/v1/users/{id}/remove-role",
 *     tags={"User Management"},
 *     summary="Remove role from user",
 *     description="Remove a role from a user",
 *     operationId="removeRole",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="User ID",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"role"},
 *             @OA\Property(property="role", type="string", enum={"superadmin","admin","catat_meter","loket","customer"}, example="admin")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Role removed successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Role removed successfully")
 *         )
 *     )
 * )
 *
 * @OA\Delete(
 *     path="/api/v1/users/{id}",
 *     tags={"User Management"},
 *     summary="Delete user",
 *     description="Delete a user (soft delete)",
 *     operationId="deleteUser",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="User ID",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="User deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="User deleted successfully")
 *         )
 *     )
 * )
 *
 * // Sync Operation
 *
 * @OA\Post(
 *     path="/api/v1/sync/payment-summary/{registeredMonthId}",
 *     tags={"Report"},
 *     summary="Sync payment summary data",
 *     description="Synchronize payment summary data for a specific month",
 *     operationId="syncPaymentSummaryForMonth",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="registeredMonthId",
 *         in="path",
 *         description="Registered month ID",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Payment summary synchronized successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Payment summary synchronized successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="registered_month_id", type="integer", example=1),
 *                 @OA\Property(property="total_payments_synced", type="integer", example=1200),
 *                 @OA\Property(property="total_amount_synced", type="number", format="float", example=30000000.0)
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/v1/meter-readings/{meterReadingId}/destroy",
 *     tags={"Meter Reading"},
 *     summary="Delete meter reading",
 *     description="Delete a meter reading (only if status is draft)",
 *     operationId="postDeleteMeterReading",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(
 *         name="meterReadingId",
 *         in="path",
 *         description="Meter reading ID",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Meter reading deleted successfully",
 *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Meter reading deleted successfully")
     *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/v1/registered-months/store",
 *     tags={"Registered Month"},
 *     summary="Create new registered month",
 *     description="Create a new registered month for billing period",
 *     operationId="storeRegisteredMonth",
 *     security={{"sanctum": {}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"month"},
 *             @OA\Property(property="month", type="string", format="date", example="2024-01"),
 *             @OA\Property(property="description", type="string", example="Billing period for January 2024")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Registered month created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Registered month created successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="month", type="string", example="2024-01"),
 *                 @OA\Property(property="is_active", type="boolean", example=true)
 *             )
 *         )
 *     )
 * )
 *
 * // Security Scheme
 *
 * @OA\SecurityScheme(
 *     type="apiKey",
 *     description="Enter token in format (Bearer <token>)",
 *     name="Authorization",
 *     in="header",
 *     securityScheme="sanctum"
 * )
 */
class AllDocumentation {}