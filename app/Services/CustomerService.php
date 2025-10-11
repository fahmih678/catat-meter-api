<?php

namespace App\Services;

use App\Models\Customer;
use App\Repositories\CustomerRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class CustomerService extends BaseService
{
    protected CustomerRepository $customerRepository;

    public function __construct(CustomerRepository $customerRepository)
    {
        parent::__construct($customerRepository);
        $this->customerRepository = $customerRepository;
    }

    public function findByCustomerNumber(int $pamId, string $customerNumber): ?Customer
    {
        return $this->customerRepository->findByCustomerNumber($pamId, $customerNumber);
    }

    public function getByPam(int $pamId): Collection
    {
        return $this->customerRepository->getByPam($pamId);
    }

    public function getByArea(int $areaId): Collection
    {
        return $this->customerRepository->getByArea($areaId);
    }

    public function searchCustomers(int $pamId, array $filters = []): LengthAwarePaginator
    {
        return $this->customerRepository->searchCustomers($pamId, $filters);
    }

    public function getActiveCustomersWithUnpaidBills(int $pamId): Collection
    {
        return $this->customerRepository->getActiveCustomersWithUnpaidBills($pamId);
    }

    public function getCustomersWithoutMeters(int $pamId): Collection
    {
        return $this->customerRepository->getCustomersWithoutMeters($pamId);
    }

    public function create(array $data): Customer
    {
        // Generate customer number if not provided
        if (empty($data['customer_number'])) {
            $data['customer_number'] = $this->generateCustomerNumber($data['pam_id']);
        }

        return parent::create($data);
    }

    public function activateCustomer(int $customerId): Customer
    {
        return $this->update($customerId, ['status' => 'active']);
    }

    public function deactivateCustomer(int $customerId): Customer
    {
        return $this->update($customerId, ['status' => 'inactive']);
    }

    public function transferToArea(int $customerId, int $newAreaId): Customer
    {
        $customer = $this->findByIdOrFail($customerId);

        // Validasi area masih dalam PAM yang sama
        if ($customer->pam_id !== \App\Models\Area::find($newAreaId)?->pam_id) {
            throw new \Exception('Area must be within the same PAM');
        }

        return $this->update($customerId, ['area_id' => $newAreaId]);
    }

    public function changeTariffGroup(int $customerId, int $newTariffGroupId): Customer
    {
        $customer = $this->findByIdOrFail($customerId);

        // Validasi tariff group masih dalam PAM yang sama
        if ($customer->pam_id !== \App\Models\TariffGroup::find($newTariffGroupId)?->pam_id) {
            throw new \Exception('Tariff group must be within the same PAM');
        }

        return $this->update($customerId, ['tariff_group_id' => $newTariffGroupId]);
    }

    private function generateCustomerNumber(int $pamId): string
    {
        $pam = \App\Models\Pam::find($pamId);
        $pamCode = $pam ? $pam->code : 'PAM';

        // Get last customer number for this PAM
        $lastCustomer = $this->customerRepository->getByPam($pamId)
            ->sortByDesc('customer_number')
            ->first();

        if ($lastCustomer && preg_match('/(\d+)$/', $lastCustomer->customer_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return $pamCode . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    protected function afterCreate($model, array $data): void
    {
        // Create activity log
        \App\Models\ActivityLog::create([
            'pam_id' => $model->pam_id,
            'user_id' => Auth::id() ?? 1, // Use default user for testing
            'activity_type' => 'customer_created',
            'description' => "Customer {$model->name} ({$model->customer_number}) created",
            'table_name' => 'customers',
            'record_id' => $model->id,
            'new_values' => $model->toArray(),
        ]);
    }

    protected function afterUpdate($model, array $data, array $oldData): void
    {
        // Log status changes
        if (isset($data['status']) && $data['status'] !== $oldData['status']) {
            \App\Models\ActivityLog::create([
                'pam_id' => $model->pam_id,
                'user_id' => Auth::id() ?? 1, // Use default user for testing
                'activity_type' => 'customer_status_changed',
                'description' => "Customer {$model->name} status changed from {$oldData['status']} to {$data['status']}",
                'table_name' => 'customers',
                'record_id' => $model->id,
                'old_values' => ['status' => $oldData['status']],
                'new_values' => ['status' => $data['status']],
            ]);
        }
    }

    protected function beforeDelete($model): void
    {
        // Check if customer has active meters
        if ($model->meters()->where('status', 'active')->exists()) {
            throw new \Exception('Cannot delete customer with active meters');
        }

        // Check if customer has unpaid bills
        if ($model->bills()->where('status', 'pending')->exists()) {
            throw new \Exception('Cannot delete customer with unpaid bills');
        }
    }
}
