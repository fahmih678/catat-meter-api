<?php

namespace App\Repositories;

use App\Models\MeterReading;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class MeterReadingRepository extends BaseRepository
{
    protected function getModel(): Model
    {
        return new MeterReading();
    }

    public function findByMeterAndRegisteredMonth(int $meterId, int $registeredMonthId): ?MeterReading
    {
        return $this->model->where('meter_id', $meterId)
            ->where('registered_month_id', $registeredMonthId)
            ->first();
    }

    public function getByPamAndPeriod(int $pamId, string $period): Collection
    {
        return $this->model->where('pam_id', $pamId)
            ->where('period', $period)
            ->with(['meter', 'meter.customer', 'readingBy'])
            ->get();
    }

    public function getPendingRecords(int $pamId): Collection
    {
        return $this->model->where('pam_id', $pamId)
            ->where('status', 'pending')
            ->with(['meter', 'meter.customer'])
            ->orderBy('period', 'desc')
            ->get();
    }

    public function getRecordsByStatus(int $pamId, string $status): Collection
    {
        return $this->model->where('pam_id', $pamId)
            ->where('status', $status)
            ->with(['meter', 'meter.customer', 'recordedBy'])
            ->orderBy('period', 'desc')
            ->get();
    }

    public function getRecordsForBilling(int $pamId, string $period): Collection
    {
        return $this->model->where('pam_id', $pamId)
            ->where('period', $period)
            ->where('status', 'pending')
            ->doesntHave('bills')
            ->with(['meter', 'meter.customer', 'meter.customer.tariffGroup'])
            ->get();
    }

    public function searchRecords(int $pamId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->where('pam_id', $pamId)
            ->with(['meter', 'meter.customer', 'recordedBy']);

        if (!empty($filters['period'])) {
            $query->where('period', $filters['period']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['customer_number'])) {
            $query->whereHas('meter.customer', function ($q) use ($filters) {
                $q->where('customer_number', 'like', "%{$filters['customer_number']}%");
            });
        }

        if (!empty($filters['recorded_by'])) {
            $query->where('recorded_by', $filters['recorded_by']);
        }

        return $query->orderBy('period', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getVolumeUsageStatistics(int $pamId, string $period): array
    {
        $records = $this->model->where('pam_id', $pamId)
            ->where('period', $period)
            ->where('status', '!=', 'draft');

        return [
            'total_volume' => $records->sum('volume_usage'),
            'average_volume' => $records->avg('volume_usage'),
            'max_volume' => $records->max('volume_usage'),
            'min_volume' => $records->min('volume_usage'),
            'total_records' => $records->count(),
        ];
    }

    public function updateStatus(int $recordId, string $status): bool
    {
        return $this->model->where('id', $recordId)
            ->update(['status' => $status]);
    }

    public function getAllWithFilters(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->with(['meter', 'meter.customer', 'meter.customer.pam']);

        if (!empty($filters['pam_id'])) {
            $query->whereHas('meter.customer', function ($q) use ($filters) {
                $q->where('pam_id', $filters['pam_id']);
            });
        }

        if (!empty($filters['meter_id'])) {
            $query->where('meter_id', $filters['meter_id']);
        }

        if (!empty($filters['period'])) {
            $query->where('period', $filters['period']);
        }

        if (!empty($filters['reading_date_from'])) {
            $query->where('reading_at', '>=', $filters['reading_date_from']);
        }

        if (!empty($filters['reading_date_to'])) {
            $query->where('reading_at', '<=', $filters['reading_date_to']);
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('reading_at', 'desc')->paginate($perPage);
    }

    public function getLastRecordByMeter(int $meterId): ?MeterReading
    {
        return $this->model->where('meter_id', $meterId)
            ->orderBy('reading_at', 'desc')
            ->first();
    }

    public function getUsageByPeriod(int $meterId, string $period, int $months): array
    {
        $records = $this->model->where('meter_id', $meterId)
            ->where('reading_at', '>=', now()->subMonths($months))
            ->orderBy('reading_at')
            ->get();

        $usage = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthStr = $month->format('Y-m');

            $monthRecords = $records->filter(function ($record) use ($monthStr) {
                return $record->period === $monthStr;
            });

            $usage[] = [
                'period' => $monthStr,
                'usage' => $monthRecords->sum('usage'),
                'records_count' => $monthRecords->count()
            ];
        }

        return $usage;
    }

    public function countByPam(int $pamId, ?string $period = null): int
    {
        $query = $this->model->whereHas('meter.customer', function ($q) use ($pamId) {
            $q->where('pam_id', $pamId);
        });

        if ($period) {
            $query->where('period', $period);
        }

        return $query->count();
    }

    public function countMetersRead(int $pamId, ?string $period = null): int
    {
        $query = $this->model->whereHas('meter.customer', function ($q) use ($pamId) {
            $q->where('pam_id', $pamId);
        });

        if ($period) {
            $query->where('period', $period);
        }

        return $query->distinct('meter_id')->count('meter_id');
    }

    public function countTotalMeters(int $pamId): int
    {
        return \App\Models\Meter::whereHas('customer', function ($q) use ($pamId) {
            $q->where('pam_id', $pamId);
        })->count();
    }

    public function getAverageUsage(int $pamId, ?string $period = null): float
    {
        $query = $this->model->whereHas('meter.customer', function ($q) use ($pamId) {
            $q->where('pam_id', $pamId);
        });

        if ($period) {
            $query->where('period', $period);
        }

        return round($query->avg('usage') ?? 0, 2);
    }

    public function getTotalUsage(int $pamId, ?string $period = null): float
    {
        $query = $this->model->whereHas('meter.customer', function ($q) use ($pamId) {
            $q->where('pam_id', $pamId);
        });

        if ($period) {
            $query->where('period', $period);
        }

        return round($query->sum('usage') ?? 0, 2);
    }

    public function getMissingReadings(int $pamId, string $period): array
    {
        $allMeters = \App\Models\Meter::whereHas('customer', function ($q) use ($pamId) {
            $q->where('pam_id', $pamId);
        })->where('status', 'active')->with(['customer'])->get();

        $recordedMeterIds = $this->model->where('period', $period)
            ->whereHas('meter.customer', function ($q) use ($pamId) {
                $q->where('pam_id', $pamId);
            })
            ->pluck('meter_id')
            ->toArray();

        $missingMeters = $allMeters->whereNotIn('id', $recordedMeterIds);

        return $missingMeters->map(function ($meter) use ($period) {
            return [
                'meter_id' => $meter->id,
                'meter_number' => $meter->meter_number,
                'customer' => [
                    'id' => $meter->customer->id,
                    'name' => $meter->customer->name,
                    'customer_number' => $meter->customer->customer_number,
                    'address' => $meter->customer->address
                ],
                'period' => $period,
                'missing_since' => now()->toDateString()
            ];
        })->values()->toArray();
    }
}
