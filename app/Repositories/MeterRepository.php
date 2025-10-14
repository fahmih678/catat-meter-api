<?php

namespace App\Repositories;

use App\Models\Meter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MeterRepository extends BaseRepository
{
    protected function getModel(): Model
    {
        return new Meter();
    }

    public function findBySerialNumber(string $serialNumber): ?Meter
    {
        return $this->model->where('meter_number', $serialNumber)->first();
    }

    public function getByCustomer(int $customerId): Collection
    {
        return $this->model->where('customer_id', $customerId)
            ->with(['customer', 'meterReadings'])
            ->get();
    }

    public function getByPam(int $pamId): Collection
    {
        return $this->model->where('pam_id', $pamId)
            ->with(['customer', 'meterReadings'])
            ->get();
    }

    public function getMetersNeedingReading(int $pamId, string $period): Collection
    {
        return $this->model->where('pam_id', $pamId)
            ->where('status', 'active')
            ->whereDoesntHave('meterReadings', function ($query) use ($period) {
                $query->where('period', $period);
            })
            ->with(['customer', 'customer.area'])
            ->get();
    }

    public function getMetersWithLatestReading(int $pamId): Collection
    {
        return $this->model->where('pam_id', $pamId)
            ->with([
                'customer',
                'meterReadings' => function ($query) {
                    $query->latest('period')->limit(1);
                }
            ])
            ->get();
    }

    public function getMetersNotRecordedForDays(int $pamId, int $days = 30): Collection
    {
        $cutoffDate = Carbon::now()->subDays($days);

        return $this->model->where('pam_id', $pamId)
            ->where('status', 'active')
            ->where(function ($query) use ($cutoffDate) {
                $query->whereNull('last_recorded_at')
                    ->orWhere('last_recorded_at', '<', $cutoffDate);
            })
            ->with(['customer'])
            ->get();
    }

    public function updateLastRecorded(int $meterId): bool
    {
        return $this->model->where('id', $meterId)
            ->update(['last_recorded_at' => Carbon::now()]);
    }

    public function getAllWithFilters(array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->model->with(['customer', 'customer.area', 'customer.pam']);

        if (!empty($filters['pam_id'])) {
            $query->whereHas('customer', function ($q) use ($filters) {
                $q->where('pam_id', $filters['pam_id']);
            });
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['area_id'])) {
            $query->whereHas('customer', function ($q) use ($filters) {
                $q->where('area_id', $filters['area_id']);
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->paginate($perPage);
    }

    public function findByCustomer(int $customerId): array
    {
        return $this->model->where('customer_id', $customerId)
            ->with(['customer', 'meterReadings' => function ($query) {
                $query->latest('reading_at')->limit(5);
            }])
            ->get()
            ->toArray();
    }

    public function search(array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->model->with(['customer', 'customer.area', 'customer.pam']);

        if (!empty($filters['pam_id'])) {
            $query->whereHas('customer', function ($q) use ($filters) {
                $q->where('pam_id', $filters['pam_id']);
            });
        }

        if (!empty($filters['query'])) {
            $searchTerm = $filters['query'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('meter_number', 'like', "%{$searchTerm}%")
                    ->orWhere('brand', 'like', "%{$searchTerm}%")
                    ->orWhere('type', 'like', "%{$searchTerm}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($searchTerm) {
                        $customerQuery->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('customer_number', 'like', "%{$searchTerm}%");
                    });
            });
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['area_id'])) {
            $query->whereHas('customer', function ($q) use ($filters) {
                $q->where('area_id', $filters['area_id']);
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->paginate($perPage);
    }

    public function getLastMeterByCustomer(int $customerId): ?Meter
    {
        return $this->model->where('customer_id', $customerId)
            ->orderBy('id', 'desc')
            ->first();
    }
}
