<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository extends BaseRepository
{
    protected function getModel(): Model
    {
        return new Customer();
    }

    public function findByCustomerNumber(int $pamId, string $customerNumber): ?Customer
    {
        return $this->model->where('pam_id', $pamId)
            ->where('customer_number', $customerNumber)
            ->first();
    }

    public function getByPam(int $pamId): Collection
    {
        return $this->model->where('pam_id', $pamId)
            ->with(['area', 'tariffGroup', 'meters'])
            ->get();
    }

    public function getByArea(int $areaId): Collection
    {
        return $this->model->where('area_id', $areaId)
            ->with(['pam', 'tariffGroup', 'meters'])
            ->get();
    }

    public function searchCustomers(int $pamId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->where('pam_id', $pamId)
            ->with(['area', 'tariffGroup', 'meters']);

        if (!empty($filters['name'])) {
            $query->where('name', 'like', "%{$filters['name']}%");
        }

        if (!empty($filters['customer_number'])) {
            $query->where('customer_number', 'like', "%{$filters['customer_number']}%");
        }

        if (!empty($filters['area_id'])) {
            $query->where('area_id', $filters['area_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['phone'])) {
            $query->where('phone', 'like', "%{$filters['phone']}%");
        }

        return $query->orderBy('name')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getActiveCustomersWithUnpaidBills(int $pamId): Collection
    {
        return $this->model->where('pam_id', $pamId)
            ->where('status', 'active')
            ->whereHas('bills', function ($query) {
                $query->where('status', 'pending');
            })
            ->with(['bills' => function ($query) {
                $query->where('status', 'pending');
            }])
            ->get();
    }

    public function getCustomersWithoutMeters(int $pamId): Collection
    {
        return $this->model->where('pam_id', $pamId)
            ->doesntHave('meters')
            ->get();
    }
}
