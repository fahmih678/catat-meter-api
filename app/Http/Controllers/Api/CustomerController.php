<?php

namespace App\Http\Controllers\Api;

use App\Helpers\RoleHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Http\Traits\HasPamFiltering;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use HasPamFiltering;
    protected CustomerService $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $pamId = $request->get('pam_id');
            $filters = $request->only(['name', 'customer_number', 'area_id', 'status', 'phone', 'per_page']);

            if ($pamId) {
                // Specific PAM requested - use service with PAM filtering
                $customers = $this->customerService->searchCustomers($pamId, $filters);
            } else {
                // No specific PAM - use general pagination with PAM filtering
                $customers = $this->customerService->getPaginatedWithPamFilter($filters);
            }

            return $this->successResponse($customers, 'Customers retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve customers: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $customer = $this->customerService->findById($id);

            if (!$customer) {
                return $this->notFoundResponse('Customer not found');
            }

            // Check PAM access permission using trait
            $accessError = $this->checkEntityPamAccess($customer);
            if ($accessError) {
                return $accessError;
            }

            return $this->successResponse($customer, 'Customer retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve customer: ' . $e->getMessage());
        }
    }

    public function store(CustomerRequest $request): JsonResponse
    {
        try {
            $customer = $this->customerService->create($request->validated());
            return $this->createdResponse($customer, 'Customer created successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create customer: ' . $e->getMessage());
        }
    }

    public function update(CustomerRequest $request, int $id): JsonResponse
    {
        try {
            $customer = $this->customerService->update($id, $request->validated());
            return $this->updatedResponse($customer, 'Customer updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update customer: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->customerService->delete($id);
            return $this->deletedResponse('Customer deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete customer: ' . $e->getMessage());
        }
    }

    public function byPam(int $pamId): JsonResponse
    {
        try {
            $customers = $this->customerService->getByPam($pamId);
            return $this->successResponse($customers, 'PAM customers retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve PAM customers: ' . $e->getMessage());
        }
    }

    public function byArea(int $areaId): JsonResponse
    {
        try {
            $customers = $this->customerService->getByArea($areaId);
            return $this->successResponse($customers, 'Area customers retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve area customers: ' . $e->getMessage());
        }
    }

    public function unpaidBills(int $pamId): JsonResponse
    {
        try {
            $customers = $this->customerService->getActiveCustomersWithUnpaidBills($pamId);
            return $this->successResponse($customers, 'Customers with unpaid bills retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve customers with unpaid bills: ' . $e->getMessage());
        }
    }

    public function withoutMeters(int $pamId): JsonResponse
    {
        try {
            $customers = $this->customerService->getCustomersWithoutMeters($pamId);
            return $this->successResponse($customers, 'Customers without meters retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve customers without meters: ' . $e->getMessage());
        }
    }

    public function activate(int $id): JsonResponse
    {
        try {
            $customer = $this->customerService->activateCustomer($id);
            return $this->updatedResponse($customer, 'Customer activated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to activate customer: ' . $e->getMessage());
        }
    }

    public function deactivate(int $id): JsonResponse
    {
        try {
            $customer = $this->customerService->deactivateCustomer($id);
            return $this->updatedResponse($customer, 'Customer deactivated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to deactivate customer: ' . $e->getMessage());
        }
    }

    public function transferArea(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'area_id' => 'required|exists:areas,id'
            ]);

            $customer = $this->customerService->transferToArea($id, $request->area_id);
            return $this->updatedResponse($customer, 'Customer transferred to new area successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to transfer customer: ' . $e->getMessage());
        }
    }

    public function changeTariff(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'tariff_group_id' => 'required|exists:tariff_groups,id'
            ]);

            $customer = $this->customerService->changeTariffGroup($id, $request->tariff_group_id);
            return $this->updatedResponse($customer, 'Customer tariff group changed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to change customer tariff: ' . $e->getMessage());
        }
    }

    public function restore(int $id): JsonResponse
    {
        try {
            $result = $this->customerService->restore($id);

            if (!$result) {
                return $this->notFoundResponse('Customer not found or already active');
            }

            return $this->successResponse(null, 'Customer restored successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to restore customer: ' . $e->getMessage());
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $pamId = $request->get('pam_id');
            $query = $request->get('q', '');

            if (empty($pamId)) {
                return $this->errorResponse('PAM ID is required for search');
            }

            $filters = [
                'query' => $query,
                'per_page' => $request->get('per_page', 15)
            ];

            $customers = $this->customerService->searchCustomers($pamId, $filters);
            return $this->successResponse($customers, 'Customers search completed');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to search customers: ' . $e->getMessage());
        }
    }
}
